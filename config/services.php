<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // OpenAI key used by the app for assistant/thread operations
    'openai' => [
        'key' => env('OPENAI_KEY'),
    ],

    // Evolution API (service that sends messages to WhatsApp instances)
    'evolution' => [
        'key' => env('EVOLUTION_KEY'),
        'url' => env('EVOLUTION_URL'),
    ],
];
