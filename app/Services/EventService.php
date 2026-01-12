<?php

namespace App\Services;

use App\Models\EventLog;
use Illuminate\Support\Facades\Log;

class EventService
{
    /**
     * Registrar um evento no sistema
     */
    public static function log(
        int $empresaId,
        string $numeroCliente,
        string $eventType,
        ?int $propertyId = null,
        ?array $metadata = null
    ): EventLog {
        $event = EventLog::create([
            'empresa_id' => $empresaId,
            'numero_cliente' => $numeroCliente,
            'event_type' => $eventType,
            'property_id' => $propertyId,
            'metadata' => $metadata ?? [],
        ]);

        Log::info('[EVENT] ' . $eventType, [
            'numero_cliente' => $numeroCliente,
            'property_id' => $propertyId,
            'metadata' => $metadata,
        ]);

        return $event;
    }

    /**
     * Lead criado (primeira mensagem)
     */
    public static function leadCreated(int $empresaId, string $numeroCliente, array $dadosLead = []): void
    {
        self::log($empresaId, $numeroCliente, 'lead_created', null, $dadosLead);
    }

    /**
     * Propriedade visualizada (card mostrado)
     */
    public static function propertyViewed(int $empresaId, string $numeroCliente, int $propertyId): void
    {
        self::log($empresaId, $numeroCliente, 'property_viewed', $propertyId, [
            'viewed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Visita agendada
     */
    public static function visitScheduled(int $empresaId, string $numeroCliente, ?int $propertyId = null, array $detalhes = []): void
    {
        self::log($empresaId, $numeroCliente, 'visit_scheduled', $propertyId, array_merge([
            'scheduled_at' => now()->toIso8601String(),
        ], $detalhes));
    }

    /**
     * Proposta enviada
     */
    public static function proposalSent(int $empresaId, string $numeroCliente, ?int $propertyId = null, array $detalhes = []): void
    {
        self::log($empresaId, $numeroCliente, 'proposal_sent', $propertyId, array_merge([
            'sent_at' => now()->toIso8601String(),
        ], $detalhes));
    }

    /**
     * Propriedade fechada/vendida
     */
    public static function propertyClosed(int $empresaId, string $numeroCliente, ?int $propertyId = null, array $detalhes = []): void
    {
        self::log($empresaId, $numeroCliente, 'fechado', $propertyId, array_merge([
            'closed_at' => now()->toIso8601String(),
        ], $detalhes));
    }

    /**
     * Lead perdido
     */
    public static function leadLost(int $empresaId, string $numeroCliente, ?int $propertyId = null, string $motivo = '', array $detalhes = []): void
    {
        self::log($empresaId, $numeroCliente, 'perdido', $propertyId, array_merge([
            'lost_at' => now()->toIso8601String(),
            'motivo' => $motivo,
        ], $detalhes));
    }

    /**
     * Mensagem de follow-up enviada
     */
    public static function followupSent(int $empresaId, string $numeroCliente, string $tipo = 'light', array $detalhes = []): void
    {
        self::log($empresaId, $numeroCliente, 'followup_' . $tipo, null, array_merge([
            'sent_at' => now()->toIso8601String(),
        ], $detalhes));
    }
}
