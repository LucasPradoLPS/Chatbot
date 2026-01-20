<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    /**
     * Agendar visita de forma completa
     * Retorna: token de confirmação + mensagem para enviar ao cliente
     */
    public static function agendarVisita(
        int $empresaId,
        string $clienteJid,
        string $clienteNome,
        int $imovelId,
        string $imovelTitulo,
        Carbon $dataAgendada,
        ?string $observacoes = null
    ): array {
        try {
            $token = Str::random(32);
            
            $appointment = Appointment::create([
                'empresa_id' => $empresaId,
                'cliente_jid' => $clienteJid,
                'cliente_nome' => $clienteNome,
                'imovel_id' => $imovelId,
                'imovel_titulo' => $imovelTitulo,
                'data_agendada' => $dataAgendada,
                'confirmation_token' => $token,
                'observacoes' => $observacoes,
                'status' => 'pendente_confirmacao',
            ]);
            
            Log::info("Visita agendada", [
                'appointment_id' => $appointment->id,
                'cliente_jid' => $clienteJid,
                'imovel_id' => $imovelId,
                'data' => $dataAgendada,
            ]);
            
            return [
                'sucesso' => true,
                'appointment_id' => $appointment->id,
                'token' => $token,
                'mensagem' => self::gerarMensagemConfirmacao($appointment),
                'link_confirmacao' => route('appointment.confirm', ['token' => $token]),
                'link_cancelamento' => route('appointment.cancel', ['token' => $token]),
            ];
        } catch (\Exception $e) {
            Log::error("Erro ao agendar visita", [
                'erro' => $e->getMessage(),
                'cliente_jid' => $clienteJid,
            ]);
            
            return [
                'sucesso' => false,
                'erro' => 'Não consegui agendar. Tente novamente.',
            ];
        }
    }
    
    /**
     * Confirmar visita (quando cliente clica no botão)
     */
    public static function confirmarVisita(string $token): array
    {
        $appointment = Appointment::where('confirmation_token', $token)->first();
        
        if (!$appointment) {
            return ['sucesso' => false, 'erro' => 'Agendamento não encontrado'];
        }
        
        if ($appointment->status === 'confirmada') {
            return ['sucesso' => true, 'msg' => 'Visita já confirmada!'];
        }
        
        $appointment->update([
            'status' => 'confirmada',
            'confirmada_em' => Carbon::now(),
        ]);
        
        Log::info("Visita confirmada", ['appointment_id' => $appointment->id]);
        
        return [
            'sucesso' => true,
            'mensagem' => "✅ Perfeito {$appointment->cliente_nome}! Sua visita ao {$appointment->imovel_titulo} está confirmada para {$appointment->data_agendada->format('d/m à\\s H:i')}.",
        ];
    }
    
    /**
     * Enviar lembrete 24h antes
     */
    public static function enviarLembretes(): void
    {
        $appointments = Appointment::where('status', 'confirmada')
            ->whereNull('lembrete_enviado_em')
            ->whereBetween('data_agendada', [
                Carbon::now()->addHours(23),
                Carbon::now()->addHours(25),
            ])
            ->get();
        
        foreach ($appointments as $apt) {
            $mensagem = "⏰ LEMBRETE: Sua visita ao {$apt->imovel_titulo} é amanhã às {$apt->data_agendada->format('H:i')}. Confirma presença? ✅ Confirmo | ❌ Preciso reagendar";
            
            // Enviar via Evolution API
            EvolutionApiService::enviarMensagem(
                $apt->cliente_jid,
                $mensagem,
                $apt->empresa_id
            );
            
            $apt->update(['lembrete_enviado_em' => Carbon::now()]);
        }
    }
    
    /**
     * Reagendar visita
     */
    public static function reagendar(
        int $appointmentId,
        Carbon $novaData,
        ?string $motivo = null
    ): array {
        $appointment = Appointment::find($appointmentId);
        
        if (!$appointment) {
            return ['sucesso' => false, 'erro' => 'Agendamento não encontrado'];
        }
        
        $dataAnterior = $appointment->data_agendada;
        
        $appointment->update([
            'data_agendada' => $novaData,
            'status' => 'reagendada',
            'observacoes' => ($appointment->observacoes ?? '') . "\n[Reagendado de {$dataAnterior->format('d/m/Y H:i')} para {$novaData->format('d/m/Y H:i')}. Motivo: $motivo]",
        ]);
        
        $mensagem = "✅ Reagendado! Sua visita agora é: {$novaData->format('d/m à\\s H:i')}. Confirma? ✅ Confirmo | ❌ Cancelar";
        
        return [
            'sucesso' => true,
            'mensagem' => $mensagem,
        ];
    }
    
    /**
     * Cancelar visita
     */
    public static function cancelar(
        int $appointmentId,
        string $motivo
    ): array {
        $appointment = Appointment::find($appointmentId);
        
        if (!$appointment) {
            return ['sucesso' => false, 'erro' => 'Agendamento não encontrado'];
        }
        
        $appointment->update([
            'status' => 'cancelada',
            'observacoes' => ($appointment->observacoes ?? '') . "\n[Cancelado. Motivo: $motivo]",
        ]);
        
        return [
            'sucesso' => true,
            'mensagem' => "Entendido. Sua visita foi cancelada. Se quiser reagendar depois, é só me chamar!",
        ];
    }
    
    /**
     * Gerar mensagem de confirmação
     */
    private static function gerarMensagemConfirmacao(Appointment $apt): string
    {
        $data = $apt->data_agendada->format('d/m/Y');
        $hora = $apt->data_agendada->format('H:i');
        
        return <<<MSG
🎯 Visita agendada com sucesso!

📍 {$apt->imovel_titulo}
📅 {$data} às {$hora}

Por favor, confirme sua presença:

✅ Confirmo a visita
❌ Preciso reagendar
💬 Tenho dúvida
MSG;
    }
    
    /**
     * Obter status de uma visita
     */
    public static function obterStatus(string $token): ?Appointment
    {
        return Appointment::where('confirmation_token', $token)->first();
    }
    
    /**
     * Listar visitas confirmadas do cliente
     */
    public static function listarVisitasCliente(string $clienteJid, int $empresaId): array
    {
        return Appointment::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->where('status', '!=', 'cancelada')
            ->orderBy('data_agendada')
            ->get()
            ->map(fn($apt) => [
                'id' => $apt->id,
                'imovel' => $apt->imovel_titulo,
                'data' => $apt->data_agendada->format('d/m à\\s H:i'),
                'status' => $apt->status,
            ])
            ->toArray();
    }
}
