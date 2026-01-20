<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = 'appointments';
    
    protected $fillable = [
        'empresa_id',
        'cliente_jid',
        'cliente_nome',
        'imovel_id',
        'imovel_titulo',
        'data_agendada',
        'status',
        'confirmation_token',
        'confirmada_em',
        'observacoes',
        'corretor_atribuido',
        'lembrete_enviado_em',
    ];
    
    protected $casts = [
        'data_agendada' => 'datetime',
        'confirmada_em' => 'datetime',
        'lembrete_enviado_em' => 'datetime',
    ];
    
    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }
}
