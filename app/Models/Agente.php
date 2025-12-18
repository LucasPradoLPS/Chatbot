<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agente extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'ia_ativa',
        'responder_grupo',
    ];

    protected $casts = [
        'ia_ativa' => 'boolean',
        'responder_grupo' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
