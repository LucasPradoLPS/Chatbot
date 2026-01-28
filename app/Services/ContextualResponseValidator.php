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
            // Aceita variações comuns e normaliza para valores canônicos.
            'canonical_map' => [
                // Apartamento
                'apartamento' => 'apartamento',
                'apto' => 'apartamento',
                'apart' => 'apartamento',
                'apartment' => 'apartamento',

                // Casa
                'casa' => 'casa',
                'sobrado' => 'casa',

                // Kitnet
                'kitnet' => 'kitnet',
                'kit net' => 'kitnet',
                'kitinete' => 'kitnet',

                // Comercial / Terreno
                'comercial' => 'comercial',
                'loja' => 'comercial',
                'sala comercial' => 'comercial',
                'terreno' => 'terreno',
                'lote' => 'terreno',
            ],
            'valid_options' => ['apartamento', 'apto', 'apart', 'apartment', 'casa', 'sobrado', 'kitnet', 'kit net', 'kitinete', 'comercial', 'loja', 'sala comercial', 'terreno', 'lote'],
            'intent_map' => 'qualificacao_tipo_imovel',
            'slot' => 'tipo_imovel',
        ],
        'STATE_Q3_QUARTOS' => [
            // Aceita tanto texto ("2 quartos", "3q") quanto os botões numéricos ("1", "2", "3", "4").
            'canonical_map' => [
                '1' => '1',
                '1 quarto' => '1',
                '1 quartos' => '1',
                '2' => '2',
                '2 quarto' => '2',
                '2 quartos' => '2',
                '3' => '3',
                '3 quarto' => '3',
                '3 quartos' => '3',
                '3q' => '3',
                '4' => '4+',
                '4+' => '4+',
                '4 ou mais' => '4+',
            ],
            'valid_options' => ['1', '2', '3', '4', '4+', '4 ou mais', '1 quarto', '2 quartos', '3 quartos', '3q'],
            'valid_patterns' => ['/^\s*\d+\s*(quartos?|q)\s*$/i', '/^\s*4\s*(\+|ou\s+mais)\s*$/i'],
            'intent_map' => 'qualificacao_dados',
            'slot' => 'quartos',
        ],
        'STATE_Q4_ORCAMENTO' => [
            // Aceita botões ("1"-"4") e variações do texto de faixa.
            'intent_map' => 'qualificacao_dados',
            // Para orçamento, atualizamos múltiplos slots.
            'slots_map' => [
                '1' => ['faixa_valor_min' => 0, 'faixa_valor_max' => 500000],
                'menos de 500k' => ['faixa_valor_min' => 0, 'faixa_valor_max' => 500000],
                'ate 500k' => ['faixa_valor_min' => 0, 'faixa_valor_max' => 500000],
                'até 500k' => ['faixa_valor_min' => 0, 'faixa_valor_max' => 500000],

                '2' => ['faixa_valor_min' => 500000, 'faixa_valor_max' => 800000],
                '500k-800k' => ['faixa_valor_min' => 500000, 'faixa_valor_max' => 800000],
                '500k - 800k' => ['faixa_valor_min' => 500000, 'faixa_valor_max' => 800000],

                '3' => ['faixa_valor_min' => 800000, 'faixa_valor_max' => 1000000],
                '800k-1m' => ['faixa_valor_min' => 800000, 'faixa_valor_max' => 1000000],
                '800k-1m+' => ['faixa_valor_min' => 800000, 'faixa_valor_max' => 1000000],
                '800k - 1m' => ['faixa_valor_min' => 800000, 'faixa_valor_max' => 1000000],

                '4' => ['faixa_valor_min' => 1000000, 'faixa_valor_max' => 2147483647],
                '1m+' => ['faixa_valor_min' => 1000000, 'faixa_valor_max' => 2147483647],
                '1m +' => ['faixa_valor_min' => 1000000, 'faixa_valor_max' => 2147483647],
                'mais de 1m' => ['faixa_valor_min' => 1000000, 'faixa_valor_max' => 2147483647],
                'acima de 1m' => ['faixa_valor_min' => 1000000, 'faixa_valor_max' => 2147483647],
            ],
        ],

        'STATE_Q5_PRIORIDADES' => [
            // Multi-seleção: aceita "1,2" e também texto (ex.: "garagem e varanda").
            'intent_map' => 'qualificacao_dados',
        ],

        'STATE_Q6_PRAZO' => [
            // Aceita botões (1-4) e texto equivalente.
            'intent_map' => 'qualificacao_dados',
            'slots_map' => [
                '1' => ['prazo_mudanca' => 'Urgente (até 1 mês)'],
                'urgente' => ['prazo_mudanca' => 'Urgente (até 1 mês)'],
                'até 1 mês' => ['prazo_mudanca' => 'Urgente (até 1 mês)'],
                'ate 1 mes' => ['prazo_mudanca' => 'Urgente (até 1 mês)'],
                '1 mês' => ['prazo_mudanca' => 'Urgente (até 1 mês)'],
                '1 mes' => ['prazo_mudanca' => 'Urgente (até 1 mês)'],

                '2' => ['prazo_mudanca' => '1 a 3 meses'],
                '1 a 3 meses' => ['prazo_mudanca' => '1 a 3 meses'],
                '1-3 meses' => ['prazo_mudanca' => '1 a 3 meses'],
                'de 1 a 3 meses' => ['prazo_mudanca' => '1 a 3 meses'],

                '3' => ['prazo_mudanca' => '3 a 6 meses'],
                '3 a 6 meses' => ['prazo_mudanca' => '3 a 6 meses'],
                '3-6 meses' => ['prazo_mudanca' => '3 a 6 meses'],
                'de 3 a 6 meses' => ['prazo_mudanca' => '3 a 6 meses'],

                '4' => ['prazo_mudanca' => 'Sem pressa'],
                'sem pressa' => ['prazo_mudanca' => 'Sem pressa'],
                'sem urgencia' => ['prazo_mudanca' => 'Sem pressa'],
                'sem urgência' => ['prazo_mudanca' => 'Sem pressa'],
            ],
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
        // Normalização leve para inputs com espaços extras
        $respostaNormalizada = preg_replace('/\s+/', ' ', $respostaNormalizada);

        // Validação custom para prioridades (multi-seleção)
        if ($estadoAtual === 'STATE_Q5_PRIORIDADES') {
            $tags = [];

            // Extrai seleções numéricas (ex.: "1", "1,2", "1 2 4")
            if (preg_match_all('/\b([1-8])\b/', $respostaNormalizada, $m)) {
                foreach ($m[1] as $n) {
                    $tags[] = match ($n) {
                        '1' => 'vaga_garagem',
                        '2' => 'varanda',
                        '3' => 'pet_friendly',
                        '4' => 'suite',
                        '5' => 'mobiliado',
                        '6' => 'perto_metro',
                        '7' => 'lavanderia',
                        '8' => 'area_lazer',
                        default => null,
                    };
                }
            }

            // Extrai por palavras-chave
            $kwMap = [
                'vaga' => 'vaga_garagem',
                'garagem' => 'vaga_garagem',
                'varanda' => 'varanda',
                'sacada' => 'varanda',
                'pet' => 'pet_friendly',
                'pet friendly' => 'pet_friendly',
                'suíte' => 'suite',
                'suite' => 'suite',
                'mobiliado' => 'mobiliado',
                'metrô' => 'perto_metro',
                'metro' => 'perto_metro',
                'lavanderia' => 'lavanderia',
                'lazer' => 'area_lazer',
                'área de lazer' => 'area_lazer',
                'area de lazer' => 'area_lazer',
            ];
            foreach ($kwMap as $kw => $tag) {
                if (str_contains($respostaNormalizada, $kw)) {
                    $tags[] = $tag;
                }
            }

            $tags = array_values(array_unique(array_filter($tags)));
            if (!empty($tags)) {
                Log::info('[VALIDACAO] Resposta válida para estado (slots)', [
                    'estado' => $estadoAtual,
                    'resposta' => $mensagem,
                    'opcao_matched' => $tags,
                    'intent' => self::STATE_RESPONSES[$estadoAtual]['intent_map'] ?? null,
                ]);
                return [
                    'is_valid' => true,
                    'intent_sugerida' => self::STATE_RESPONSES[$estadoAtual]['intent_map'] ?? null,
                    'motivo' => "Prioridades reconhecidas",
                    'slots' => [
                        // Mantém compatibilidade com MatchingEngine e LeadCaptureService
                        'tags_prioridades' => $tags,
                        'top_3_prioridades' => array_slice($tags, 0, 3),
                    ],
                ];
            }

            // Se não conseguir interpretar, deixa a IA lidar (não bloqueia o usuário)
            return [
                'is_valid' => null,
                'intent_sugerida' => null,
                'motivo' => 'Prioridades sem validação objetiva (deixar IA interpretar)',
            ];
        }

        if (!isset(self::STATE_RESPONSES[$estadoAtual])) {
            return [
                'is_valid' => null,  
                'intent_sugerida' => null,
                'motivo' => 'Estado sem validaÃ§Ã£o contextual',
            ];
        }
        $validacao = self::STATE_RESPONSES[$estadoAtual];

        // Casos onde o estado atualiza múltiplos slots (ex.: orçamento).
        if (isset($validacao['slots_map']) && is_array($validacao['slots_map'])) {
            foreach ($validacao['slots_map'] as $chave => $slots) {
                $chaveNormalizada = strtolower(trim((string) $chave));
                $chaveNormalizada = preg_replace('/\s+/', ' ', $chaveNormalizada);
                if ($respostaNormalizada === $chaveNormalizada) {
                    Log::info('[VALIDACAO] Resposta vÃ¡lida para estado (slots)', [
                        'estado' => $estadoAtual,
                        'resposta' => $mensagem,
                        'opcao_matched' => $chave,
                        'intent' => $validacao['intent_map'] ?? null,
                    ]);
                    return [
                        'is_valid' => true,
                        'intent_sugerida' => $validacao['intent_map'] ?? null,
                        'motivo' => "Resposta '{$mensagem}' reconhecida como opÃ§Ã£o vÃ¡lida para {$estadoAtual}",
                        'slots' => $slots,
                    ];
                }
            }
        }

        if (isset($validacao['valid_options'])) {
            foreach ($validacao['valid_options'] as $opcao) {
                $opcaoNormalizada = strtolower(trim($opcao));
                $opcaoNormalizada = preg_replace('/\s+/', ' ', $opcaoNormalizada);

                // Para opções puramente numéricas (ex.: "1", "2", "4+"), não usar match por substring.
                $opcaoEhNumerica = (bool) preg_match('/^\d+\+?$/', $opcaoNormalizada);
                $match = false;
                if ($opcaoEhNumerica) {
                    $match = ($respostaNormalizada === $opcaoNormalizada);
                } else {
                    $match = (
                        $respostaNormalizada === $opcaoNormalizada ||
                        strpos($respostaNormalizada, $opcaoNormalizada) === 0 ||
                        strpos($respostaNormalizada, $opcaoNormalizada) !== false
                    );
                }

                if ($match) {
                    $valorSlot = $opcao;
                    if (isset($validacao['canonical_map'])) {
                        $valorSlot = $validacao['canonical_map'][$opcaoNormalizada] ?? $opcao;
                    }
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
                        'valor_slot' => $valorSlot,
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
        if (($validacao['is_valid'] ?? null) === true && isset($validacao['slots']) && is_array($validacao['slots'])) {
            foreach ($validacao['slots'] as $slot => $valorSlot) {
                $valor = $valorSlot;
                if (is_string($valor)) {
                    $valor = ucfirst($valor);
                }
                $slotsAtuais[$slot] = $valor;
                Log::info('[SLOTS] Atualizado por validaÃ§Ã£o contextual', [
                    'slot' => $slot,
                    'valor' => $valor,
                ]);
            }
            return $slotsAtuais;
        }

        if (($validacao['is_valid'] ?? null) === true && isset($validacao['slot']) && isset($validacao['valor_slot'])) {
            $valor = $validacao['valor_slot'];
            if (is_string($valor)) {
                $valor = ucfirst($valor);
            }
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
            'STATE_Q4_ORCAMENTO' => 'uma faixa de valor (ex: "Menos de 500k", "500k-800k", "800k-1M", "1M+") ou o nÃºmero da opÃ§Ã£o (1-4)',
            'STATE_Q5_PRIORIDADES' => 'uma ou mais prioridades (ex.: "1,4" ou "garagem e varanda")',
            'STATE_Q6_PRAZO' => 'o prazo para mudança (1-4 ou texto como "até 1 mês")',
            'STATE_LGPD' => 'Sim ou NÃ£o',
            'STATE_PROPOSTA' => 'uma forma de pagamento: Ã€ vista, Financiamento, Parcelado, ConsÃ³rcio, FGTS, Permuta ou Misto',
            default => 'uma resposta vÃ¡lida',
        };
    }
}

