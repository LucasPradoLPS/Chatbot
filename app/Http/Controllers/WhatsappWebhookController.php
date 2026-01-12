<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $event = $data['event'] ?? null;
        // Evolution envia status dentro de data.status (e não em key.status); cobrimos todas as variantes.
        $status = $payloadData['status'] ?? ($data['status'] ?? ($key['status'] ?? null));
        $message = $payloadData['message'] ?? [];
        $messageId = $key['id'] ?? null;
        $remetente = $key['remoteJid']
            ?? ($payloadData['jid'] ?? null)
            ?? ($payloadData['number'] ?? null)
            ?? ($data['jid'] ?? null)
            ?? ($data['number'] ?? null);

        $fromMe = $key['fromMe'] ?? false;
        $source = $payloadData['source'] ?? null;
        $senderPn = $key['senderPn'] ?? null;

        // Dedup rápido: evita enfileirar/reprocessar a mesma mensagem ou update de status múltiplas vezes
        if ($messageId) {
            $dedupKey = 'webhook_msg_' . $messageId;
            if (!Cache::add($dedupKey, true, now()->addMinutes(10))) {
                Log::info('[BLOQUEADO] Webhook duplicado ignorado', [
                    'message_id' => $messageId,
                    'instance' => $instance,
                    'status' => $status,
                ]);
                http_response_code(202);
                header('Content-Type: application/json');
                die(json_encode(['ignored' => 'duplicate']));
            }
        }

        // Bloquear eventos de status apenas quando NÃO há conteúdo de mensagem
        // Alguns provedores enviam status DELIVERY_ACK junto com mensagens reais; por isso checamos se $message está vazio.
        if (empty($message) && in_array($status, ['DELIVERY_ACK', 'READ', 'ERROR', 'PENDING'])) {
            Log::debug('[BLOQUEADO] Evento de status, não é mensagem real', [
                'status' => $status,
                'evento' => $event,
                'instance' => $instance,
            ]);
            http_response_code(202);
            header('Content-Type: application/json');
            die(json_encode(['ignored' => 'status_update']));
        }

        // Bloquear se não houver conteúdo de mensagem
        if (empty($message)) {
            Log::debug('[BLOQUEADO] Webhook sem conteúdo de mensagem', [
                'instance' => $instance,
                'remetente' => $remetente,
                'keys_disponiveis' => array_keys($payloadData ?? []),
            ]);
            http_response_code(202);
            header('Content-Type: application/json');
            die(json_encode(['ignored' => 'no_message_content']));
        }

        // Garantir que senderPn seja incluído no data para o job processar
        if ($senderPn && isset($data['data']) && isset($data['data']['key'])) {
            $data['data']['key']['senderPn'] = $senderPn;
        }

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
