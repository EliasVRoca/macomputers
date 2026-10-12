<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'contacto', 'direccion', 'pais_origen'];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }
}
