<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\AgenteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Webhook principal esperado pelo app
Route::post('webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);
// Alias quando Evolution adiciona sufixo do evento
Route::post('webhook/whatsapp/messages-upsert', [WhatsappWebhookController::class, 'handle']);
// Aliases gerais para compatibilidade
Route::post('webhook/messages-upsert', [WhatsappWebhookController::class, 'handle']);
Route::post('webhook', [WhatsappWebhookController::class, 'handle']);

// Agente: criação e geração de Assistant da OpenAI
Route::post('agentes', [AgenteController::class, 'store']);
Route::post('agentes/generate', [AgenteController::class, 'generate']);

// Ping simples para ver se API está respondendo
Route::get('ping', function () {
	return ['ok' => true, 'time' => now()->toISOString()];
});
// Ping2 minimal
Route::get('ping2', function () {
    return 'OK';
});