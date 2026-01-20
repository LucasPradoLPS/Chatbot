<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    
    protected $fillable = [
        'empresa_id',
        'cliente_jid',
        'acao',
        'dados_acao',
        'imovel_id',
        'score_calculado',
        'criterios_score',
        'decisao_motivo',
        'foi_sobrescrita',
        'sobrescrita_por',
    ];
    
    protected $casts = [
        'dados_acao' => 'array',
        'criterios_score' => 'array',
    ];
    
    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }
}
