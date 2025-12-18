<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensagensMemoria extends Model
{
    use HasFactory;

    protected $table = 'mensagens_memorias';

    protected $fillable = [
        'empresa_id',
        'numero_cliente',
        'mensagem',
        'tipo',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
