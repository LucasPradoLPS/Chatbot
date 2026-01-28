<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'numero_cliente',
        'thread_id',
        'assistente_id',
        'ultima_atividade_usuario',
        'slots',
        'etapa_fluxo',
        'objetivo',
        'lgpd_consentimento',
        'intent',
        'estado_atual',
        'estado_historico',
        'fallback_tentativas',
        'refino_ciclos',
        'crm_status',
        'ultimo_contato',
        'proximo_followup',
        'followup_tentativas',
        'motivo_perda',
        'lgpd_consentimento_data',
        'lgpd_opt_out',
        'lgpd_politica_versao',
        'saudacao_inicial',
    ];

    protected $casts = [
        'ultima_atividade_usuario' => 'datetime',
        'slots' => 'array',
        'lgpd_consentimento' => 'boolean',
        'estado_historico' => 'array',
        'fallback_tentativas' => 'integer',
        'refino_ciclos' => 'integer',
        'ultimo_contato' => 'datetime',
        'proximo_followup' => 'datetime',
        'followup_tentativas' => 'integer',
        'lgpd_consentimento_data' => 'datetime',
        'lgpd_opt_out' => 'boolean',
    ];
}
