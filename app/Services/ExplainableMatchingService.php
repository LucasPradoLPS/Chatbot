<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Serviço de Explicabilidade do Matching
 * 
 * Mostra ao cliente POR QUE aquele imóvel foi recomendado
 * Ex: "Bateu: varanda ✓ + vaga ✓ + faixa (8% acima)"
 */
class ExplainableMatchingService
{
    /**
     * Gerar explicação de score em linguagem simples
     */
    public static function explicarScore(
        array $imovel,
        array $slots,
        int $scoreTotal,
        array $detalhesScore
    ): string {
        $explicacao = self::montarExplicacao($imovel, $scoreTotal, $detalhesScore);
        
        return <<<MSG
🎯 Por que recomendei este imóvel?

{$explicacao}

*Score: {$scoreTotal}/100*

Quer visitar? | Ver fotos | Ver no mapa
MSG;
    }
    
    /**
     * Montar explicação visual
     */
    private static function montarExplicacao(
        array $imovel,
        int $score,
        array $detalhes
    ): string {
        $linhas = [];
        
        // Critério: Bairro
        if (isset($detalhes['bairro'])) {
            $status = $detalhes['bairro']['match'] ? '✅' : '❌';
            $linhas[] = "{$status} Bairro: {$imovel['bairro']}";
        }
        
        // Critério: Preço
        if (isset($detalhes['preco'])) {
            $diff = $detalhes['preco']['diferenca_percentual'];
            if ($diff <= 0) {
                $status = '✅';
                $texto = "dentro do orçamento";
            } elseif ($diff <= 15) {
                $status = '⚠️';
                $texto = "{$diff}% acima (estica um pouco)";
            } else {
                $status = '❌';
                $texto = "{$diff}% acima (muito acima)";
            }
            $linhas[] = "{$status} Preço: R$ " . number_format($imovel['valor'], 0, ',', '.') . " - {$texto}";
        }
        
        // Critério: Quartos
        if (isset($detalhes['quartos'])) {
            $status = $detalhes['quartos']['match'] ? '✅' : '⚠️';
            $linhas[] = "{$status} {$imovel['quartos']} quartos (você quer {$detalhes['quartos']['desejado']})";
        }
        
        // Critério: Vagas
        if (isset($detalhes['vagas'])) {
            $status = $detalhes['vagas']['match'] ? '✅' : '⚠️';
            $linhas[] = "{$status} {$imovel['vagas']} vagas de garagem";
        }
        
        // Prioridades
        if (isset($detalhes['prioridades'])) {
            $atendidas = $detalhes['prioridades']['atendidas'] ?? [];
            if (!empty($atendidas)) {
                $linhas[] = "✅ Tem: " . implode(', ', $atendidas);
            }
            
            $nao_atendidas = $detalhes['prioridades']['nao_atendidas'] ?? [];
            if (!empty($nao_atendidas)) {
                $linhas[] = "❌ Não tem: " . implode(', ', $nao_atendidas);
            }
        }
        
        return implode("\n", $linhas);
    }
    
    /**
     * Gerar card com comparação visual (para A/B)
     */
    public static function gerarCardComparativo(
        array $imovelA,
        array $imovelB,
        array $detalhesA,
        array $detalhesB
    ): string {
        $preco_a = number_format($imovelA['valor'], 0, ',', '.');
        $preco_b = number_format($imovelB['valor'], 0, ',', '.');
        
        $quartos_a = $imovelA['quartos'];
        $quartos_b = $imovelB['quartos'];
        
        $vagas_a = $imovelA['vagas'];
        $vagas_b = $imovelB['vagas'];
        
        return <<<MSG
🏠 *Comparação de Imóveis*

*OPÇÃO A*
{$imovelA['titulo']}
📍 {$imovelA['bairro']}
💰 R$ {$preco_a}
🛏️ {$quartos_a} quartos | 🚗 {$vagas_a} vagas

*OPÇÃO B*
{$imovelB['titulo']}
📍 {$imovelB['bairro']}
💰 R$ {$preco_b}
🛏️ {$quartos_b} quartos | 🚗 {$vagas_b} vagas

Qual mais te interessa?
→ Opção A | → Opção B | ❓ Dúvida
MSG;
    }
    
    /**
     * Mostrar por que um imóvel NÃO foi recomendado
     */
    public static function explicarDescarte(
        array $imovel,
        array $motivosDescarte
    ): string {
        $motivos = [];
        
        if (in_array('fora_do_orcamento', $motivosDescarte)) {
            $motivos[] = '💰 Muito acima do seu orçamento';
        }
        
        if (in_array('bairro_errado', $motivosDescarte)) {
            $motivos[] = '📍 Não está na região que você pediu';
        }
        
        if (in_array('poucos_quartos', $motivosDescarte)) {
            $motivos[] = '🛏️ Tem menos quartos que você quer';
        }
        
        if (in_array('preferencia_descartada', $motivosDescarte)) {
            $motivos[] = '🚫 Você pediu pra não incluir este tipo';
        }
        
        $motivosTexto = implode("\n", array_map(fn($m) => "• {$m}", $motivos));
        
        return "Não recomendei este pois:\n{$motivosTexto}\n\nWantmore info?";
    }
}
