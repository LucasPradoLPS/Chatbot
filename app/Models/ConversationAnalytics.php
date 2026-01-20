<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationAnalytics extends Model
{
    protected $table = 'conversation_analytics';
    
    protected $fillable = [
        'empresa_id',
        'cliente_jid',
        'thread_id',
        'chegou_em_qualificacao_em',
        'recebeu_opcoes_em',
        'pediu_visita_em',
        'visitou_em',
        'recebeu_proposta_em',
        'converteu_em',
        'nps',
        'csat',
        'feedback_texto',
        'motivo_nao_conversao',
        'num_mensagens',
        'num_imagens_recebidas',
        'num_opcoes_apresentadas',
        'num_imoveis_clicados',
        'tempo_medio_resposta_seg',
        'objecoes_detectadas',
        'playbooks_usados',
    ];
    
    protected $casts = [
        'chegou_em_qualificacao_em' => 'datetime',
        'recebeu_opcoes_em' => 'datetime',
        'pediu_visita_em' => 'datetime',
        'visitou_em' => 'datetime',
        'recebeu_proposta_em' => 'datetime',
        'converteu_em' => 'datetime',
        'objecoes_detectadas' => 'array',
        'playbooks_usados' => 'array',
    ];
    
    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }
}
