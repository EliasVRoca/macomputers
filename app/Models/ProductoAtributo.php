<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Atributo libre de la ficha técnica de un producto (clave/valor).
 */
class ProductoAtributo extends Model
{
    protected $table = 'producto_atributos';

    protected $fillable = ['producto_id', 'atributo', 'valor'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
