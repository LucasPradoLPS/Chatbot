<?php
// Testa o webhook com mídia (imagem/PDF/DOCX/TXT/CSV) usando uma URL pública
// Uso:
//   php test_media.php pdf
//   php test_media.php image
//   php test_media.php docx
//   php test_media.php txt
//   php test_media.php csv

$tipo = $argv[1] ?? 'pdf';

$numero = '5511999999009';
$jid = $numero . '@s.whatsapp.net';
$instance = 'N8n';
$mediaUrl = null;
$mimetype = null;
$messageKey = [
    'remoteJid' => $jid,
    'fromMe' => false,
    'id' => 'TEST_MEDIA_' . uniqid(),
];

switch (strtolower($tipo)) {
    case 'image':
        // Imagem pública de teste (substitua se quiser)
        $mediaUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Fronalpstock_full.jpg/640px-Fronalpstock_full.jpg';
        $mimetype = 'image/jpeg';
        $message = [
            'imageMessage' => [
                'url' => $mediaUrl,
                'mimetype' => $mimetype,
                'caption' => 'Essa é a imagem para testar visão',
            ],
        ];
        break;
    case 'docx':
        // DOCX público de teste (modelo simples)
        $mediaUrl = 'https://filesamples.com/samples/document/docx/sample3.docx';
        $mimetype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $message = [
            'documentMessage' => [
                'url' => $mediaUrl,
                'mimetype' => $mimetype,
                'fileName' => 'sample.docx',
            ],
        ];
        break;
    case 'txt':
        $mediaUrl = 'https://www.w3.org/TR/PNG/iso_8859-1.txt';
        $mimetype = 'text/plain';
        $message = [
            'documentMessage' => [
                'url' => $mediaUrl,
                'mimetype' => $mimetype,
                'fileName' => 'sample.txt',
            ],
        ];
        break;
    case 'csv':
        $mediaUrl = 'https://people.sc.fsu.edu/~jburkardt/data/csv/airtravel.csv';
        $mimetype = 'text/csv';
        $message = [
            'documentMessage' => [
                'url' => $mediaUrl,
                'mimetype' => $mimetype,
                'fileName' => 'sample.csv',
            ],
        ];
        break;
    case 'pdf':
    default:
        // PDF público de teste
        $mediaUrl = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
        $mimetype = 'application/pdf';
        $message = [
            'documentMessage' => [
                'url' => $mediaUrl,
                'mimetype' => $mimetype,
                'fileName' => 'dummy.pdf',
            ],
        ];
        break;
}

$payload = [
    'instance' => $instance,
    'event' => 'messages.upsert',
    'data' => [
        'key' => $messageKey,
        'message' => $message,
        'source' => 'simulate',
    ],
];

$endpoint = 'http://127.0.0.1:8000/api/webhook/whatsapp';

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

echo "\n→ Enviando teste '{$tipo}' para {$endpoint}\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP: {$httpCode}\n";
if ($err) {
    echo "Erro cURL: {$err}\n";
}
if ($response) {
    echo "Resposta: {$response}\n";
}

echo "\nPronto! Confira os logs em storage/logs/laravel.log\n\n";
