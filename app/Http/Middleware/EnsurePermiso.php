<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas: ->middleware('permiso:ventas') o ->middleware('permiso:apartados,encargos').
 * Con varios permisos alcanza con que el usuario tenga uno de ellos.
 * La validación ocurre en el servidor; el navleft solo oculta opciones.
 */
class EnsurePermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $user = $request->user();
        $autorizado = $user && collect($permisos)
            ->contains(fn (string $permiso): bool => $user->tienePermiso($permiso));

        if (! $autorizado) {
            abort(403, 'No tiene permiso para acceder a este módulo.');
        }

        return $next($request);
    }
}
