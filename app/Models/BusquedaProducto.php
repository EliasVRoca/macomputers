<?php

namespace App\Models;

use App\Enums\ResultadoBusqueda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una búsqueda del buscador inteligente.
 */
class BusquedaProducto extends Model
{
    protected $table = 'busquedas_producto';

    protected $fillable = ['termino', 'sucursal_id', 'user_id', 'resultado', 'producto_id'];

    protected function casts(): array
    {
        return ['resultado' => ResultadoBusqueda::class];
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
