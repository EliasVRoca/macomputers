<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usuarios\CambiarEstadoUsuarioRequest;
use App\Http\Requests\Usuarios\FiltrarUsuariosRequest;
use App\Http\Requests\Usuarios\GuardarUsuarioRequest;
use App\Models\Persona;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(FiltrarUsuariosRequest $request): View
    {
        $filtros = $request->validated();

        return view('usuarios.index', [
            'items' => $this->listado($filtros)->paginate(15)->withQueryString(),
            'filtros' => $filtros,
            'roles' => Role::orderBy('nombre')->get(),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new User(['activo' => true]), new Persona, route('usuarios.store'), 'POST');
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos) {
            $persona = Persona::create($this->datosPersona($datos));
            User::create([...$this->datosCuenta($datos), 'persona_id' => $persona->id]);
        });

        return redirect()->route('usuarios.index')->with('status', 'Usuario registrado.');
    }

    public function edit(User $usuario): View
    {
        return $this->formulario($usuario, $usuario->persona ?? new Persona, route('usuarios.update', $usuario), 'PUT');
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $usuario) {
            $persona = $usuario->persona ?? new Persona;
            $persona->fill($this->datosPersona($datos))->save();

            $cuenta = $this->datosCuenta($datos);
            if (blank($cuenta['password'] ?? null)) {
                unset($cuenta['password']);
            }

            $usuario->update([...$cuenta, 'persona_id' => $persona->id]);
        });

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado.');
    }

    /**
     * Baja lógica: nunca se borra el usuario ni su persona.
     */
    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()->is($usuario)) {
            return back()->withErrors(['eliminar' => 'No puede dar de baja su propio usuario.']);
        }

        $this->desactivar($usuario);

        return redirect()->route('usuarios.index')
            ->with('status', 'Usuario dado de baja. Puede reactivarlo cuando lo necesite.');
    }

    public function estado(CambiarEstadoUsuarioRequest $request, User $usuario): RedirectResponse
    {
        if ($request->user()->is($usuario) && ! $request->boolean('activo')) {
            return back()->withErrors(['estado' => 'No puede desactivar su propia cuenta.']);
        }

        if ($request->boolean('activo')) {
            $usuario->update(['activo' => true]);

            return redirect()->route('usuarios.index')->with('status', 'Usuario activado.');
        }

        $this->desactivar($usuario);

        return redirect()->route('usuarios.index')->with('status', 'Usuario desactivado.');
    }

    private function desactivar(User $usuario): void
    {
        $usuario->update(['activo' => false]);

        DB::table('sessions')->where('user_id', $usuario->id)->delete();
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

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function datosPersona(array $datos): array
    {
        return [
            'nombre' => $datos['nombre'],
            'ci' => $datos['ci'],
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function datosCuenta(array $datos): array
    {
        return [
            'username' => $datos['username'],
            'email' => $datos['email'],
            'password' => $datos['password'] ?? null,
            'role_id' => $datos['role_id'],
            'sucursal_id' => $datos['sucursal_id'],
            'activo' => (bool) ($datos['activo'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function listado(array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');

        return User::query()
            ->with(['persona', 'role', 'sucursal'])
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('username', 'like', "%{$buscar}%")
                ->orWhere('email', 'like', "%{$buscar}%")
                ->orWhereHas('persona', fn (Builder $p) => $p
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('ci', 'like', "%{$buscar}%"))))
            ->when($filtros['role_id'] ?? null, fn (Builder $q) => $q->where('role_id', $filtros['role_id']))
            ->when($filtros['sucursal_id'] ?? null, fn (Builder $q) => $q->where('sucursal_id', $filtros['sucursal_id']))
            ->when(array_key_exists('activo', $filtros) && $filtros['activo'] !== null, fn (Builder $q) => $q->where('activo', (bool) $filtros['activo']))
            ->orderBy('username');
    }
}
