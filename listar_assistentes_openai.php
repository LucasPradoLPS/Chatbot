<?php

// Lista assistants da OpenAI (Assistants API v2), SEM chave hardcoded.
// Uso:
//   set OPENAI_API_KEY=...   (Windows)
//   php listar_assistentes_openai.php

$apiKey = getenv('OPENAI_API_KEY') ?: getenv('OPENAI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "Erro: defina OPENAI_API_KEY no ambiente (não commite chaves no git).\n");
    exit(1);
}

$ch = curl_init('https://api.openai.com/v1/assistants?limit=50');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'OpenAI-Beta: assistants=v2',
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$body = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false) {
    fwrite(STDERR, "Erro cURL: {$err}\n");
    exit(2);
}

if ($code < 200 || $code >= 300) {
    fwrite(STDERR, "HTTP {$code}: {$body}\n");
    exit(3);
}

echo $body;
