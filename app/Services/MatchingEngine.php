<?php

namespace App\Services;

use App\Config\MatchingEngineConfig;
use Illuminate\Support\Collection;

/**
 * Motor de recomendação com scoring de match para imóveis
 */
class MatchingEngine
{
    /**
     * Calcular score de match para um imóvel baseado nos slots do usuário
     *
     * @param array $imovel Dados do imóvel com chaves: bairro, valor, quartos, vagas, prioridades[], etc.
     * @param array $slots Slots preenchidos do usuário: bairro_regiao[], faixa_valor_max, quartos, vagas, tags_prioridades[]
     * @return array Score e detalhes do cálculo
     */
    public static function calculateScore(array $imovel, array $slots): array
    {
        $score = 0;
        $detalhes = [];
        $penalidades = 0;

        // +40 se bairro/região bate
        if (isset($slots['bairro_regiao']) && isset($imovel['bairro'])) {
            $bairrosDesejados = is_array($slots['bairro_regiao']) ? $slots['bairro_regiao'] : [$slots['bairro_regiao']];
            if (in_array(strtolower($imovel['bairro']), array_map('strtolower', $bairrosDesejados))) {
                $score += MatchingEngineConfig::getPoint('neighborhood_match');
                $detalhes[] = '+' . MatchingEngineConfig::getPoint('neighborhood_match') . ' (Bairro corresponde)';
            }
        }

        // +20 se valor dentro do máximo (ou próximo)
        $valorMaximo = isset($slots['faixa_valor_max']) ? (int)$slots['faixa_valor_max'] : PHP_INT_MAX;
        $valorImovel = isset($imovel['valor']) ? (int)$imovel['valor'] : 0;

        if ($valorMaximo > 0 && $valorImovel > 0) {
            if ($valorImovel <= $valorMaximo) {
                // Dentro do orçamento
                $score += MatchingEngineConfig::getPoint('value_within_budget');
                $detalhes[] = '+' . MatchingEngineConfig::getPoint('value_within_budget') . ' (Valor dentro do orçamento)';
            } else {
                // Acima do orçamento - penalidade
                $diferenca = $valorImovel - $valorMaximo;
                $percentualAcima = ($diferenca / $valorMaximo) * 100;

                if ($percentualAcima <= MatchingEngineConfig::getThreshold('over_budget_threshold')) {
                    // "Quase lá" - pequena penalidade de -30, mas ainda viável
                    $penalidades += MatchingEngineConfig::getPenalty('over_budget_light');
                    $detalhes[] = MatchingEngineConfig::getPenalty('over_budget_light') . ' (Estica ' . round($percentualAcima, 1) . '% acima do máximo)';
                } else {
                    // "Muito fora" - penalidade severa
                    $penalidades += MatchingEngineConfig::getPenalty('over_budget_heavy');
                    $detalhes[] = MatchingEngineConfig::getPenalty('over_budget_heavy') . ' (Muito acima do máximo: ' . round($percentualAcima, 1) . '%)';
                }
            }
        }

        // +10 por quarto compatível (exato ou próximo)
        if (isset($slots['quartos']) && isset($imovel['quartos'])) {
            $quartosDesejados = (int)$slots['quartos'];
            $quartosImovel = (int)$imovel['quartos'];

            if ($quartosImovel === $quartosDesejados) {
                $score += MatchingEngineConfig::getPoint('bedrooms_exact');
                $detalhes[] = '+' . MatchingEngineConfig::getPoint('bedrooms_exact') . ' (Quartos exatos)';
            } elseif ($quartosImovel === $quartosDesejados + 1) {
                $score += MatchingEngineConfig::getPoint('bedrooms_plus_one');
                $detalhes[] = '+' . MatchingEngineConfig::getPoint('bedrooms_plus_one') . ' (Um quarto a mais)';
            }
        }

        // +10 por vaga compatível
        if (isset($slots['vagas']) && isset($imovel['vagas'])) {
            $vagasDesejadas = (int)$slots['vagas'];
            $vagasImovel = (int)$imovel['vagas'];

            if ($vagasImovel >= $vagasDesejadas) {
                $score += MatchingEngineConfig::getPoint('parking_sufficient');
                $detalhes[] = '+' . MatchingEngineConfig::getPoint('parking_sufficient') . ' (Vagas atendem)';
            }
        }

        // +5 por cada prioridade atendida (pet, varanda, suíte, etc.)
        if (isset($slots['tags_prioridades']) && isset($imovel['tags'])) {
            $prioridades = is_array($slots['tags_prioridades']) ? $slots['tags_prioridades'] : [$slots['tags_prioridades']];
            $tagsimovel = is_array($imovel['tags']) ? $imovel['tags'] : [$imovel['tags']];

            $prioridadesAtendidas = 0;
            foreach ($prioridades as $pri) {
                if (in_array(strtolower($pri), array_map('strtolower', $tagsimovel))) {
                    $prioridadesAtendidas++;
                }
            }

            $scoreTagsCount = $prioridadesAtendidas * MatchingEngineConfig::getPoint('priority_per_tag');
            if ($scoreTagsCount > 0) {
                $score += $scoreTagsCount;
                $detalhes[] = '+' . $scoreTagsCount . ' (' . $prioridadesAtendidas . ' prioridades atendidas)';
            }
        }

        // Aplicar penalidades
        $score = max(0, $score + $penalidades);

        return [
            'score' => $score,
            'penalidades' => $penalidades,
            'detalhes' => $detalhes,
        ];
    }

    /**
     * Ordenar e filtrar imóveis por categoria (exatos vs. "quase lá")
     *
     * @param array $imoveis Lista de imóveis com scores
     * @param int $maxExatos Máximo de imóveis "exatos" a retornar
     * @param int $maxQuaseLa Máximo de imóveis "quase lá" a retornar
     * @return array ['exatos' => [...], 'quase_la' => [...], 'descartados' => [...]]
     */
    public static function categorizeResults(array $imoveis, int $maxExatos = 5, int $maxQuaseLa = 2): array
    {
        // Separar por categoria: exatos (score >= 70) vs. quase lá (score 40-69) vs. descartados
        $exatos = [];
        $quaseLa = [];
        $descartados = [];

        foreach ($imoveis as $imovel) {
            $score = $imovel['score_detalhes']['score'] ?? 0;

            if ($score >= MatchingEngineConfig::getThreshold('exact')) {
                $exatos[] = $imovel;
            } elseif ($score >= MatchingEngineConfig::getThreshold('almost')) {
                $quaseLa[] = $imovel;
            } else {
                $descartados[] = $imovel;
            }
        }

        // Ordenar por score (maior primeiro)
        usort($exatos, fn($a, $b) => ($b['score_detalhes']['score'] ?? 0) <=> ($a['score_detalhes']['score'] ?? 0));
        usort($quaseLa, fn($a, $b) => ($b['score_detalhes']['score'] ?? 0) <=> ($a['score_detalhes']['score'] ?? 0));

        // Limitar quantidade
        $exatos = array_slice($exatos, 0, MatchingEngineConfig::PRESENTATION_LIMITS['max_exatos']);
        $quaseLa = array_slice($quaseLa, 0, MatchingEngineConfig::PRESENTATION_LIMITS['max_quase_la']);

        return [
            'exatos' => $exatos,
            'quase_la' => $quaseLa,
            'descartados' => $descartados,
        ];
    }

    /**
     * Formatar exibição de um imóvel para apresentação ao usuário
     *
     * @param array $imovel
     * @param string $categoria 'exato' ou 'quase_la'
     * @return string Texto formatado
     */
    public static function formatPropertyCard(array $imovel, string $categoria = 'exato'): string
    {
        $codigo = $imovel['id'] ?? ($imovel['codigo'] ?? null);
        $titulo = $imovel['titulo'] ?? 'Imóvel sem título';
        $bairro = $imovel['bairro'] ?? 'Localização não informada';
        $valor = isset($imovel['valor']) ? 'R$ ' . number_format($imovel['valor'], 0, '.', '.') : 'Preço não informado';
        $quartos = $imovel['quartos'] ?? '-';
        $vagas = $imovel['vagas'] ?? '-';
        $score = $imovel['score_detalhes']['score'] ?? 0;

        $badge = $categoria === 'quase_la' ? '⚠️ ESTICA UM POUCO' : '✅ EXATO';

        $codigoFmt = $codigo ? " #$codigo" : '';
        $card = "🏠 *$titulo*$codigoFmt\n";
        $card .= "📍 $bairro\n";
        $card .= "💰 $valor\n";
        $card .= "🛏️ $quartos quartos | 🚗 $vagas vagas\n";

        if ($categoria === 'quase_la') {
            $card .= "\n$badge - Esse está um pouco acima do seu orçamento, mas vale a pena ver!\n";
        }

        if ($codigo) {
            $card .= "\n→ Ver fotos | → Ver no mapa | → Agendar visita (#$codigo) | → Mais info";
        } else {
            $card .= "\n→ Ver fotos | → Ver no mapa | → Agendar visita | → Mais info";
        }

        return $card;
    }

    /**
     * Gerar recomendações formatadas para enviar ao usuário
     *
     * @param array $imoveis Lista de imóveis (sem score ainda)
     * @param array $slots Slots do usuário
     * @param int $maxResultados Máximo de resultados totais a retornar
     * @return array ['mensagem' => string, 'imoveis' => array]
     */
    public static function generateRecommendations(array $imoveis, array $slots, int $maxResultados = 8): array
    {
        // Calcular score para cada imóvel
        $ioveisComScore = [];
        foreach ($imoveis as $imovel) {
            $scoreDetalhes = self::calculateScore($imovel, $slots);
            $imovel['score_detalhes'] = $scoreDetalhes;
            $ioveisComScore[] = $imovel;
        }

        // Categorizar e filtrar
        $maxExatos = min(5, $maxResultados);
        $maxQuaseLa = max(2, $maxResultados - $maxExatos);

        $categorizado = self::categorizeResults($ioveisComScore, $maxExatos, $maxQuaseLa);

        // Construir mensagem
        $mensagem = "🎯 *Encontrei as melhores opções para você!*\n\n";

        if (!empty($categorizado['exatos'])) {
            $mensagem .= "✅ *OPÇÕES PERFEITAS (dentro do seu orçamento):*\n";
            $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
            foreach ($categorizado['exatos'] as $imovel) {
                $mensagem .= self::formatPropertyCard($imovel, 'exato') . "\n\n";
            }
        }

        if (!empty($categorizado['quase_la'])) {
            $mensagem .= "\n⚠️ *ESTICA UM POUCO (vale a pena ver):*\n";
            $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
            foreach ($categorizado['quase_la'] as $imovel) {
                $mensagem .= self::formatPropertyCard($imovel, 'quase_la') . "\n\n";
            }
        }

        if (empty($categorizado['exatos']) && empty($categorizado['quase_la'])) {
            $mensagem .= "Desculpe, não encontrei opções exatas. Posso:\n";
            $mensagem .= "1. Aumentar o orçamento?\n";
            $mensagem .= "2. Mudar de bairro?\n";
            $mensagem .= "3. Falar com um corretor para opções customizadas?\n";
        }

        $mensagem .= "\n*Como prefere continuar?*\n";
        $mensagem .= "→ Quero ajustar (bairro, valor, etc.)\n";
        $mensagem .= "→ Agendar visita em uma delas\n";
        $mensagem .= "→ Falar com corretor\n";

        return [
            'mensagem' => $mensagem,
            'imoveis_exatos' => $categorizado['exatos'],
            'imoveis_quase_la' => $categorizado['quase_la'],
            'total_apresentados' => count($categorizado['exatos']) + count($categorizado['quase_la']),
        ];
    }
}
