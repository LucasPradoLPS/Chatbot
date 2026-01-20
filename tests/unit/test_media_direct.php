<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\ProcessWhatsappMessage;
use App\Models\InstanciaWhatsapp;

$tipo = $argv[1] ?? 'pdf';
$instancia = InstanciaWhatsapp::first();
if (!$instancia) {
    echo "❌ Nenhuma InstanciaWhatsapp encontrada. Crie uma na base.\n";
    exit(1);
}

$numero = '5511999999010';
$jid = $numero . '@s.whatsapp.net';
$mediaUrl = null;
$mimetype = null;
$message = [];

switch (strtolower($tipo)) {
    case 'image':
        $mediaUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Fronalpstock_full.jpg/640px-Fronalpstock_full.jpg';
        $mimetype = 'image/jpeg';
        $message = [
            'imageMessage' => [
                'url' => $mediaUrl,
                'mimetype' => $mimetype,
                'caption' => 'Legenda de teste da imagem',
            ],
        ];
        break;
    case 'docx':
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
    'instance' => $instancia->instance_name,
    'event' => 'messages.upsert',
    'data' => [
        'key' => [
            'remoteJid' => $jid,
            'senderPn' => $numero,
            'fromMe' => false,
            'id' => 'TEST_MEDIA_' . uniqid(),
        ],
        'message' => $message,
        'source' => 'simulate',
    ],
];

echo "\n→ Processando mídia '{$tipo}' diretamente (sem HTTP)\n";
(new ProcessWhatsappMessage($payload))->handle();
echo "\n✓ Concluído. Veja logs em storage/logs/laravel.log\n\n";
