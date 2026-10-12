<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $items = User::with(['persona', 'role', 'sucursal'])->orderBy('username')->paginate(15);

        return view('usuarios.index', ['items' => $items]);
    }

    public function create(): View
    {
        return $this->formulario(new User(['activo' => true]), new Persona(), route('usuarios.store'), 'POST');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        DB::transaction(function () use ($datos) {
            $persona = Persona::create($datos['persona']);
            User::create([...$datos['cuenta'], 'persona_id' => $persona->id]);
        });

        return redirect()->route('usuarios.index')->with('status', 'Usuario registrado.');
    }

    public function edit(User $usuario): View
    {
        $persona = $usuario->persona ?? new Persona();

        return $this->formulario($usuario, $persona, route('usuarios.update', $usuario), 'PUT');
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);

        DB::transaction(function () use ($datos, $usuario) {
            $persona = $usuario->persona ?? new Persona();
            $persona->fill($datos['persona'])->save();

            $cuenta = $datos['cuenta'];
            if (blank($cuenta['password'] ?? null)) {
                unset($cuenta['password']);
            }

            $usuario->update([...$cuenta, 'persona_id' => $persona->id]);
        });

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()->is($usuario)) {
            return back()->withErrors(['eliminar' => 'No puede eliminar su propio usuario.']);
        }

        try {
            DB::transaction(function () use ($usuario) {
                $persona = $usuario->persona;
                $usuario->delete();
                $persona?->delete();
            });
        } catch (QueryException) {
            return back()->withErrors(['eliminar' => 'No se puede eliminar: el usuario tiene ventas o movimientos registrados. Desactívelo en su lugar.']);
        }

        return redirect()->route('usuarios.index')->with('status', 'Usuario eliminado.');
    }

    private function formulario(User $usuario, Persona $persona, string $accion, string $metodo): View
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'persona' => $persona,
            'accion' => $accion,
            'metodo' => $metodo,
            'roles' => Role::orderBy('nombre')->get(),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
        ]);
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ci' => ['required', 'string', 'max:20', Rule::unique('personas', 'ci')->ignore($usuario?->persona_id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($usuario?->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'sucursal_id' => ['required', Rule::exists('sucursales', 'id')],
        ], [], [
            'ci' => 'C.I.',
            'role_id' => 'rol',
            'sucursal_id' => 'sucursal',
        ]);

        return [
            'persona' => [
                'nombre' => $datos['nombre'],
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
            ],
            'cuenta' => [
                'username' => $datos['username'],
                'email' => $datos['email'],
                'password' => $datos['password'] ?? null,
                'role_id' => $datos['role_id'],
                'sucursal_id' => $datos['sucursal_id'],
                'activo' => $request->boolean('activo'),
            ],
        ];
    }
}
