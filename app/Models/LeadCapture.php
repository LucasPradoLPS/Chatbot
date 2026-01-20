<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadCapture extends Model
{
    use SoftDeletes;
    
    protected $table = 'lead_captures';
    
    protected $fillable = [
        'empresa_id',
        'cliente_jid',
        'cliente_nome',
        'cliente_telefone',
        'renda_aproximada',
        'tipo_financiamento',
        'prazo_desejado_anos',
        'urgencia',
        'tem_pre_aprovacao',
        'pre_aprovacao_valor',
        'pre_aprovacao_banco',
        'cidade_principal',
        'bairros_nao_negociaveis',
        'top_3_prioridades',
        'imoveis_gostou',
        'imoveis_descartou',
        'preferencias_descartadas',
        'ultimo_contato_em',
        'status_lead',
        'dias_inativo',
        'enviou_follow_up_1',
        'enviou_follow_up_2',
        'proximo_follow_up_em',
        'consentimento_dados',
        'consentimento_dados_em',
        'consentimento_marketing',
        'consentimento_marketing_em',
    ];
    
    protected $casts = [
        'renda_aproximada' => 'decimal:2',
        'tem_pre_aprovacao' => 'boolean',
        'enviou_follow_up_1' => 'boolean',
        'enviou_follow_up_2' => 'boolean',
        'consentimento_dados' => 'boolean',
        'consentimento_marketing' => 'boolean',
        'bairros_nao_negociaveis' => 'array',
        'top_3_prioridades' => 'array',
        'imoveis_gostou' => 'array',
        'imoveis_descartou' => 'array',
        'preferencias_descartadas' => 'array',
        'ultimo_contato_em' => 'datetime',
        'consentimento_dados_em' => 'datetime',
        'consentimento_marketing_em' => 'datetime',
        'proximo_follow_up_em' => 'datetime',
    ];
    
    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }
}
