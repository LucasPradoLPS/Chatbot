<?php
/**
 * Script de teste para Validação Contextual de Respostas
 * Testa se o sistema reconhece opções válidas em cada estado
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ContextualResponseValidator;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  TESTE: Validação Contextual de Respostas\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Casos de teste
$testCases = [
    // STATE_Q2_TIPO - Tipo de Imóvel
    [
        'estado' => 'STATE_Q2_TIPO',
        'mensagem' => 'Casa',
        'esperado' => true,
        'descricao' => 'Resposta "Casa" ao tipo de imóvel'
    ],
    [
        'estado' => 'STATE_Q2_TIPO',
        'mensagem' => 'apartamento',
        'esperado' => true,
        'descricao' => 'Resposta "apartamento" (minúscula) ao tipo de imóvel'
    ],
    [
        'estado' => 'STATE_Q2_TIPO',
        'mensagem' => 'KITNET',
        'esperado' => true,
        'descricao' => 'Resposta "KITNET" (maiúscula) ao tipo de imóvel'
    ],
    [
        'estado' => 'STATE_Q2_TIPO',
        'mensagem' => 'Quero uma casa',
        'esperado' => true,
        'descricao' => 'Resposta "Quero uma casa" contém opção válida'
    ],
    [
        'estado' => 'STATE_Q2_TIPO',
        'mensagem' => 'Não sei',
        'esperado' => false,
        'descricao' => 'Resposta "Não sei" não é opção válida'
    ],

    // STATE_LGPD - Consentimento
    [
        'estado' => 'STATE_LGPD',
        'mensagem' => 'Sim',
        'esperado' => true,
        'descricao' => 'Resposta "Sim" ao LGPD'
    ],
    [
        'estado' => 'STATE_LGPD',
        'mensagem' => 'Não',
        'esperado' => true,
        'descricao' => 'Resposta "Não" ao LGPD'
    ],
    [
        'estado' => 'STATE_LGPD',
        'mensagem' => 'Concordo',
        'esperado' => true,
        'descricao' => 'Resposta "Concordo" ao LGPD'
    ],
    [
        'estado' => 'STATE_LGPD',
        'mensagem' => 'Talvez',
        'esperado' => false,
        'descricao' => 'Resposta "Talvez" não é opção válida'
    ],

    // STATE_PROPOSTA - Forma de Pagamento
    [
        'estado' => 'STATE_PROPOSTA',
        'mensagem' => 'À vista',
        'esperado' => true,
        'descricao' => 'Resposta "À vista" ao pagamento'
    ],
    [
        'estado' => 'STATE_PROPOSTA',
        'mensagem' => 'Financiamento',
        'esperado' => true,
        'descricao' => 'Resposta "Financiamento" ao pagamento'
    ],
    [
        'estado' => 'STATE_PROPOSTA',
        'mensagem' => 'FGTS',
        'esperado' => true,
        'descricao' => 'Resposta "FGTS" ao pagamento'
    ],
    [
        'estado' => 'STATE_PROPOSTA',
        'mensagem' => 'Não sei',
        'esperado' => false,
        'descricao' => 'Resposta "Não sei" não é forma de pagamento'
    ],

    // STATE_Q3_QUARTOS - Número de Quartos
    [
        'estado' => 'STATE_Q3_QUARTOS',
        'mensagem' => '2 quartos',
        'esperado' => true,
        'descricao' => 'Resposta "2 quartos" ao número de quartos'
    ],
    [
        'estado' => 'STATE_Q3_QUARTOS',
        'mensagem' => '3q',
        'esperado' => true,
        'descricao' => 'Resposta "3q" ao número de quartos'
    ],
    [
        'estado' => 'STATE_Q3_QUARTOS',
        'mensagem' => 'não sei',
        'esperado' => false,
        'descricao' => 'Resposta "não sei" não é válida para quartos'
    ],
];

// Executar testes
$totalTestes = count($testCases);
$sucessos = 0;
$falhas = 0;

foreach ($testCases as $index => $teste) {
    $resultado = ContextualResponseValidator::validate($teste['estado'], $teste['mensagem']);
    $ehValida = $resultado['é_válida'] === true;
    $passou = $ehValida === $teste['esperado'];

    $status = $passou ? '✅ PASSOU' : '❌ FALHOU';
    echo "Teste " . ($index + 1) . "/" . $totalTestes . ": {$status}\n";
    echo "  Estado: {$teste['estado']}\n";
    echo "  Mensagem: \"{$teste['mensagem']}\"\n";
    echo "  Descrição: {$teste['descricao']}\n";
    echo "  Esperado: " . ($teste['esperado'] ? 'VÁLIDA' : 'INVÁLIDA') . "\n";
    echo "  Resultado: " . ($ehValida ? 'VÁLIDA' : 'INVÁLIDA') . "\n";
    
    if (!empty($resultado['intent_sugerida'])) {
        echo "  Intent sugerida: {$resultado['intent_sugerida']}\n";
    }
    if (!empty($resultado['slot'])) {
        echo "  Slot atualizado: {$resultado['slot']} = {$resultado['valor_slot']}\n";
    }
    
    echo "  Motivo: {$resultado['motivo']}\n";
    echo "\n";

    if ($passou) {
        $sucessos++;
    } else {
        $falhas++;
    }
}

// Resumo final
echo "═══════════════════════════════════════════════════════════════\n";
echo "RESUMO DOS TESTES:\n";
echo "  Total: {$totalTestes}\n";
echo "  ✅ Sucessos: {$sucessos}\n";
echo "  ❌ Falhas: {$falhas}\n";
echo "  Taxa de sucesso: " . round(($sucessos / $totalTestes) * 100, 1) . "%\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($falhas === 0) {
    echo "🎉 TODOS OS TESTES PASSARAM! A validação contextual está funcionando!\n\n";
    exit(0);
} else {
    echo "⚠️  Alguns testes falharam. Verifique os resultados acima.\n\n";
    exit(1);
}
