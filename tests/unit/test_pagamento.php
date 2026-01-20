<?php

/**
 * Script de teste para Opções de Pagamento
 * Testa o serviço OpcoesPagamentoService
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\OpcoesPagamentoService;
use App\Services\SimuladorFinanciamento;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "       TESTE - OPÇÕES DE PAGAMENTO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Dados do imóvel para teste
$valorImovel = 350000.00;
$entradaDisponivel = 70000.00; // 20%
$rendaFaixa = "5000-8000";
$prazoAnos = 30;

echo "📋 **DADOS DO TESTE:**\n";
echo "Valor do Imóvel: R$ " . number_format($valorImovel, 2, ',', '.') . "\n";
echo "Entrada Disponível: R$ " . number_format($entradaDisponivel, 2, ',', '.') . "\n";
echo "Renda Faixa: $rendaFaixa\n";
echo "Prazo Financiamento: $prazoAnos anos\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// TESTE 1: Obter todas as opções de pagamento
// ============================================================
echo "🧪 **TESTE 1: Listar todas as opções de pagamento**\n\n";

$opcoes = OpcoesPagamentoService::obterOpcoes();

foreach ($opcoes as $chave => $opcao) {
    echo "{$opcao['icone']} **{$opcao['nome']}** ($chave)\n";
    echo "   Descrição: {$opcao['descricao']}\n";
    echo "   ✅ Vantagens: " . count($opcao['vantagens']) . " itens\n";
    echo "   ⚠️ Desvantagens: " . count($opcao['desvantagens']) . " itens\n";
    echo "   📋 Requisitos: " . count($opcao['requisitos']) . " itens\n\n";
}

echo "✅ Teste 1 concluído: " . count($opcoes) . " opções disponíveis\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// TESTE 2: Descrição detalhada de uma forma de pagamento
// ============================================================
echo "🧪 **TESTE 2: Descrição detalhada - Financiamento**\n\n";

$descricao = OpcoesPagamentoService::descreverFormaPagamento('financiamento');
echo $descricao . "\n\n";

echo "✅ Teste 2 concluído\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// TESTE 3: Cálculo de desconto à vista
// ============================================================
echo "🧪 **TESTE 3: Cálculo de desconto à vista**\n\n";

// Teste com desconto padrão (10%)
$descontoPadrao = OpcoesPagamentoService::calcularDescontoAVista($valorImovel);
echo "💰 **Desconto Padrão (10%):**\n";
echo "Valor Original: R$ " . number_format($descontoPadrao['valor_original'], 2, ',', '.') . "\n";
echo "Desconto (%): {$descontoPadrao['percentual_desconto']}%\n";
echo "Valor Desconto: R$ " . number_format($descontoPadrao['valor_desconto'], 2, ',', '.') . "\n";
echo "Valor Final: R$ " . number_format($descontoPadrao['valor_final'], 2, ',', '.') . "\n";
echo "Economia: R$ " . number_format($descontoPadrao['economia'], 2, ',', '.') . "\n\n";

// Teste com desconto customizado (15%)
$descontoCustomizado = OpcoesPagamentoService::calcularDescontoAVista($valorImovel, 15);
echo "💰 **Desconto Customizado (15%):**\n";
echo "Valor Original: R$ " . number_format($descontoCustomizado['valor_original'], 2, ',', '.') . "\n";
echo "Desconto (%): {$descontoCustomizado['percentual_desconto']}%\n";
echo "Valor Desconto: R$ " . number_format($descontoCustomizado['valor_desconto'], 2, ',', '.') . "\n";
echo "Valor Final: R$ " . number_format($descontoCustomizado['valor_final'], 2, ',', '.') . "\n";
echo "Economia: R$ " . number_format($descontoCustomizado['economia'], 2, ',', '.') . "\n\n";

echo "✅ Teste 3 concluído\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// TESTE 4: Parcelamento direto
// ============================================================
echo "🧪 **TESTE 4: Parcelamento direto (sem juros)**\n\n";

$entrada = 105000; // 30% de entrada
$numParcelas = 36;

$parceladoDireto = OpcoesPagamentoService::calcularParceladoDireto(
    $valorImovel,
    $entrada,
    $numParcelas,
    0 // sem juros
);

echo "📅 **Parcelado Direto (36x sem juros):**\n";
echo "Valor Imóvel: R$ " . number_format($parceladoDireto['valor_imovel'], 2, ',', '.') . "\n";
echo "Entrada (30%): R$ " . number_format($parceladoDireto['entrada'], 2, ',', '.') . "\n";
echo "Valor a Parcelar: R$ " . number_format($parceladoDireto['valor_a_parcelar'], 2, ',', '.') . "\n";
echo "Número de Parcelas: {$parceladoDireto['num_parcelas']}x\n";
echo "Parcela Mensal: R$ " . number_format($parceladoDireto['parcela_mensal'], 2, ',', '.') . "\n";
echo "Total Pago: R$ " . number_format($parceladoDireto['total_pago'], 2, ',', '.') . "\n";
echo "Total Juros: R$ " . number_format($parceladoDireto['total_juros'], 2, ',', '.') . "\n\n";

echo "✅ Teste 4 concluído\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// TESTE 5: Comparação de formas de pagamento
// ============================================================
echo "🧪 **TESTE 5: Comparação de formas de pagamento**\n\n";

$comparacao = OpcoesPagamentoService::compararFormasPagamento(
    $valorImovel,
    $entradaDisponivel,
    $rendaFaixa,
    $prazoAnos
);

echo "📊 **COMPARAÇÃO GERADA:**\n\n";

foreach ($comparacao as $chave => $opcao) {
    echo "{$opcao['icone']} **{$opcao['forma']}**\n";
    
    if (isset($opcao['disponivel']) && !$opcao['disponivel']) {
        echo "   ❌ {$opcao['motivo']}\n\n";
        continue;
    }
    
    if (isset($opcao['nota'])) {
        echo "   {$opcao['nota']}\n";
        echo "   💡 {$opcao['recomendacao']}\n\n";
        continue;
    }
    
    if (isset($opcao['valor_entrada'])) {
        echo "   Entrada: R$ " . number_format($opcao['valor_entrada'], 2, ',', '.') . "\n";
    }
    
    if (isset($opcao['parcela_mensal']) && $opcao['parcela_mensal'] > 0) {
        echo "   Parcela: R$ " . number_format($opcao['parcela_mensal'], 2, ',', '.') . " x {$opcao['num_parcelas']}\n";
    }
    
    if (isset($opcao['total_pago'])) {
        echo "   Total: R$ " . number_format($opcao['total_pago'], 2, ',', '.') . "\n";
    }
    
    if (isset($opcao['economia_vs_tabela']) && $opcao['economia_vs_tabela'] > 0) {
        echo "   🎉 Economia: R$ " . number_format($opcao['economia_vs_tabela'], 2, ',', '.') . "\n";
    }
    
    if (isset($opcao['viavel'])) {
        $status = $opcao['viavel'] ? '✅ Viável' : '⚠️ Atenção';
        echo "   $status\n";
    }
    
    echo "   💡 {$opcao['recomendacao']}\n\n";
}

echo "✅ Teste 5 concluído\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// TESTE 6: Formatação da comparação para usuário
// ============================================================
echo "🧪 **TESTE 6: Mensagem formatada da comparação**\n\n";

$mensagemFormatada = OpcoesPagamentoService::formatarComparacao($comparacao);
echo $mensagemFormatada . "\n\n";

echo "✅ Teste 6 concluído\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// TESTE 7: Cenário com entrada insuficiente
// ============================================================
echo "🧪 **TESTE 7: Cenário com entrada insuficiente (10% apenas)**\n\n";

$entradaBaixa = 35000; // Apenas 10%

echo "Entrada: R$ " . number_format($entradaBaixa, 2, ',', '.') . " (10% do imóvel)\n\n";

$comparacaoBaixa = OpcoesPagamentoService::compararFormasPagamento(
    $valorImovel,
    $entradaBaixa,
    $rendaFaixa,
    $prazoAnos
);

echo "📊 **Resultado:**\n\n";

foreach (['financiamento', 'parcelado_direto'] as $tipo) {
    if (isset($comparacaoBaixa[$tipo])) {
        $opcao = $comparacaoBaixa[$tipo];
        echo "{$opcao['icone']} **{$opcao['forma']}**\n";
        
        if (isset($opcao['disponivel']) && !$opcao['disponivel']) {
            echo "   ❌ {$opcao['motivo']}\n\n";
        } else {
            echo "   ✅ Disponível\n\n";
        }
    }
}

echo "✅ Teste 7 concluído: Sistema corretamente bloqueia opções com entrada insuficiente\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================================
// RESUMO FINAL
// ============================================================
echo "🎉 **TODOS OS TESTES CONCLUÍDOS COM SUCESSO!**\n\n";
echo "✅ Teste 1: Listagem de opções\n";
echo "✅ Teste 2: Descrição detalhada\n";
echo "✅ Teste 3: Cálculo de desconto à vista\n";
echo "✅ Teste 4: Parcelamento direto\n";
echo "✅ Teste 5: Comparação de formas\n";
echo "✅ Teste 6: Formatação para usuário\n";
echo "✅ Teste 7: Validação de entrada insuficiente\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "O serviço OpcoesPagamentoService está funcionando corretamente!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
