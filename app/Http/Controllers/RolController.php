<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    public function index(): View
    {
        return view('roles.index', ['items' => Role::withCount('users')->orderBy('nombre')->paginate(15)]);
    }

    public function create(): View
    {
        return view('roles.form', ['rol' => new Role(['permisos' => []]), 'accion' => route('roles.store'), 'metodo' => 'POST']);
    }

    public function store(Request $request): RedirectResponse
    {
        Role::create($this->validar($request));

        return redirect()->route('roles.index')->with('status', 'Rol registrado.');
    }

    public function edit(Role $role): View
    {
        return view('roles.form', ['rol' => $role, 'accion' => route('roles.update', $role), 'metodo' => 'PUT']);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->es_sistema) {
            return back()->withErrors(['rol' => 'El rol Gerente es de sistema y no puede modificarse.']);
        }

        $role->update($this->validar($request, $role));

        return redirect()->route('roles.index')->with('status', 'Rol actualizado.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->es_sistema) {
            return back()->withErrors(['eliminar' => 'El rol Gerente es de sistema y no puede eliminarse.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['eliminar' => 'No se puede eliminar: el rol tiene usuarios asignados.']);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', 'Rol eliminado.');
    }

    private function validar(Request $request, ?Role $role = null): array
    {
        $claves = array_keys(config('permisos.modulos'));

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60', Rule::unique('roles', 'nombre')->ignore($role?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string', Rule::in($claves)],
        ]);

        $datos['permisos'] = array_values($datos['permisos'] ?? []);
        $datos['es_sistema'] = false;

        return $datos;
    }
}
