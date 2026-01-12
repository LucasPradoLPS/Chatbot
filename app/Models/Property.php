<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'codigo_propriedade',
        'titulo',
        'descricao',
        'tipo_imovel',
        'bairro',
        'endereco',
        'numero',
        'complemento',
        'cep',
        'cidade',
        'estado',
        'preco',
        'preco_aluguel',
        'quartos',
        'banheiros',
        'vagas',
        'area_total',
        'area_util',
        'condominio',
        'iptu',
        'tags',
        'fotos',
        'maps_url',
        'maps_lat',
        'maps_lng',
        'status',
        'disponivel_desde',
        'publicado_em',
    ];

    protected $casts = [
        'fotos' => 'array',
        'tags' => 'array',
        'preco' => 'float',
        'preco_aluguel' => 'float',
        'condominio' => 'float',
        'iptu' => 'float',
        'area_total' => 'float',
        'area_util' => 'float',
        'quartos' => 'integer',
        'banheiros' => 'integer',
        'vagas' => 'integer',
        'maps_lat' => 'float',
        'maps_lng' => 'float',
        'disponivel_desde' => 'date',
        'publicado_em' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function eventLogs()
    {
        return $this->hasMany(EventLog::class);
    }
}
