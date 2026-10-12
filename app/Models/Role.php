<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['nombre', 'descripcion', 'permisos', 'es_sistema'];

    protected function casts(): array
    {
        return [
            'permisos' => 'array',
            'es_sistema' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tienePermiso(string $clave): bool
    {
        if ($this->es_sistema) {
            return true;
        }

        return in_array($clave, $this->permisos ?? [], true);
    }
}
