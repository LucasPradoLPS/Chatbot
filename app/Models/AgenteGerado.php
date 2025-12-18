<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenteGerado extends Model
{
    use HasFactory;

    protected $table = 'agente_gerados';

    protected $fillable = [
        'empresa_id',
        'funcao',
        'agente_base_id',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
