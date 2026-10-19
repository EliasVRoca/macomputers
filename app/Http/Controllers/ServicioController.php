<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicio;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\ServicioRepuesto;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ServicioService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServicioController extends Controller
{
    public function __construct(private ServicioService $servicios) {}

    public function index(Request $request): View
    {
        $usuario = $request->user();
        $buscar = trim((string) $request->query('buscar', ''));

        $items = Servicio::with(['cliente', 'sucursal', 'tecnico', 'venta'])
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('prioridad'), fn ($q) => $q->where('prioridad', $request->query('prioridad')))
            ->when($usuario->esGerente() && $request->filled('sucursal_id'), fn ($q) => $q->where('sucursal_id', $request->integer('sucursal_id')))
            ->when($request->boolean('mias'), fn ($q) => $q->where('tecnico_id', $usuario->id))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('numero', 'like', "%{$buscar}%")
                ->orWhere('equipo', 'like', "%{$buscar}%")
                ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%"))))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_recepcion', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_recepcion', '<=', $request->date('hasta')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $conteos = Servicio::query()
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('servicios.index', [
            'items' => $items,
            'conteos' => $conteos,
            'estados' => EstadoServicio::opciones(),
            'prioridades' => Servicio::PRIORIDADES,
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $usuario->esGerente(),
            'filtros' => $request->only(['buscar', 'estado', 'prioridad', 'sucursal_id', 'desde', 'hasta', 'mias']),
        ]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        return view('servicios.form', [
            'servicio' => new Servicio(['estado' => EstadoServicio::Recibido->value, 'prioridad' => 'normal']),
            'accion' => route('servicios.store'),
            'metodo' => 'POST',
            'clientes' => Cliente::orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'tecnicos' => $usuario->esGerente() ? $this->tecnicos() : collect(),
            'puedeElegirSucursal' => $usuario->esGerente(),
            'puedeElegirTecnico' => $usuario->esGerente(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $this->validar($request, $usuario, esAlta: true);

        $servicio = $this->servicios->crear([
            ...$datos,
            'sucursal_id' => $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id,
        ], $usuario);

        return redirect()->route('servicios.show', $servicio)
            ->with('status', "Orden {$servicio->numero} registrada.");
    }

    public function show(Request $request, Servicio $servicio): View
    {
        $this->autorizarSucursal($request, $servicio);

        $servicio->load(['cliente', 'sucursal', 'tecnico', 'venta.items', 'repuestos.producto', 'historial.usuario']);

        return view('servicios.show', [
            'servicio' => $servicio,
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'disponibles' => Inventario::where('sucursal_id', $servicio->sucursal_id)->pluck('cantidad', 'producto_id'),
            'siguientes' => $servicio->estadoEnum()->siguientes(),
        ]);
    }

    public function edit(Request $request, Servicio $servicio): View
    {
        $this->autorizarGestion($request, $servicio);

        return view('servicios.form', [
            'servicio' => $servicio->load(['cliente', 'sucursal', 'venta']),
            'accion' => route('servicios.update', $servicio),
            'metodo' => 'PUT',
            'clientes' => collect(),
            'sucursales' => $this->sucursales($request),
            'tecnicos' => collect(),
            'puedeElegirSucursal' => false,
            'puedeElegirTecnico' => false,
        ]);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarGestion($request, $servicio);

        $datos = $this->validar($request, $request->user(), esAlta: false);

        $this->servicios->actualizar($servicio, $datos);

        return redirect()->route('servicios.show', $servicio)->with('status', 'Datos de la orden actualizados.');
    }

    public function cambiarEstado(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarSucursal($request, $servicio);

        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoServicio::class)],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $this->servicios->cambiarEstado(
            $servicio,
            EstadoServicio::from($datos['estado']),
            $request->user(),
            $datos['comentario'] ?? null,
        );

        return redirect()->route('servicios.show', $servicio)
            ->with('status', 'Estado de la orden actualizado.');
    }

    public function agregarRepuesto(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarSucursal($request, $servicio);

        $datos = $request->validate([
            'producto_id' => ['required', Rule::exists('productos', 'id')],
            'cantidad' => ['required', 'integer', 'min:1'],
        ], [], ['producto_id' => 'repuesto', 'cantidad' => 'cantidad']);

        $this->servicios->agregarRepuesto($servicio, (int) $datos['producto_id'], (int) $datos['cantidad'], $request->user());

        return redirect()->route('servicios.show', $servicio)->with('status', 'Repuesto agregado y descontado del inventario.');
    }

    public function quitarRepuesto(Request $request, Servicio $servicio, ServicioRepuesto $repuesto): RedirectResponse
    {
        $this->autorizarSucursal($request, $servicio);

        abort_unless($repuesto->servicio_id === $servicio->id, 404);

        $this->servicios->quitarRepuesto($servicio, $repuesto, $request->user());

        return redirect()->route('servicios.show', $servicio)->with('status', 'Repuesto quitado y devuelto al inventario.');
    }

    public function imprimir(Request $request, Servicio $servicio): View
    {
        $this->autorizarSucursal($request, $servicio);

        return view('servicios.imprimir', [
            'servicio' => $servicio->load(['cliente', 'sucursal', 'tecnico', 'venta', 'repuestos.producto']),
        ]);
    }

    public function destroy(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarGestion($request, $servicio);

        // Al eliminar la venta de servicio se elimina también el registro del servicio.
        $servicio->venta()->delete();

        return redirect()->route('servicios.index')->with('status', 'Orden eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, User $usuario, bool $esAlta): array
    {
        $reglas = [
            'equipo' => ['required', 'string', 'max:150'],
            'diagnostico' => ['nullable', 'string', 'max:1000'],
            'fecha_recepcion' => ['required', 'date'],
            'fecha_prometida' => ['nullable', 'date', 'after_or_equal:fecha_recepcion'],
            'prioridad' => ['required', Rule::in(array_keys(Servicio::PRIORIDADES))],
            'accesorios_recibidos' => ['nullable', 'string', 'max:500'],
            'mano_de_obra' => ['required', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'garantia_dias' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];

        $atributos = [
            'mano_de_obra' => 'mano de obra',
            'fecha_recepcion' => 'fecha de recepción',
            'fecha_prometida' => 'fecha prometida',
            'accesorios_recibidos' => 'accesorios recibidos',
            'garantia_dias' => 'días de garantía',
        ];

        if (! $esAlta) {
            return $request->validate($reglas, [], $atributos);
        }

        return $request->validate([
            ...$reglas,
            'modo_cliente' => ['required', Rule::in(['existente', 'nuevo'])],
            'cliente_id' => ['nullable', 'required_if:modo_cliente,existente', Rule::exists('clientes', 'id')],
            'cliente_nombre' => ['nullable', 'required_if:modo_cliente,nuevo', 'string', 'max:150'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')],
            'sucursal_id' => [$usuario->esGerente() ? 'required' : 'nullable', Rule::exists('sucursales', 'id')],
            'tecnico_id' => ['nullable', Rule::exists('users', 'id')],
        ], [], [
            ...$atributos,
            'cliente_id' => 'cliente',
            'cliente_nombre' => 'nombre del cliente',
            'cliente_telefono' => 'teléfono',
            'cliente_carnet' => 'carnet',
            'sucursal_id' => 'sucursal',
            'tecnico_id' => 'técnico',
        ]);
    }

    private function sucursales(Request $request)
    {
        $usuario = $request->user();

        return $usuario->esGerente()
            ? Sucursal::where('activa', true)->orderBy('nombre')->get()
            : Sucursal::whereKey($usuario->sucursal_id)->get();
    }

    /**
     * Usuarios con acceso al módulo de servicios (técnicos y gerentes).
     */
    private function tecnicos()
    {
        return User::with('role')
            ->where('activo', true)
            ->get()
            ->filter(fn (User $u) => $u->tienePermiso('servicios'))
            ->sortBy(fn (User $u) => $u->nombre())
            ->values();
    }

    private function autorizarSucursal(Request $request, Servicio $servicio): void
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $servicio->sucursal_id, 403, 'La orden pertenece a otra sucursal.');
    }

    /**
     * Editar o eliminar una orden cerrada (Entregado/Cancelado) es exclusivo del rol Gerente.
     * Ningún otro rol —presente o futuro— puede hacerlo, aunque tenga el permiso de servicios.
     */
    private function autorizarGestion(Request $request, Servicio $servicio): void
    {
        $this->autorizarSucursal($request, $servicio);

        abort_if(
            $servicio->cerrado() && ! $request->user()->esGerente(),
            403,
            'Solo un gerente puede editar o eliminar una orden entregada o cancelada.',
        );
    }
}
