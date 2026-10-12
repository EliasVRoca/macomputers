<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Encargo extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_COMPLETADO = 'completado';

    protected $table = 'encargos';

    protected $fillable = ['cliente_id', 'sucursal_id', 'user_id', 'descripcion', 'anticipo', 'saldo', 'estado', 'fecha'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'anticipo' => 'decimal:2', 'saldo' => 'decimal:2'];
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
}
