<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessWhatsappMessage;

class WhatsappWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        // Extrair remetente de forma robusta, cobrindo formatos alternativos do Evolution
        $instance = $data['instance'] ?? null;
        $payloadData = $data['data'] ?? [];
        $key = $payloadData['key'] ?? [];
        $remetente = $key['remoteJid']
            ?? ($payloadData['jid'] ?? null)
            ?? ($payloadData['number'] ?? null)
            ?? ($data['jid'] ?? null)
            ?? ($data['number'] ?? null);

        $fromMe = $key['fromMe'] ?? false;
        $source = $payloadData['source'] ?? null;

        Log::info('Webhook received; dispatching job', [
            'instance' => $instance,
            'remetente' => $remetente,
            'fromMe' => $fromMe,
            'source' => $source,
            'ip' => $request->ip(),
            'xff' => $request->header('X-Forwarded-For'),
        ]);
        Log::debug('[DEBUG] Webhook raw payload', ['payload' => $data]);

        if (!$instance || !$remetente) {
            // Using manual output to avoid issues with response()->json on Windows built-in server.
            http_response_code(400);
            header('Content-Type: application/json');
            // Log detalhado para investigar por que não há remetente/instância
            \Log::warning('[BLOQUEADO] Dados incompletos no webhook', [
                'instance' => $instance,
                'remetente' => $remetente,
                'payload_keys' => array_keys($data ?? []),
                'data_keys' => array_keys($payloadData ?? []),
            ]);
            die(json_encode(['error' => 'Dados incompletos']));
        }

        // Processamento: síncrono (imediato) para testes, ou assíncrono via fila.
        if (config('app.queue_sync_webhook')) {
            \Log::info('Webhook in sync mode; processing inline');
            (new \App\Jobs\ProcessWhatsappMessage($data))->handle();
        } else {
            ProcessWhatsappMessage::dispatch($data)->onQueue('whatsapp');
        }
        // Immediate ack to Evolution API.
        http_response_code(202);
        header('Content-Type: application/json');
        die(json_encode(['accepted' => true]));
    }
}
