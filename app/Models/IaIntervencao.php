<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IaIntervencao extends Model
{
    use HasFactory;

    protected $table = 'ia_intervencoes';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'numero_cliente',
        'intervencao_em',
    ];

    protected $casts = [
        'intervencao_em' => 'datetime',
    ];
}
