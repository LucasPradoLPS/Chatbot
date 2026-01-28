<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Chatbot - Permitir responder a chat consigo mesmo
    |--------------------------------------------------------------------------
    | Quando verdadeiro, o job de WhatsApp não irá ignorar mensagens com
    | `fromMe=true`. Útil para testes em conversas "Eu mesmo"; desative em
    | produção para evitar loops.
    */
    'allow_self_chat' => env('ALLOW_SELF_CHAT', false),

    // Processar webhook de forma síncrona para testes
    'queue_sync_webhook' => env('QUEUE_SYNC_WEBHOOK', false),

    // Quando true, não bloqueia mensagens fora do horário comercial.
    // Útil para manter o bot funcionando 24/7.
    'ignore_off_hours' => env('IGNORE_OFF_HOURS', false),

    /*
    |--------------------------------------------------------------------------
    | Chatbot - Regras de envio para Evolution
    |--------------------------------------------------------------------------
    | check_number_before_send: quando true, consulta existência do número no
    | Evolution antes de enviar. Para responder qualquer número, deixe false.
    | always_include_jid: quando true, inclui sempre o JID no payload de envio
    | (além de number), aumentando compatibilidade com diferentes instâncias.
    */
    'check_number_before_send' => env('CHECK_NUMBER_BEFORE_SEND', false),
    'always_include_jid' => env('ALWAYS_INCLUDE_JID', true),

    // Código do país para normalizar números WhatsApp (E.164)
    'whatsapp_country_code' => env('WHATSAPP_COUNTRY_CODE', '55'),

];
