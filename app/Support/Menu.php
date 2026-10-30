<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Construye la navegación (riel, panel y paleta) según los permisos del usuario.
 */
class Menu
{
    /**
     * Grupos con sus módulos, ya filtrados por permisos.
     *
     * @return array<int, array{nombre: string, icon: string, activo: bool, modulos: array<int, array{clave: string, label: string, icon: string, route: string, activo: bool}>}>
     */
    public static function para(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $modulos = config('permisos.modulos');
        $rutaActual = Route::currentRouteName() ?? '';
        $grupos = [];

        foreach (config('permisos.grupos') as $nombre => $grupo) {
            $items = [];

            foreach ($grupo['modulos'] as $clave) {
                if (! isset($modulos[$clave]) || ! $user->tienePermiso($clave)) {
                    continue;
                }

                $items[] = [
                    'clave' => $clave,
                    'label' => $modulos[$clave]['label'],
                    'icon' => $modulos[$clave]['icon'],
                    'route' => $modulos[$clave]['route'],
                    'activo' => self::esActivo($rutaActual, $clave, $modulos[$clave]['route']),
                ];
            }

            if ($items === []) {
                continue;
            }

            $grupos[] = [
                'nombre' => $nombre,
                'icon' => $grupo['icon'],
                'activo' => collect($items)->contains(fn ($i) => $i['activo']),
                'modulos' => $items,
            ];
        }

        return $grupos;
    }

    /**
     * Acciones rápidas disponibles para el usuario.
     *
     * @return array<int, array{label: string, route: string, icon: string}>
     */
    public static function acciones(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect(config('permisos.acciones'))
            ->filter(fn ($a) => $user->tienePermiso($a['permiso']))
            ->map(fn ($a) => ['label' => $a['label'], 'route' => $a['route'], 'icon' => $a['icon']])
            ->values()
            ->all();
    }

    /**
     * Todas las entradas de la paleta: módulos + acciones.
     *
     * @return array<int, array{label: string, grupo: ?string, url: string}>
     */
    public static function paleta(?User $user): array
    {
        $entradas = [];

        if ($user) {
            $entradas[] = ['label' => 'Reportes', 'grupo' => null, 'url' => route('reportes.index')];
        }

        foreach (self::para($user) as $grupo) {
            foreach ($grupo['modulos'] as $modulo) {
                $entradas[] = [
                    'label' => $modulo['label'],
                    'grupo' => $grupo['nombre'],
                    'url' => route($modulo['route']),
                ];
            }
        }

        foreach (self::acciones($user) as $accion) {
            $entradas[] = [
                'label' => $accion['label'],
                'grupo' => null,
                'url' => route($accion['route']),
            ];
        }

        return $entradas;
    }

    public static function primerModulo(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (config('permisos.grupos') as $grupo) {
            foreach ($grupo['modulos'] as $clave) {
                if ($user->tienePermiso($clave)) {
                    return config("permisos.modulos.{$clave}.route");
                }
            }
        }

        return null;
    }

    /**
     * Un módulo está activo si la ruta actual pertenece a su prefijo (ventas.index, ventas.create...).
     */
    private static function esActivo(string $rutaActual, string $clave, string $rutaModulo): bool
    {
        return $rutaActual === $rutaModulo || str_starts_with($rutaActual, $clave.'.');
    }
}
