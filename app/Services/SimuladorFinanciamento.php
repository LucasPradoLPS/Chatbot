<?php

namespace App\Services;

/**
 * Simulador de Financiamento Imobiliário
 * Cálculo de parcelas, taxas e recomendações
 */
class SimuladorFinanciamento
{
    /**
     * Taxa média de juros (anual) - usar como padrão
     * Atualmente: 7.5% ao ano (ajustável conforme mercado)
     */
    private const TAXA_JUROS_ANUAL = 7.5;

    /**
     * Realizar simulação de financiamento
     *
     * @param float $valorImovel Valor do imóvel em reais
     * @param float $entradaDisponivel Entrada que o cliente pode dar
     * @param string $rendaFaixa Faixa de renda aproximada (ex: "3000-5000", "5000-10000")
     * @param int $prazoAnos Prazo do financiamento (20, 30, 35 anos)
     * @return array Resultado da simulação
     */
    public static function simular(
        float $valorImovel,
        float $entradaDisponivel,
        string $rendaFaixa,
        int $prazoAnos
    ): array {
        // Validações básicas
        if ($valorImovel <= 0) {
            return [
                'sucesso' => false,
                'erro' => 'Valor do imóvel deve ser maior que zero.',
            ];
        }

        if ($entradaDisponivel < 0) {
            return [
                'sucesso' => false,
                'erro' => 'Entrada não pode ser negativa.',
            ];
        }

        if ($entradaDisponivel > $valorImovel) {
            return [
                'sucesso' => false,
                'erro' => 'Entrada não pode ser maior que o valor do imóvel.',
            ];
        }

        if (!in_array($prazoAnos, [20, 30, 35])) {
            return [
                'sucesso' => false,
                'erro' => 'Prazo deve ser 20, 30 ou 35 anos.',
            ];
        }

        // Cálculos
        $percentualEntrada = ($entradaDisponivel / $valorImovel) * 100;
        $valorFinanciado = $valorImovel - $entradaDisponivel;
        
        // Calcular parcela usando fórmula de amortização (Price)
        $parcelaMensal = self::calcularParcelaMensal($valorFinanciado, self::TAXA_JUROS_ANUAL, $prazoAnos);
        
        // Extrair faixa de renda
        $rendaMedia = self::extrairRendaMedia($rendaFaixa);
        $percentualRenda = $rendaMedia > 0 ? ($parcelaMensal / $rendaMedia) * 100 : 0;
        
        // Viabilidade (parcela não deve exceder 30% da renda)
        $viavel = $percentualRenda <= 30;
        
        // Recomendações
        $recomendacoes = self::gerarRecomendacoes(
            $percentualEntrada,
            $viavel,
            $percentualRenda,
            $entradaDisponivel,
            $valorImovel
        );

        // Estimativa de custos adicionais (aproximado)
        $tarifas = $valorFinanciado * 0.01; // 1% de tarifas/custos administrativos
        $totalPrimeiraMes = $parcelaMensal + $tarifas;

        return [
            'sucesso' => true,
            'imovel' => [
                'valor' => $valorImovel,
                'entrada_dada' => $entradaDisponivel,
                'entrada_percentual' => round($percentualEntrada, 1),
            ],
            'financiamento' => [
                'valor_financiado' => $valorFinanciado,
                'taxa_juros_anual' => self::TAXA_JUROS_ANUAL,
                'prazo_anos' => $prazoAnos,
                'prazo_meses' => $prazoAnos * 12,
            ],
            'parcela' => [
                'valor_mensal' => round($parcelaMensal, 2),
                'com_tarifas_aproximadas' => round($totalPrimeiraMes, 2),
            ],
            'renda' => [
                'faixa_informada' => $rendaFaixa,
                'renda_media_estimada' => round($rendaMedia, 2),
                'percentual_parcela' => round($percentualRenda, 1),
                'viavel' => $viavel,
            ],
            'recomendacoes' => $recomendacoes,
        ];
    }

    /**
     * Calcular parcela mensal usando fórmula Price (amortização)
     *
     * @param float $principal Valor do financiamento
     * @param float $taxaAnual Taxa de juros anual (%)
     * @param int $prazoAnos Prazo em anos
     * @return float Parcela mensal
     */
    private static function calcularParcelaMensal(float $principal, float $taxaAnual, int $prazoAnos): float
    {
        if ($principal == 0) {
            return 0;
        }

        // Converter taxa anual para mensal
        $taxaMensal = $taxaAnual / 100 / 12;
        $numParcelas = $prazoAnos * 12;

        // Fórmula Price: P = V * [i(1+i)^n] / [(1+i)^n - 1]
        if ($taxaMensal == 0) {
            return $principal / $numParcelas;
        }

        $numerador = $taxaMensal * pow(1 + $taxaMensal, $numParcelas);
        $denominador = pow(1 + $taxaMensal, $numParcelas) - 1;

        return $principal * ($numerador / $denominador);
    }

    /**
     * Extrair renda média de uma faixa (ex: "3000-5000" → 4000)
     *
     * @param string $rendaFaixa Faixa (ex: "3000-5000", "5000+")
     * @return float Renda média ou 0 se inválida
     */
    private static function extrairRendaMedia(string $rendaFaixa): float
    {
        // Remover espaços
        $rendaFaixa = trim($rendaFaixa);

        // Se contiver "-", pegar média
        if (strpos($rendaFaixa, '-') !== false) {
            $partes = explode('-', $rendaFaixa);
            $min = (float)preg_replace('/\D/', '', $partes[0] ?? '0');
            $max = (float)preg_replace('/\D/', '', $partes[1] ?? '0');
            if ($min > 0 && $max > 0) {
                return ($min + $max) / 2;
            }
        }

        // Se contiver "+", pegar apenas o número
        if (strpos($rendaFaixa, '+') !== false) {
            $valor = (float)preg_replace('/\D/', '', $rendaFaixa);
            return $valor > 0 ? $valor : 0;
        }

        // Tentar extrair um número direto
        $valor = (float)preg_replace('/\D/', '', $rendaFaixa);
        return $valor > 0 ? $valor : 0;
    }

    /**
     * Gerar recomendações personalizadas baseadas na simulação
     *
     * @param float $percentualEntrada Percentual de entrada
     * @param bool $viavel Se o financiamento é viável
     * @param float $percentualRenda Percentual da renda gasto com parcela
     * @param float $entradaDada Entrada dada
     * @param float $valorImovel Valor do imóvel
     * @return array Lista de recomendações
     */
    private static function gerarRecomendacoes(
        float $percentualEntrada,
        bool $viavel,
        float $percentualRenda,
        float $entradaDada,
        float $valorImovel
    ): array {
        $recomendacoes = [];

        // Entrada baixa
        if ($percentualEntrada < 20) {
            $entradaNecessaria = $valorImovel * 0.20 - $entradaDada;
            $recomendacoes[] = [
                'tipo' => 'alerta',
                'titulo' => 'Entrada Baixa',
                'mensagem' => sprintf(
                    'Sua entrada é de %.1f%%. Aumentar para 20%% (R$ %s a mais) reduz significativamente os juros e a parcela.',
                    $percentualEntrada,
                    number_format($entradaNecessaria, 2, ',', '.')
                ),
            ];
        }

        // Entrada adequada
        if ($percentualEntrada >= 20 && $percentualEntrada < 30) {
            $recomendacoes[] = [
                'tipo' => 'positivo',
                'titulo' => 'Entrada Adequada',
                'mensagem' => 'Sua entrada está boa! Mas se conseguir aumentar para 30%, reduz ainda mais a parcela.',
            ];
        }

        // Entrada excelente
        if ($percentualEntrada >= 30) {
            $recomendacoes[] = [
                'tipo' => 'positivo',
                'titulo' => 'Excelente Entrada',
                'mensagem' => sprintf(
                    'Você está dando uma entrada de %.1f%%! Isso reduz muito os juros e deixa a parcela bem menor.',
                    $percentualEntrada
                ),
            ];
        }

        // Viabilidade
        if (!$viavel) {
            $recomendacoes[] = [
                'tipo' => 'alerta',
                'titulo' => 'Parcela Alta em Relação à Renda',
                'mensagem' => sprintf(
                    'A parcela representa %.1f%% da sua renda. Bancos preferem no máximo 30%%. Tente aumentar a entrada ou escolher um imóvel mais barato.',
                    $percentualRenda
                ),
            ];
        } else {
            $recomendacoes[] = [
                'tipo' => 'positivo',
                'titulo' => 'Parcela Viável',
                'mensagem' => sprintf(
                    'A parcela é %.1f%% da sua renda — dentro do limite bancário de 30%%. ✅',
                    $percentualRenda
                ),
            ];
        }

        // Prazo
        if ($percentualRenda > 25) {
            $recomendacoes[] = [
                'tipo' => 'info',
                'titulo' => 'Prazo Maior',
                'mensagem' => 'Se aumentar o prazo para 35 anos, a parcela mensal fica menor (mas pagará mais juros).',
            ];
        }

        return $recomendacoes;
    }

    /**
     * Formatar resultado da simulação para exibir ao usuário
     *
     * @param array $resultado Resultado da simulação
     * @return string Mensagem formatada
     */
    public static function formatarResultado(array $resultado): string
    {
        if (!$resultado['sucesso']) {
            return '❌ *Simulação não realizada*' . "\n" . $resultado['erro'];
        }

        $imovel = $resultado['imovel'];
        $financiamento = $resultado['financiamento'];
        $parcela = $resultado['parcela'];
        $renda = $resultado['renda'];
        $recomendacoes = $resultado['recomendacoes'];

        $mensagem = "📊 *SIMULAÇÃO DE FINANCIAMENTO*\n";
        $mensagem .= "━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Dados de entrada
        $mensagem .= "💰 **Imóvel:**\n";
        $mensagem .= "Valor: R$ " . number_format($imovel['valor'], 2, ',', '.') . "\n";
        $mensagem .= "Entrada: R$ " . number_format($imovel['entrada_dada'], 2, ',', '.') . " (" . $imovel['entrada_percentual'] . "%)\n\n";

        // Financiamento
        $mensagem .= "🏦 **Financiamento:**\n";
        $mensagem .= "Valor a financiar: R$ " . number_format($financiamento['valor_financiado'], 2, ',', '.') . "\n";
        $mensagem .= "Taxa de juros: " . $financiamento['taxa_juros_anual'] . "% ao ano\n";
        $mensagem .= "Prazo: " . $financiamento['prazo_anos'] . " anos (" . $financiamento['prazo_meses'] . " meses)\n\n";

        // Resultado principal
        $mensagem .= "📋 **RESULTADO:**\n";
        $mensagem .= "Parcela mensal: R$ " . number_format($parcela['valor_mensal'], 2, ',', '.') . "\n";
        $mensagem .= "Com tarifas aprox.: R$ " . number_format($parcela['com_tarifas_aproximadas'], 2, ',', '.') . "\n\n";

        // Viabilidade
        $statusRenda = $renda['viavel'] ? '✅ Viável' : '⚠️ Acima do limite';
        $mensagem .= "💵 **Renda:**\n";
        $mensagem .= "Sua renda est.: R$ " . number_format($renda['renda_media_estimada'], 2, ',', '.') . "\n";
        $mensagem .= "Parcela / Renda: " . $renda['percentual_parcela'] . "% ($statusRenda)\n\n";

        // Recomendações
        $mensagem .= "💡 **RECOMENDAÇÕES:**\n";
        foreach ($recomendacoes as $rec) {
            $icon = match($rec['tipo']) {
                'positivo' => '✅',
                'alerta' => '⚠️',
                'info' => 'ℹ️',
                default => '•',
            };
            $mensagem .= "$icon " . $rec['titulo'] . "\n";
            $mensagem .= "   " . $rec['mensagem'] . "\n\n";
        }

        return $mensagem;
    }
}
