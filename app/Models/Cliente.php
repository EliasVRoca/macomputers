<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = ['nombre', 'telefono', 'direccion', 'carnet', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function apartados(): HasMany
    {
        return $this->hasMany(Apartado::class);
    }

    public function encargos(): HasMany
    {
        return $this->hasMany(Encargo::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }
}
