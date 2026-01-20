<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\AgenteController;
use App\Http\Controllers\LogController;
Route::post('webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);
Route::post('webhook/whatsapp/messages-upsert', [WhatsappWebhookController::class, 'handle']);
Route::post('webhook/messages-upsert', [WhatsappWebhookController::class, 'handle']);
Route::post('webhook', [WhatsappWebhookController::class, 'handle']);
Route::post('agentes', [AgenteController::class, 'store']);
Route::post('agentes/generate', [AgenteController::class, 'generate']);
Route::get('ping', function () {
	Log::info('API Ping chamado', ['time' => now()->toISOString()]);
	return ['ok' => true, 'time' => now()->toISOString()];
});
Route::get('ping2', function () {
	Log::info('API Ping2 chamado');
    return 'OK';
});
Route::prefix('debug')->group(function () {
	Route::get('logs', [LogController::class, 'index']);
	Route::get('logs/{filename}', [LogController::class, 'show']);
	Route::delete('logs/{filename}', [LogController::class, 'clear']);
});
