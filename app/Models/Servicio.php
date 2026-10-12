<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    public const ESTADOS = [
        'recibido' => 'Recibido',
        'en_proceso' => 'En proceso',
        'listo' => 'Listo',
        'entregado' => 'Entregado',
    ];

    protected $table = 'servicios';

    protected $fillable = ['venta_id', 'cliente_id', 'sucursal_id', 'tecnico_id', 'equipo', 'diagnostico', 'estado'];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }
}
