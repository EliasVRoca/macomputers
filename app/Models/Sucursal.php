<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'direccion', 'telefono', 'activa'];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }
}
