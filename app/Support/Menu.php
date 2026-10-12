<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Construye el navleft según los permisos del usuario autenticado.
 */
class Menu
{
    /**
     * @return array<string, array<int, array{clave: string, label: string, route: string, activo: bool}>>
     */
    public static function para(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $modulos = config('permisos.modulos');
        $menu = [];

        foreach (config('permisos.grupos') as $grupo => $claves) {
            foreach ($claves as $clave) {
                if (! isset($modulos[$clave]) || ! $user->tienePermiso($clave)) {
                    continue;
                }

                $nombreRuta = Route::currentRouteName() ?? '';

                $menu[$grupo][] = [
                    'clave' => $clave,
                    'label' => $modulos[$clave]['label'],
                    'route' => $modulos[$clave]['route'],
                    'activo' => str_starts_with($nombreRuta, $clave.'.') || $nombreRuta === $modulos[$clave]['route'],
                ];
            }
        }

        return $menu;
    }

    public static function primerModulo(User $user): ?string
    {
        foreach (config('permisos.grupos') as $claves) {
            foreach ($claves as $clave) {
                if ($user->tienePermiso($clave)) {
                    return config("permisos.modulos.{$clave}.route");
                }
            }
        }

        return null;
    }
}
