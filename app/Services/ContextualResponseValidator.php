<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;

class ContextualResponseValidator
{
    private const STATE_RESPONSES = [
        'STATE_OBJETIVO' => [
            'valid_options' => ['comprar', 'alugar', 'vender', 'anunciar', 'investimento', 'suporte', 'falar com corretor'],
            'intent_map' => 'objetivo_usuario',
            'slot' => 'objetivo',
        ],
        'STATE_Q2_TIPO' => [
            'valid_options' => ['apartamento', 'casa', 'kitnet', 'comercial', 'terreno'],
            'intent_map' => 'qualificacao_tipo_imovel',
            'slot' => 'tipo_imovel',
        ],
        'STATE_Q3_QUARTOS' => [
            'valid_patterns' => ['/\d+\s*quarto/i', '/\d+\s*q/i'],
            'intent_map' => 'qualificacao_dados',
            'slot' => 'quartos',
        ],
        'STATE_LGPD' => [
            'valid_options' => ['sim', 'nÃ£o', 'nao', 'concordo', 'aceito', 'claro', 'okay', 'ok'],
            'intent_map' => 'resposta_binaria',
            'slot' => 'lgpd_consentimento',
        ],
        'STATE_PROPOSTA' => [
            'valid_options' => ['Ã  vista', 'a vista', 'financiamento', 'parcelado', 'consÃ³rcio', 'fgts', 'permuta', 'misto'],
            'intent_map' => 'resposta_forma_pagamento',
            'slot' => 'forma_pagamento',
        ],
    ];
    public static function validate(string $estadoAtual, string $mensagem): array
    {
        $respostaNormalizada = strtolower(trim($mensagem));
        if (!isset(self::STATE_RESPONSES[$estadoAtual])) {
            return [
                'is_valid' => null,  
                'intent_sugerida' => null,
                'motivo' => 'Estado sem validaÃ§Ã£o contextual',
            ];
        }
        $validacao = self::STATE_RESPONSES[$estadoAtual];
        if (isset($validacao['valid_options'])) {
            foreach ($validacao['valid_options'] as $opcao) {
                $opcaoNormalizada = strtolower(trim($opcao));
                if ($respostaNormalizada === $opcaoNormalizada || 
                    strpos($respostaNormalizada, $opcaoNormalizada) === 0 ||
                    strpos($respostaNormalizada, $opcaoNormalizada) !== false) {
                    Log::info('[VALIDACAO] Resposta vÃ¡lida para estado', [
                        'estado' => $estadoAtual,
                        'resposta' => $mensagem,
                        'opcao_matched' => $opcao,
                        'intent' => $validacao['intent_map'],
                    ]);
                    return [
                        'is_valid' => true,
                        'intent_sugerida' => $validacao['intent_map'],
                        'motivo' => "Resposta '{$mensagem}' reconhecida como opÃ§Ã£o vÃ¡lida para {$estadoAtual}",
                        'slot' => $validacao['slot'],
                        'valor_slot' => $opcao,  
                    ];
                }
            }
        }
        if (isset($validacao['valid_patterns'])) {
            foreach ($validacao['valid_patterns'] as $pattern) {
                if (preg_match($pattern, $mensagem)) {
                    Log::info('[VALIDACAO] Resposta vÃ¡lida por padrÃ£o', [
                        'estado' => $estadoAtual,
                        'resposta' => $mensagem,
                        'pattern' => $pattern,
                        'intent' => $validacao['intent_map'],
                    ]);
                    preg_match($pattern, $mensagem, $matches);
                    $valor = $matches[0] ?? $mensagem;
                    return [
                        'is_valid' => true,
                        'intent_sugerida' => $validacao['intent_map'],
                        'motivo' => "Resposta '{$mensagem}' corresponde ao padrÃ£o esperado para {$estadoAtual}",
                        'slot' => $validacao['slot'],
                        'valor_slot' => $valor,
                    ];
                }
            }
        }
        Log::info('[VALIDACAO] Resposta NÃƒO Ã© vÃ¡lida para estado', [
            'estado' => $estadoAtual,
            'resposta' => $mensagem,
            'opcoes_esperadas' => $validacao['valid_options'] ?? $validacao['valid_patterns'] ?? [],
        ]);
        return [
            'is_valid' => false,
            'intent_sugerida' => null,
            'motivo' => "Resposta '{$mensagem}' nÃ£o Ã© vÃ¡lida para {$estadoAtual}",
            'opcoes_esperadas' => $validacao['valid_options'] ?? [],
        ];
    }
    public static function updateSlotsFromValidation(array $slotsAtuais, array $validacao): array
    {
        if ($validacao['is_valid'] === true && isset($validacao['slot']) && isset($validacao['valor_slot'])) {
            $valor = ucfirst($validacao['valor_slot']);
            $slotsAtuais[$validacao['slot']] = $valor;
            Log::info('[SLOTS] Atualizado por validaÃ§Ã£o contextual', [
                'slot' => $validacao['slot'],
                'valor' => $valor,
            ]);
        }
        return $slotsAtuais;
    }
    public static function getValidOptionsForState(string $estadoAtual): array
    {
        return self::STATE_RESPONSES[$estadoAtual]['valid_options'] ?? [];
    }
    public static function getExpectedAnswerDescription(string $estadoAtual): string
    {
        return match($estadoAtual) {
            'STATE_OBJETIVO' => 'uma das opÃ§Ãµes: Comprar, Alugar, Vender, Anunciar, Investimento, Suporte ou Falar com corretor',
            'STATE_Q2_TIPO' => 'uma das opÃ§Ãµes: Apartamento, Casa, Kitnet, Comercial ou Terreno',
            'STATE_Q3_QUARTOS' => 'um nÃºmero de quartos (ex: "2 quartos", "3q")',
            'STATE_LGPD' => 'Sim ou NÃ£o',
            'STATE_PROPOSTA' => 'uma forma de pagamento: Ã€ vista, Financiamento, Parcelado, ConsÃ³rcio, FGTS, Permuta ou Misto',
            default => 'uma resposta vÃ¡lida',
        };
    }
}

