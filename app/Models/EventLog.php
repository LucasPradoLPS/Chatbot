<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'numero_cliente',
        'event_type',
        'property_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function thread()
    {
        return $this->hasOne(Thread::class, 'numero_cliente', 'numero_cliente');
    }
}
