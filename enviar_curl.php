<?php
$mensagem = $argv[1] ?? 'Olá';
$numero = $argv[2] ?? '553199380844';

$payload = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => $numero . '@s.whatsapp.net',
            'senderPn' => $numero,
            'id' => 'msg_' . uniqid(),
            'fromMe' => false
        ],
        'message' => [
            'conversation' => $mensagem
        ]
    ]
];

$json = json_encode($payload);

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  📨 ENVIANDO VIA CURL                                   ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "📝 Mensagem: $mensagem\n";
echo "📱 Número: $numero\n\n";

$cmd = "curl -X POST http://127.0.0.1:8000/api/webhook/whatsapp -H \"Content-Type: application/json\" -d '" . str_replace("'", "'\\''", $json) . "' -w \"\\nStatus: %{http_code}\\n\" 2>&1";

echo "🔄 Executando curl...\n\n";
$output = [];
$return = 0;
exec($cmd, $output, $return);

foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n";
