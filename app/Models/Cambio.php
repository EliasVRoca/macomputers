<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cambio extends Model
{
    public const TIPO_CAMBIO = 'cambio';

    public const TIPO_DEVOLUCION = 'devolucion';

    protected $table = 'cambios';

    protected $fillable = [
        'venta_id', 'producto_id', 'producto_reemplazo_id', 'sucursal_id', 'user_id', 'tipo', 'motivo', 'fecha',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function reemplazo(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_reemplazo_id');
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
