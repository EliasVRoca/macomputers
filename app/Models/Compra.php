<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'proveedor_id', 'sucursal_id', 'user_id', 'fecha', 'total',
        'anulada_at', 'anulada_por_id', 'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'total' => 'decimal:2', 'anulada_at' => 'datetime'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompraItem::class);
    }

    public function estaAnulada(): bool
    {
        return $this->anulada_at !== null;
    }
}
