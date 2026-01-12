<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuporteChamado extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'numero_cliente',
        'nome_cliente',
        'telefone_whatsapp',
        'unidade_endereco',
        'tipo_problema',
        'urgencia',
        'midia_link',
        'status',
        'prioridade',
        'sla_estimativa_horas',
        'observacoes',
    ];

    protected $casts = [
        'sla_estimativa_horas' => 'integer',
    ];
}
