<?php

set_time_limit(0);
ini_set('max_execution_time', 0);

$url = "http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert";

$nomes = ["Joao", "Maria", "Carlos", "Ana", "Pedro", "Julia", "Lucas", "Fernanda", "Roberto", "Camila"];
$mensagens = [
    "Oi tudo bem", "Quero comprar imovel", "Alugar seria melhor",
    "Perdizes", "Ate 500 mil", "3 quartos", "Sim autorizo", "Concordo",
    "Me mostra as opcoes", "Qual valor", "Agendar visita", "Segunda"
];

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     TESTE DE ESTRESSE SUPER INTENSO DO CHATBOT            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

$numUsuarios = 150;
$msgPerUsuario = 6;
$totalRequisicoes = $numUsuarios * $msgPerUsuario;

echo "\nParametros:\n";
echo "  Usuarios: $numUsuarios\n";
echo "  Mensagens por usuario: $msgPerUsuario\n";
echo "  TOTAL: $totalRequisicoes requisicoes\n\n";

// FASE 1: Volume massivo
echo "FASE 1: TESTE DE VOLUME MASSIVO ($totalRequisicoes requisicoes)\n";
echo str_repeat("-", 60) . "\n";

$sucessos1 = 0;
$falhas1 = 0;
$inicio1 = microtime(true);

for ($u = 1; $u <= $numUsuarios; $u++) {
    $usuarioId = 55000000 + ($u * 1000) + rand(0, 999);
    $nome = $nomes[array_rand($nomes)];
    
    for ($m = 1; $m <= $msgPerUsuario; $m++) {
        $mensagem = $mensagens[array_rand($mensagens)];
        $messageId = "STRESS_INTENS_U{$u}_M{$m}_" . uniqid();
        
        $payload = [
            "instance" => "N8n",
            "data" => [
                "key" => [
                    "remoteJid" => "{$usuarioId}@s.whatsapp.net",
                    "id" => $messageId,
                    "fromMe" => false
                ],
                "pushName" => $nome,
                "message" => [
                    "conversation" => $mensagem
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 202 || $httpCode == 200) {
            $sucessos1++;
        } else {
            $falhas1++;
        }
        
        if (($u * $msgPerUsuario + $m) % 50 == 0) {
            $percentual = round(($u * $msgPerUsuario + $m) / $totalRequisicoes * 100, 1);
            echo "  [$percentual%] $sucessos1 OK, $falhas1 ERRO\n";
        }
    }
}

$tempo1 = microtime(true) - $inicio1;
echo "  FASE 1: " . number_format($sucessos1 + $falhas1) . " requisicoes em " . round($tempo1, 2) . " segundos\n";
$taxa1 = round($sucessos1 / ($sucessos1 + $falhas1) * 100, 2);
echo "  Taxa de sucesso: $taxa1%\n\n";

// FASE 2: Picos de trafego
echo "FASE 2: TESTE DE PICOS DE TRAFEGO (3 picos)\n";
echo str_repeat("-", 60) . "\n";

$sucessos2 = 0;
$falhas2 = 0;

for ($pico = 1; $pico <= 3; $pico++) {
    $inicio_pico = microtime(true);
    $sucessos_pico = 0;
    $falhas_pico = 0;
    
    for ($u = 1; $u <= 75; $u++) {
        $usuarioId = 56000000 + ($pico * 100000) + ($u * 1000);
        $nome = $nomes[array_rand($nomes)];
        
        for ($m = 1; $m <= 2; $m++) {
            $mensagem = $mensagens[array_rand($mensagens)];
            
            $payload = [
                "instance" => "N8n",
                "data" => [
                    "key" => [
                        "remoteJid" => "{$usuarioId}@s.whatsapp.net",
                        "id" => "PICO_{$pico}_U{$u}_M{$m}_" . uniqid(),
                        "fromMe" => false
                    ],
                    "pushName" => $nome,
                    "message" => [
                        "conversation" => $mensagem
                    ]
                ]
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 202 || $httpCode == 200) {
                $sucessos_pico++;
            } else {
                $falhas_pico++;
            }
        }
    }
    
    $tempo_pico = microtime(true) - $inicio_pico;
    $req_pico = $sucessos_pico + $falhas_pico;
    $taxa_pico = round($sucessos_pico / $req_pico * 100, 2);
    echo "  Pico $pico: $req_pico requisicoes em " . round($tempo_pico, 2) . " segundos | Taxa: $taxa_pico%\n";
    
    $sucessos2 += $sucessos_pico;
    $falhas2 += $falhas_pico;
}

echo "\n";

// FASE 3: Duracao continua
echo "FASE 3: TESTE DE DURACAO CONTINUA (45 segundos)\n";
echo str_repeat("-", 60) . "\n";

$sucessos3 = 0;
$falhas3 = 0;
$requisicoes3 = 0;
$inicio3 = time();
$duracao = 45;

while (time() - $inicio3 < $duracao) {
    $usuarioId = 57000000 + rand(0, 50000);
    $nome = $nomes[array_rand($nomes)];
    $mensagem = $mensagens[array_rand($mensagens)];
    
    $payload = [
        "instance" => "N8n",
        "data" => [
            "key" => [
                "remoteJid" => "{$usuarioId}@s.whatsapp.net",
                "id" => "DURACAO_" . uniqid(),
                "fromMe" => false
            ],
            "pushName" => $nome,
            "message" => [
                "conversation" => $mensagem
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $requisicoes3++;
    
    if ($httpCode == 202 || $httpCode == 200) {
        $sucessos3++;
    } else {
        $falhas3++;
    }
    
    usleep(50000); // 50ms entre requisicoes
}

$taxa3 = round($sucessos3 / $requisicoes3 * 100, 2);
echo "  FASE 3: $requisicoes3 requisicoes em $duracao segundos | Taxa: $taxa3%\n\n";

// Aguardar processamento
echo "Aguardando processamento completo (90 segundos)...\n";
for ($i = 0; $i < 90; $i++) {
    echo ".";
    sleep(1);
}
echo "\n\n";

// RESULTADOS FINAIS
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              RESULTADOS FINAIS DO TESTE                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$total_requisicoes = $sucessos1 + $falhas1 + $sucessos2 + $falhas2 + $sucessos3 + $falhas3;
$total_sucessos = $sucessos1 + $sucessos2 + $sucessos3;
$total_falhas = $falhas1 + $falhas2 + $falhas3;
$taxa_total = round($total_sucessos / $total_requisicoes * 100, 2);

echo "FASE 1 (Volume Massivo):\n";
echo "  Total: " . ($sucessos1 + $falhas1) . " requisicoes\n";
echo "  Sucessos: $sucessos1 | Falhas: $falhas1 | Taxa: $taxa1%\n\n";

echo "FASE 2 (Picos de Trafego):\n";
echo "  Total: " . ($sucessos2 + $falhas2) . " requisicoes\n";
$taxa2 = round($sucessos2 / ($sucessos2 + $falhas2) * 100, 2);
echo "  Sucessos: $sucessos2 | Falhas: $falhas2 | Taxa: $taxa2%\n\n";

echo "FASE 3 (Duracao Continua):\n";
echo "  Total: " . ($sucessos3 + $falhas3) . " requisicoes\n";
echo "  Sucessos: $sucessos3 | Falhas: $falhas3 | Taxa: $taxa3%\n\n";

echo "CONSOLIDADO:\n";
echo "  Total de Requisicoes: $total_requisicoes\n";
echo "  Total de Sucessos: $total_sucessos\n";
echo "  Total de Falhas: $total_falhas\n";
echo "  Taxa de Sucesso GERAL: $taxa_total%\n\n";

if ($taxa_total >= 99) {
    echo "✓ SUCCESS: Chatbot AGUENTA carga EXTREMA!\n";
} elseif ($taxa_total >= 95) {
    echo "✓ OK: Chatbot em excelente estado\n";
} else {
    echo "⚠ WARNING: Alguns problemas detectados\n";
}

echo "\n";

?>
