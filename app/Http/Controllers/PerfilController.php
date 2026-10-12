<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        $usuario = $request->user();

        return view('perfil.edit', ['usuario' => $usuario->load(['persona', 'role', 'sucursal'])]);
    }

    public function update(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->id)],
        ]);

        DB::transaction(function () use ($datos, $usuario) {
            $usuario->persona()->updateOrCreate([], [
                'nombre' => $datos['nombre'],
                'ci' => $usuario->persona?->ci ?? ('S/C-'.$usuario->id),
                'telefono' => $datos['telefono'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
            ]);

            $usuario->update(['email' => $datos['email']]);
        });

        return redirect()->route('perfil.edit')->with('status', 'Datos actualizados.');
    }

    public function password(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'current_password' => 'contraseña actual',
        ]);

        $request->user()->update(['password' => $datos['password']]);

        return redirect()->route('perfil.edit')->with('status', 'Contraseña actualizada.');
    }

    public function sesiones(Request $request): RedirectResponse
    {
        $cerradas = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return redirect()->route('perfil.edit')->with('status', $cerradas > 0
            ? "Se cerraron {$cerradas} sesión(es) en otros equipos."
            : 'No había otras sesiones abiertas.');
    }
}
