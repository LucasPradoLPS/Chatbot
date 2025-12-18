<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappWebhookController;

Route::get('/', function () {
    try {
        return response()->json([
            'message' => 'Chatbot Laravel API',
            'status' => 'online'
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Compatibilidade: algumas configurações da Evolution usam caminhos sem o prefixo /api
// Aceitar POSTs em /webhook e /webhook/messages-upsert pelo mesmo controller
Route::post('/webhook', [WhatsappWebhookController::class, 'handle']);
Route::post('/webhook/messages-upsert', [WhatsappWebhookController::class, 'handle']);
