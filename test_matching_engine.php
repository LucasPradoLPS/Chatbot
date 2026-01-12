<?php

// Arquivo de teste: test_matching_engine.php
// Executar: php test_matching_engine.php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Services\MatchingEngine;

echo "═══════════════════════════════════════════════════════\n";
echo "TESTE: MatchingEngine - Lógica de Recomendação\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Simulação de slots do usuário
$slotsUsuario = [
    'nome' => 'João Silva',
    'bairro_regiao' => ['Vila Mariana', 'Pinheiros', 'Vila Madalena'],
    'faixa_valor_max' => 500000,
    'quartos' => 2,
    'vagas' => 1,
    'tags_prioridades' => ['pet_friendly', 'varanda'],
];

echo "👤 PERFIL DO USUÁRIO:\n";
echo "   Nome: " . $slotsUsuario['nome'] . "\n";
echo "   Bairros: " . implode(', ', $slotsUsuario['bairro_regiao']) . "\n";
echo "   Orçamento: R$ " . number_format($slotsUsuario['faixa_valor_max'], 0, '.', '.') . "\n";
echo "   Quartos: " . $slotsUsuario['quartos'] . "\n";
echo "   Vagas: " . $slotsUsuario['vagas'] . "\n";
echo "   Prioridades: " . implode(', ', $slotsUsuario['tags_prioridades']) . "\n\n";

// Catálogo de imóveis de exemplo
$imoveis = [
    [
        'id' => 1,
        'titulo' => 'Apt. 2 quartos em Vila Mariana',
        'bairro' => 'Vila Mariana',
        'valor' => 480000,
        'quartos' => 2,
        'vagas' => 1,
        'tags' => ['pet_friendly', 'varanda'],
    ],
    [
        'id' => 2,
        'titulo' => 'Apt. 3 quartos em Vila Mariana',
        'bairro' => 'Vila Mariana',
        'valor' => 580000,
        'quartos' => 3,
        'vagas' => 2,
        'tags' => ['suíte', 'varanda', 'piscina'],
    ],
    [
        'id' => 3,
        'titulo' => 'Apt. 2 quartos em Pinheiros',
        'bairro' => 'Pinheiros',
        'valor' => 520000,
        'quartos' => 2,
        'vagas' => 1,
        'tags' => ['pet_friendly'],
    ],
    [
        'id' => 4,
        'titulo' => 'Apt. 4 quartos em Imirim',
        'bairro' => 'Imirim',
        'valor' => 420000,
        'quartos' => 4,
        'vagas' => 1,
        'tags' => ['suíte', 'quintal'],
    ],
    [
        'id' => 5,
        'titulo' => 'Apt. 2 quartos em Morumbi',
        'bairro' => 'Morumbi',
        'valor' => 650000,
        'quartos' => 2,
        'vagas' => 2,
        'tags' => ['suíte', 'piscina', 'pet_friendly'],
    ],
    [
        'id' => 6,
        'titulo' => 'Apt. 2 quartos em Vila Madalena',
        'bairro' => 'Vila Madalena',
        'valor' => 460000,
        'quartos' => 2,
        'vagas' => 1,
        'tags' => ['pet_friendly', 'varanda', 'ar_condicionado'],
    ],
];

echo "═══════════════════════════════════════════════════════\n";
echo "ANÁLISE INDIVIDUAL DE SCORES\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Calcular score para cada imóvel
$imoveisComScore = [];
foreach ($imoveis as $imovel) {
    $scoreDetalhes = MatchingEngine::calculateScore($imovel, $slotsUsuario);
    $imovel['score_detalhes'] = $scoreDetalhes;
    $imoveisComScore[] = $imovel;

    echo "🏠 " . $imovel['titulo'] . "\n";
    echo "   Bairro: " . $imovel['bairro'] . "\n";
    echo "   Valor: R$ " . number_format($imovel['valor'], 0, '.', '.') . "\n";
    echo "   Quartos: " . $imovel['quartos'] . " | Vagas: " . $imovel['vagas'] . "\n";
    echo "   Tags: " . implode(', ', $imovel['tags']) . "\n";
    echo "   SCORE: " . $scoreDetalhes['score'] . " pontos\n";
    foreach ($scoreDetalhes['detalhes'] as $detalhe) {
        echo "      " . $detalhe . "\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════\n";
echo "CATEGORIZAÇÃO E FILTROS\n";
echo "═══════════════════════════════════════════════════════\n\n";

$categorizado = MatchingEngine::categorizeResults($imoveisComScore, maxExatos: 5, maxQuaseLa: 2);

echo "✅ EXATOS (Score >= 70):\n";
if (!empty($categorizado['exatos'])) {
    foreach ($categorizado['exatos'] as $i => $imovel) {
        echo "   " . ($i + 1) . ". " . $imovel['titulo'] . " (Score: " . $imovel['score_detalhes']['score'] . ")\n";
    }
} else {
    echo "   Nenhum imóvel exato encontrado.\n";
}

echo "\n⚠️ QUASE LÁ (Score 40-69):\n";
if (!empty($categorizado['quase_la'])) {
    foreach ($categorizado['quase_la'] as $i => $imovel) {
        echo "   " . ($i + 1) . ". " . $imovel['titulo'] . " (Score: " . $imovel['score_detalhes']['score'] . ")\n";
    }
} else {
    echo "   Nenhum imóvel quase lá encontrado.\n";
}

echo "\n❌ DESCARTADOS (Score < 40):\n";
if (!empty($categorizado['descartados'])) {
    foreach ($categorizado['descartados'] as $i => $imovel) {
        echo "   " . ($i + 1) . ". " . $imovel['titulo'] . " (Score: " . $imovel['score_detalhes']['score'] . ")\n";
    }
} else {
    echo "   Todos os imóveis foram aprovados!\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "MENSAGEM FORMATADA PARA O USUÁRIO\n";
echo "═══════════════════════════════════════════════════════\n\n";

$resultado = MatchingEngine::generateRecommendations($imoveisComScore, $slotsUsuario);

echo $resultado['mensagem'] . "\n\n";

echo "═══════════════════════════════════════════════════════\n";
echo "RESUMO\n";
echo "═══════════════════════════════════════════════════════\n";
echo "Total de imóveis analisados: " . count($imoveisComScore) . "\n";
echo "Exatos apresentados: " . count($resultado['imoveis_exatos']) . "\n";
echo "Quase lá apresentados: " . count($resultado['imoveis_quase_la']) . "\n";
echo "Total apresentado: " . $resultado['total_apresentados'] . "\n";
echo "═══════════════════════════════════════════════════════\n";
