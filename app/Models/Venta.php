<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    public const TIPO_VENTA = 'venta';

    public const TIPO_SERVICIO = 'servicio';

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_ANULADA = 'anulada';

    /** Medios de pago admitidos en una venta. */
    public const MEDIOS_PAGO = ['Efectivo', 'Transferencia', 'Tarjeta', 'QR'];

    protected $table = 'ventas';

    protected $fillable = [
        'sucursal_id', 'cliente_id', 'user_id', 'tipo', 'fecha', 'medio_pago', 'total', 'estado',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'total' => 'decimal:2'];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }

    public function cambios(): HasMany
    {
        return $this->hasMany(Cambio::class);
    }

    public function servicio(): HasOne
    {
        return $this->hasOne(Servicio::class);
    }

    public function apartado(): HasOne
    {
        return $this->hasOne(Apartado::class);
    }

    public function numero(): string
    {
        return 'NV-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
