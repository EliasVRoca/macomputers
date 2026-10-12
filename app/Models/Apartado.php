<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apartado extends Model
{
    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_COMPLETADO = 'completado';

    protected $table = 'apartados';

    protected $fillable = [
        'cliente_id', 'sucursal_id', 'user_id', 'producto_id', 'cantidad',
        'precio_unitario', 'sena', 'saldo', 'estado', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'precio_unitario' => 'decimal:2',
            'sena' => 'decimal:2',
            'saldo' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
