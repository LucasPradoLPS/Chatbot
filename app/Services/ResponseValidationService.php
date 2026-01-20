<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Validação Automática de Respostas
 * 
 * Antes de enviar, valida se:
 * - Não contradiz contexto
 * - Não promete algo indevido
 * - Cita valores coerentes
 * - Não inventa condições/taxas/documentos
 */
class ResponseValidationService
{
    /**
     * Validar resposta antes de enviar ao cliente
     */
    public static function validarResposta(
        string $resposta,
        array $contexto,
        array $imovelData = []
    ): array {
        $erros = [];
        $avisos = [];
        
        // Validação 1: Valores citados são coerentes?
        $validacaoValores = self::validarValoresCitados($resposta, $imovelData);
        if (!$validacaoValores['ok']) {
            $erros[] = $validacaoValores['erro'];
        }
        
        // Validação 2: Não promete aprovação de crédito?
        if (self::prometeAprovacaoIndebida($resposta)) {
            $erros[] = "Resposta não deve prometer aprovação de financiamento sem disclaimers";
        }
        
        // Validação 3: Não inventa condições legais?
        if (self::inventaCondicoesLegais($resposta)) {
            $erros[] = "Resposta cita condições legais/documentos sem base";
        }
        
        // Validação 4: Não contradiz contexto anterior?
        $validacaoContexto = self::validarConsistenciaContexto($resposta, $contexto);
        if (!$validacaoContexto['ok']) {
            $avisos[] = $validacaoContexto['aviso'];
        }
        
        // Validação 5: Linguagem apropriada?
        if (self::temLinguagemInapropriada($resposta)) {
            $erros[] = "Resposta contém linguagem inapropriada ou spam";
        }
        
        return [
            'valida' => count($erros) === 0,
            'erros' => $erros,
            'avisos' => $avisos,
            'pode_enviar' => count($erros) === 0,
        ];
    }
    
    /**
     * Validar se valores citados batem com os dados
     */
    private static function validarValoresCitados(string $resposta, array $imovelData): array
    {
        // Procurar por "R$ XXXXX" na resposta
        preg_match_all('/R\$\s*([\d.,]+)/i', $resposta, $matches);
        
        foreach ($matches[1] as $valorCitado) {
            $valorCitadoNum = self::parseValor($valorCitado);
            
            // Se tem data de imóvel, comparar com valor dele
            if (isset($imovelData['valor'])) {
                $diferenca = abs($valorCitadoNum - $imovelData['valor']) / $imovelData['valor'] * 100;
                
                // Mais de 20% de diferença é suspeito
                if ($diferenca > 20) {
                    return [
                        'ok' => false,
                        'erro' => "Valor citado (R$ {$valorCitado}) não bate com imóvel (R$ " . number_format($imovelData['valor'], 0, ',', '.') . ")",
                    ];
                }
            }
        }
        
        return ['ok' => true];
    }
    
    /**
     * Detectar promessas indevidas de aprovação
     */
    private static function prometeAprovacaoIndebida(string $resposta): bool
    {
        $padroes = [
            'você vai ser aprovado',
            'você consegue financiar com certeza',
            'banco aprova com garantia',
            'aprovação garantida',
            'você consegue 100%',
        ];
        
        $respostaLower = strtolower($resposta);
        foreach ($padroes as $padrao) {
            if (stripos($respostaLower, $padrao) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detectar invenção de condições legais
     */
    private static function inventaCondicoesLegais(string $resposta): bool
    {
        // Frases muito específicas sobre lei/documentação sem context
        $palavras = ['exige documento', 'a lei permite', 'fgts cobre', 'iptu não paga'];
        
        foreach ($palavras as $palavra) {
            // Se fala sobre isso mas não tem contexto técnico, é suspeito
            if (stripos($resposta, $palavra) !== false) {
                // TODO: Implementar validação mais sofisticada
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Validar consistência com contexto anterior
     */
    private static function validarConsistenciaContexto(string $resposta, array $contexto): array
    {
        // Se contexto diz que cliente descartou um bairro,
        // não recomende imóvel lá
        
        if (isset($contexto['bairros_nao_negociaveis'])) {
            $bairrosNao = (array)$contexto['bairros_nao_negociaveis'];
            $respostaLower = strtolower($resposta);
            
            foreach ($bairrosNao as $bairro) {
                if (stripos($respostaLower, strtolower($bairro)) !== false) {
                    return [
                        'ok' => false,
                        'aviso' => "Resposta menciona bairro {$bairro} que cliente descartou",
                    ];
                }
            }
        }
        
        return ['ok' => true];
    }
    
    /**
     * Detectar linguagem inapropriada
     */
    private static function temLinguagemInapropriada(string $resposta): bool
    {
        // Palavras proibidas, spam markers, etc
        $proibidas = ['ganhar dinheiro fácil', 'rápido demais', 'qualquer aprovação'];
        
        $respostaLower = strtolower($resposta);
        foreach ($proibidas as $palavra) {
            if (stripos($respostaLower, $palavra) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse de valor (ex: "500.000" -> 500000)
     */
    private static function parseValor(string $valor): float
    {
        $valor = preg_replace('/[^0-9.,]/', '', $valor);
        $valor = str_replace(',', '.', $valor);
        $valor = str_replace('.', '', $valor);
        $valor = str_replace('.', '.', preg_replace('/\.(?=\d{3}(?!\d))/', '', $valor));
        
        return (float)$valor;
    }
    
    /**
     * Log de validação (para auditoria)
     */
    public static function registrarValidacao(
        string $clienteJid,
        string $resposta,
        array $resultadoValidacao
    ): void {
        Log::info("Validação de resposta", [
            'cliente_jid' => $clienteJid,
            'valida' => $resultadoValidacao['valida'],
            'erros' => $resultadoValidacao['erros'],
            'avisos' => $resultadoValidacao['avisos'],
        ]);
    }
}
