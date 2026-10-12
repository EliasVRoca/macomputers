<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraspasoItem extends Model
{
    protected $table = 'traspaso_items';

    protected $fillable = ['traspaso_id', 'producto_id', 'cantidad'];

    public function traspaso(): BelongsTo
    {
        return $this->belongsTo(Traspaso::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
