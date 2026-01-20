<?php
namespace App\Services;

class OpcoesPagamentoService
{
    public const FORMAS_PAGAMENTO = [
        'a_vista' => 'Ã€ Vista',
        'financiamento' => 'Financiamento BancÃ¡rio',
        'parcelado_direto' => 'Parcelado Direto com Construtora/ProprietÃ¡rio',
        'consorcio' => 'ConsÃ³rcio',
        'fgts' => 'FGTS',
        'permuta' => 'Permuta (Troca de ImÃ³vel)',
        'misto' => 'Misto (Entrada + Financiamento ou FGTS + Financiamento)',
    ];
    private const DESCONTO_A_VISTA_MIN = 5.0;  
    private const DESCONTO_A_VISTA_MAX = 15.0; 
    private const DESCONTO_A_VISTA_PADRAO = 10.0; 
    public static function obterOpcoes(): array
    {
        return [
            'a_vista' => [
                'nome' => 'Ã€ Vista',
                'icone' => 'ðŸ’°',
                'descricao' => 'Pagamento integral em dinheiro',
                'vantagens' => [
                    'Desconto significativo (geralmente 5% a 15%)',
                    'Sem juros',
                    'NegociaÃ§Ã£o mais forte',
                    'Processo mais rÃ¡pido',
                ],
                'desvantagens' => [
                    'Requer capital total disponÃ­vel',
                    'Reduz liquidez imediata',
                ],
                'requisitos' => [
                    'Ter o valor total disponÃ­vel',
                ],
            ],
            'financiamento' => [
                'nome' => 'Financiamento BancÃ¡rio',
                'icone' => 'ðŸ¦',
                'descricao' => 'Financiamento com bancos (Caixa, BB, ItaÃº, Santander, etc.)',
                'vantagens' => [
                    'NÃ£o precisa ter valor total',
                    'Prazo longo (atÃ© 35 anos)',
                    'Usa FGTS para abater entrada ou parcelas',
                    'Taxas competitivas',
                ],
                'desvantagens' => [
                    'AnÃ¡lise de crÃ©dito necessÃ¡ria',
                    'Juros ao longo do tempo',
                    'Entrada mÃ­nima (geralmente 20%)',
                    'Burocracia e documentaÃ§Ã£o',
                ],
                'requisitos' => [
                    'Ter renda comprovada',
                    'Entrada de 20% a 30%',
                    'AprovaÃ§Ã£o de crÃ©dito',
                ],
            ],
            'parcelado_direto' => [
                'nome' => 'Parcelado Direto',
                'icone' => 'ðŸ“…',
                'descricao' => 'Parcelamento direto com construtora ou proprietÃ¡rio',
                'vantagens' => [
                    'Sem anÃ¡lise bancÃ¡ria',
                    'Mais flexÃ­vel',
                    'Sem juros ou juros menores',
                    'Ideal para imÃ³veis na planta',
                ],
                'desvantagens' => [
                    'Entrada maior (30% a 50%)',
                    'Prazo menor que financiamento',
                    'Parcelas maiores',
                ],
                'requisitos' => [
                    'Entrada substancial',
                    'Acordo direto com vendedor/construtora',
                ],
            ],
            'consorcio' => [
                'nome' => 'ConsÃ³rcio',
                'icone' => 'ðŸŽ²',
                'descricao' => 'Grupo de consÃ³rcio imobiliÃ¡rio',
                'vantagens' => [
                    'Sem juros (apenas taxa administrativa)',
                    'FlexÃ­vel',
                    'Pode usar lance para antecipar',
                ],
                'desvantagens' => [
                    'Depende de sorteio ou lance',
                    'Pode demorar anos para ser contemplado',
                    'NÃ£o compra imÃ³vel imediatamente',
                ],
                'requisitos' => [
                    'PaciÃªncia para aguardar contemplaÃ§Ã£o',
                    'Capital para lances (opcional)',
                ],
            ],
            'fgts' => [
                'nome' => 'FGTS',
                'icone' => 'ðŸ“',
                'descricao' => 'Uso do FGTS para entrada e/ou amortizaÃ§Ã£o',
                'vantagens' => [
                    'Usa recurso jÃ¡ disponÃ­vel',
                    'Reduz entrada necessÃ¡ria',
                    'Pode abater parcelas mensais',
                ],
                'desvantagens' => [
                    'Limitado ao saldo disponÃ­vel',
                    'Regras especÃ­ficas da Caixa',
                    'NÃ£o pode ser Ãºnico recurso',
                ],
                'requisitos' => [
                    'Ter FGTS disponÃ­vel',
                    'ImÃ³vel residencial',
                    'NÃ£o ter outro financiamento ativo',
                ],
            ],
            'permuta' => [
                'nome' => 'Permuta',
                'icone' => 'ðŸ”„',
                'descricao' => 'Troca de imÃ³vel como parte ou totalidade do pagamento',
                'vantagens' => [
                    'NÃ£o precisa vender antes',
                    'NegociaÃ§Ã£o direta',
                    'Pode facilitar upgrade',
                ],
                'desvantagens' => [
                    'AvaliaÃ§Ã£o pode diferir da expectativa',
                    'Dependente de interesse mÃºtuo',
                    'Pode precisar complementar valor',
                ],
                'requisitos' => [
                    'Ter imÃ³vel para trocar',
                    'Acordo sobre valores',
                ],
            ],
            'misto' => [
                'nome' => 'Misto',
                'icone' => 'ðŸ”€',
                'descricao' => 'CombinaÃ§Ã£o de entrada/FGTS + financiamento',
                'vantagens' => [
                    'Mais flexÃ­vel',
                    'Reduz valor financiado',
                    'Parcelas menores',
                    'Aproveita melhor recursos disponÃ­veis',
                ],
                'desvantagens' => [
                    'Requer planejamento detalhado',
                    'Pode combinar requisitos mÃºltiplos',
                ],
                'requisitos' => [
                    'Entrada parcial',
                    'AprovaÃ§Ã£o de financiamento',
                ],
            ],
        ];
    }
    public static function calcularDescontoAVista(float $valorImovel, ?float $percentualDesconto = null): array
    {
        $percentual = $percentualDesconto ?? self::DESCONTO_A_VISTA_PADRAO;
        if ($percentual < 0 || $percentual > 30) {
            $percentual = self::DESCONTO_A_VISTA_PADRAO;
        }
        $desconto = $valorImovel * ($percentual / 100);
        $valorFinal = $valorImovel - $desconto;
        return [
            'valor_original' => $valorImovel,
            'percentual_desconto' => $percentual,
            'valor_desconto' => $desconto,
            'valor_final' => $valorFinal,
            'economia' => $desconto,
        ];
    }
    public static function calcularParceladoDireto(
        float $valorImovel,
        float $entrada,
        int $numParcelas,
        float $taxaJurosMensal = 0.0
    ): array {
        $valorRestante = $valorImovel - $entrada;
        if ($taxaJurosMensal == 0) {
            $parcelaMensal = $valorRestante / $numParcelas;
            $totalPago = $valorImovel;
        } else {
            $taxaDecimal = $taxaJurosMensal / 100;
            $montante = $valorRestante * pow(1 + $taxaDecimal, $numParcelas);
            $parcelaMensal = $montante / $numParcelas;
            $totalPago = $entrada + ($parcelaMensal * $numParcelas);
        }
        return [
            'valor_imovel' => $valorImovel,
            'entrada' => $entrada,
            'valor_a_parcelar' => $valorRestante,
            'num_parcelas' => $numParcelas,
            'taxa_juros_mensal' => $taxaJurosMensal,
            'parcela_mensal' => $parcelaMensal,
            'total_pago' => $totalPago,
            'total_juros' => $totalPago - $valorImovel,
        ];
    }
    public static function compararFormasPagamento(
        float $valorImovel,
        float $entradaDisponivel,
        string $rendaFaixa,
        int $prazoFinanciamentoAnos = 30
    ): array {
        $comparacao = [];
        $aVista = self::calcularDescontoAVista($valorImovel);
        $comparacao['a_vista'] = [
            'forma' => 'Ã€ Vista',
            'icone' => 'ðŸ’°',
            'valor_final' => $aVista['valor_final'],
            'valor_entrada' => $aVista['valor_final'], 
            'parcela_mensal' => 0,
            'num_parcelas' => 0,
            'total_pago' => $aVista['valor_final'],
            'economia_vs_tabela' => $aVista['economia'],
            'recomendacao' => 'Melhor opÃ§Ã£o se vocÃª tem todo o dinheiro disponÃ­vel.',
        ];
        if ($entradaDisponivel >= $valorImovel * 0.20) {
            $resultadoFinanciamento = SimuladorFinanciamento::simular(
                $valorImovel,
                $entradaDisponivel,
                $rendaFaixa,
                $prazoFinanciamentoAnos
            );
            if ($resultadoFinanciamento['sucesso']) {
                $parcela = $resultadoFinanciamento['parcela']['valor_mensal'];
                $totalParcelas = $parcela * ($prazoFinanciamentoAnos * 12);
                $totalPago = $entradaDisponivel + $totalParcelas;
                $comparacao['financiamento'] = [
                    'forma' => 'Financiamento',
                    'icone' => 'ðŸ¦',
                    'valor_final' => $valorImovel,
                    'valor_entrada' => $entradaDisponivel,
                    'parcela_mensal' => $parcela,
                    'num_parcelas' => $prazoFinanciamentoAnos * 12,
                    'total_pago' => $totalPago,
                    'economia_vs_tabela' => 0,
                    'viavel' => $resultadoFinanciamento['renda']['viavel'],
                    'recomendacao' => $resultadoFinanciamento['renda']['viavel']
                        ? 'Parcela cabe no orÃ§amento. Boa opÃ§Ã£o se nÃ£o tem todo valor.'
                        : 'Parcela acima de 30% da renda. Considere aumentar entrada.',
                ];
            }
        } else {
            $comparacao['financiamento'] = [
                'forma' => 'Financiamento',
                'icone' => 'ðŸ¦',
                'disponivel' => false,
                'motivo' => 'Entrada mÃ­nima de 20% necessÃ¡ria (R$ ' . number_format($valorImovel * 0.20, 2, ',', '.') . ')',
            ];
        }
        if ($entradaDisponivel >= $valorImovel * 0.30) {
            $parceladoDireto = self::calcularParceladoDireto($valorImovel, $entradaDisponivel, 36, 0);
            $comparacao['parcelado_direto'] = [
                'forma' => 'Parcelado Direto',
                'icone' => 'ðŸ“…',
                'valor_final' => $valorImovel,
                'valor_entrada' => $entradaDisponivel,
                'parcela_mensal' => $parceladoDireto['parcela_mensal'],
                'num_parcelas' => 36,
                'total_pago' => $parceladoDireto['total_pago'],
                'economia_vs_tabela' => 0,
                'recomendacao' => 'Sem juros! Bom se conseguir entrada de 30%+.',
            ];
        } else {
            $comparacao['parcelado_direto'] = [
                'forma' => 'Parcelado Direto',
                'icone' => 'ðŸ“…',
                'disponivel' => false,
                'motivo' => 'Entrada mÃ­nima de 30% necessÃ¡ria (R$ ' . number_format($valorImovel * 0.30, 2, ',', '.') . ')',
            ];
        }
        $comparacao['misto'] = [
            'forma' => 'Misto (FGTS + Financiamento)',
            'icone' => 'ðŸ”€',
            'recomendacao' => 'Combine FGTS com entrada e financie o restante. Reduz parcelas!',
            'nota' => 'Solicite simulaÃ§Ã£o personalizada com nosso especialista.',
        ];
        return $comparacao;
    }
    public static function formatarComparacao(array $comparacao): string
    {
        $mensagem = "ðŸ’³ *COMPARAÃ‡ÃƒO DE FORMAS DE PAGAMENTO*\n";
        $mensagem .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n\n";
        foreach ($comparacao as $opcao) {
            $icone = $opcao['icone'] ?? 'â€¢';
            $forma = $opcao['forma'];
            $mensagem .= "$icone **$forma**\n";
            if (isset($opcao['disponivel']) && !$opcao['disponivel']) {
                $mensagem .= "   âŒ {$opcao['motivo']}\n\n";
                continue;
            }
            if (isset($opcao['nota'])) {
                $mensagem .= "   {$opcao['nota']}\n";
                $mensagem .= "   ðŸ’¡ {$opcao['recomendacao']}\n\n";
                continue;
            }
            if (isset($opcao['valor_entrada']) && $opcao['valor_entrada'] > 0) {
                $mensagem .= "   Entrada: R$ " . number_format($opcao['valor_entrada'], 2, ',', '.') . "\n";
            }
            if (isset($opcao['parcela_mensal']) && $opcao['parcela_mensal'] > 0) {
                $mensagem .= "   Parcela: R$ " . number_format($opcao['parcela_mensal'], 2, ',', '.') . " x " . $opcao['num_parcelas'] . "\n";
            }
            if (isset($opcao['total_pago'])) {
                $mensagem .= "   Total: R$ " . number_format($opcao['total_pago'], 2, ',', '.') . "\n";
            }
            if (isset($opcao['economia_vs_tabela']) && $opcao['economia_vs_tabela'] > 0) {
                $mensagem .= "   ðŸŽ‰ Economia: R$ " . number_format($opcao['economia_vs_tabela'], 2, ',', '.') . "\n";
            }
            if (isset($opcao['viavel'])) {
                $status = $opcao['viavel'] ? 'âœ… ViÃ¡vel' : 'âš ï¸ AtenÃ§Ã£o';
                $mensagem .= "   $status\n";
            }
            $mensagem .= "   ðŸ’¡ {$opcao['recomendacao']}\n\n";
        }
        $mensagem .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n";
        $mensagem .= "Qual forma de pagamento te interessa mais?\n";
        return $mensagem;
    }
    public static function descreverFormaPagamento(string $formaPagamento): string
    {
        $opcoes = self::obterOpcoes();
        if (!isset($opcoes[$formaPagamento])) {
            return "Forma de pagamento nÃ£o encontrada.";
        }
        $opcao = $opcoes[$formaPagamento];
        $mensagem = "{$opcao['icone']} **{$opcao['nome']}**\n";
        $mensagem .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n\n";
        $mensagem .= "*DescriÃ§Ã£o:*\n{$opcao['descricao']}\n\n";
        $mensagem .= "âœ… *Vantagens:*\n";
        foreach ($opcao['vantagens'] as $vantagem) {
            $mensagem .= "â€¢ $vantagem\n";
        }
        $mensagem .= "\n";
        $mensagem .= "âš ï¸ *Desvantagens:*\n";
        foreach ($opcao['desvantagens'] as $desvantagem) {
            $mensagem .= "â€¢ $desvantagem\n";
        }
        $mensagem .= "\n";
        $mensagem .= "ðŸ“‹ *Requisitos:*\n";
        foreach ($opcao['requisitos'] as $requisito) {
            $mensagem .= "â€¢ $requisito\n";
        }
        return $mensagem;
    }
}

