<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\AgenteController;
use App\Http\Controllers\LogController;

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
	Log::info('API Ping chamado', ['time' => now()->toISOString()]);
	return ['ok' => true, 'time' => now()->toISOString()];
});
// Ping2 minimal
Route::get('ping2', function () {
	Log::info('API Ping2 chamado');
    return 'OK';
});

// Rotas de DEBUG para listar e gerenciar logs
Route::prefix('debug')->group(function () {
	// Listar todos os arquivos de log com debug detalhado
	Route::get('logs', [LogController::class, 'index']);
	// Ver conteúdo de um arquivo de log específico
	Route::get('logs/{filename}', [LogController::class, 'show']);
	// Limpar um arquivo de log
	Route::delete('logs/{filename}', [LogController::class, 'clear']);
});