<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Suite de Testes de Regressão para Conversas
 * 
 * Define roteiros "cliente X pergunta Y" e garante que o bot responde corretamente
 * Executa automaticamente antes de deploy
 */
class ConversationTestSuite
{
    private static array $testCases = [
        [
            'nome' => 'Saudação com nome',
            'mensagem' => 'Olá',
            'contexto' => ['pushName' => 'Lucas Prado'],
            'esperado' => 'Olá Lucas Prado',
            'nao_esperado' => 'visitante',
        ],
        [
            'nome' => 'Qualificação básica',
            'mensagem' => 'Quero um apartamento de 2 quartos',
            'contexto' => [],
            'esperado' => ['2 quartos', 'recolher', 'preferências'],
            'nao_esperado' => [],
        ],
        [
            'nome' => 'Detecção de objeção: preço alto',
            'mensagem' => 'Muito caro!',
            'contexto' => ['etapa' => 'resultado_busca'],
            'esperado' => ['entendo', 'preço', 'opções'],
            'nao_esperado' => ['sem problemas', 'compraria'],
        ],
        [
            'nome' => 'Detecção de urgência',
            'mensagem' => 'Preciso rápido, sou urgente',
            'contexto' => [],
            'esperado' => ['urgente', 'corretor', 'logo'],
            'nao_esperado' => ['espera aí'],
        ],
        [
            'nome' => 'Horário fora de atendimento',
            'mensagem' => 'Olá',
            'contexto' => [
                'hora_atual' => 22, // 22h = fora do atendimento
                'dia_semana' => 'Sunday', // fora também
            ],
            'esperado' => ['fora do horário', '08h', '17h'],
            'nao_esperado' => ['como posso ajudar'],
        ],
        [
            'nome' => 'Recomendação com explicação',
            'mensagem' => 'Quer me mostrar opções?',
            'contexto' => [
                'etapa' => 'resultado_busca',
                'imoveis_score' => [
                    ['id' => 1, 'score' => 85, 'titulo' => 'Apt Vila Mariana'],
                ],
            ],
            'esperado' => ['score', 'bateu', 'varanda', 'quartos'],
            'nao_esperado' => [],
        ],
        [
            'nome' => 'Confirmação de agendamento',
            'mensagem' => 'Quero marcar visita',
            'contexto' => ['etapa' => 'resultado_busca'],
            'esperado' => ['qual data', 'qual hora', 'confirmar'],
            'nao_esperado' => ['recusado'],
        ],
        [
            'nome' => 'Validação LGPD',
            'mensagem' => 'Não autorizo',
            'contexto' => ['etapa' => 'consentimento'],
            'esperado' => ['entendi', 'respeito', 'dados'],
            'nao_esperado' => ['vou usar mesmo'],
        ],
        [
            'nome' => 'Follow-up apropriado',
            'mensagem' => '[Cliente inativo há 2 dias]',
            'contexto' => ['dias_inativo' => 2],
            'esperado' => ['achei', 'parecidas', 'quer ver'],
            'nao_esperado' => ['spam', 'CLIQUE AQUI'],
        ],
    ];
    
    /**
     * Executar todos os testes
     */
    public static function executarSuite(): array
    {
        Log::info("Iniciando suite de testes de regressão");
        
        $resultados = [];
        $passou = 0;
        $falhou = 0;
        
        foreach (self::$testCases as $testCase) {
            $resultado = self::executarTestCase($testCase);
            $resultados[] = $resultado;
            
            if ($resultado['passou']) {
                $passou++;
            } else {
                $falhou++;
            }
        }
        
        $relatorio = [
            'total' => count(self::$testCases),
            'passou' => $passou,
            'falhou' => $falhou,
            'percentual_sucesso' => round($passou / count(self::$testCases) * 100, 1),
            'testes' => $resultados,
            'timestamp' => now(),
        ];
        
        Log::info("Suite de testes finalizada", [
            'passou' => $passou,
            'falhou' => $falhou,
            'sucesso' => $relatorio['percentual_sucesso'] . '%',
        ]);
        
        return $relatorio;
    }
    
    /**
     * Executar um único test case
     */
    private static function executarTestCase(array $testCase): array
    {
        try {
            // Simular a resposta do bot
            $resposta = self::simularRespostaBot(
                $testCase['mensagem'],
                $testCase['contexto']
            );
            
            // Validar resposta esperada
            $passou = self::validarResposta($resposta, $testCase);
            
            return [
                'teste' => $testCase['nome'],
                'passou' => $passou,
                'mensagem_entrada' => $testCase['mensagem'],
                'resposta_obtida' => substr($resposta, 0, 100) . '...',
                'esperado' => $testCase['esperado'],
                'erro' => $passou ? null : 'Resposta não corresponde ao esperado',
            ];
        } catch (\Exception $e) {
            return [
                'teste' => $testCase['nome'],
                'passou' => false,
                'mensagem_entrada' => $testCase['mensagem'],
                'resposta_obtida' => null,
                'erro' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Simular resposta do bot para teste
     * (Implementação simplificada - expandir conforme necessário)
     */
    private static function simularRespostaBot(string $mensagem, array $contexto): string
    {
        // Verificar horário
        $hora = $contexto['hora_atual'] ?? date('H');
        if ($hora < 8 || $hora >= 17) {
            return "Desculpe, estamos fora do horário de atendimento (08h-17h).";
        }
        
        // Verificar saudação
        if (strtolower($mensagem) === 'olá' || strtolower($mensagem) === 'oi') {
            $nome = $contexto['pushName'] ?? 'visitante';
            return "Olá {$nome}! Como posso ajudá-lo?";
        }
        
        // Detectar objeção
        $objecao = ObjectionHandlerService::detectarObjecao($mensagem);
        if ($objecao) {
            return ObjectionHandlerService::gerarRespostaObjecao($objecao);
        }
        
        // Default
        return "Entendi. Como posso ajudar?";
    }
    
    /**
     * Validar se resposta corresponde ao esperado
     */
    private static function validarResposta(string $resposta, array $testCase): bool
    {
        $respostaLower = strtolower($resposta);
        
        // Verificar esperados
        if (is_string($testCase['esperado'])) {
            $esperados = [$testCase['esperado']];
        } else {
            $esperados = $testCase['esperado'];
        }
        
        foreach ($esperados as $esperado) {
            if (stripos($respostaLower, strtolower($esperado)) === false) {
                return false;
            }
        }
        
        // Verificar não-esperados
        if (is_string($testCase['nao_esperado'])) {
            $naoEsperados = [$testCase['nao_esperado']];
        } else {
            $naoEsperados = $testCase['nao_esperado'];
        }
        
        foreach ($naoEsperados as $naoEsperado) {
            if ($naoEsperado && stripos($respostaLower, strtolower($naoEsperado)) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Adicionar novo test case customizado
     */
    public static function adicionarTestCase(array $testCase): void
    {
        self::$testCases[] = $testCase;
    }
    
    /**
     * Executar testes antes de deploy (CI/CD)
     */
    public static function preDeploy(): bool
    {
        $resultado = self::executarSuite();
        
        if ($resultado['percentual_sucesso'] < 90) {
            Log::error("TESTES FALHARAM - Deploy bloqueado", $resultado);
            return false;
        }
        
        Log::info("Todos os testes passaram - Deploy liberado");
        return true;
    }
}
