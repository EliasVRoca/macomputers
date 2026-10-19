<?php

namespace App\Services;

use App\Models\Sucursal;
use App\Models\User;

/**
 * Reglas de baja lógica de sucursales.
 *
 * La sucursal nunca se borra: solo se desactiva. Para no dejar datos
 * inaccesibles, se bloquea la baja si todavía tiene stock, usuarios activos
 * asignados o si es la última sucursal activa del sistema.
 */
class SucursalService
{
    public function motivoNoDesactivable(Sucursal $sucursal): ?string
    {
        if ($sucursal->inventarios()->where('cantidad', '!=', 0)->exists()) {
            return 'la sucursal tiene stock; ajuste o traslade el inventario antes de darla de baja.';
        }

        if (User::query()->where('sucursal_id', $sucursal->id)->where('activo', true)->exists()) {
            return 'la sucursal tiene usuarios activos asignados; reasígnelos antes de darla de baja.';
        }

        if (! Sucursal::query()->where('activa', true)->whereKeyNot($sucursal->id)->exists()) {
            return 'no se puede dar de baja la última sucursal activa.';
        }

        return null;
    }
}
