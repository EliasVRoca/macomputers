<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicio;
use App\Http\Requests\Servicios\ActualizarServicioRequest;
use App\Http\Requests\Servicios\AgregarRepuestoRequest;
use App\Http\Requests\Servicios\CambiarEstadoServicioRequest;
use App\Http\Requests\Servicios\FiltrarServiciosRequest;
use App\Http\Requests\Servicios\GuardarServicioRequest;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\ServicioRepuesto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\ServicioService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicioController extends Controller
{
    public function __construct(private ServicioService $servicios) {}

    public function index(FiltrarServiciosRequest $request): View
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $conteos = Servicio::query()
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('servicios.index', [
            'items' => $this->consulta($usuario, $filtros)->paginate(15)->withQueryString(),
            'conteos' => $conteos,
            'estados' => EstadoServicio::opciones(),
            'prioridades' => Servicio::PRIORIDADES,
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $usuario->esGerente(),
            'filtros' => $filtros,
        ]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        return view('servicios.form', [
            'servicio' => new Servicio(['estado' => EstadoServicio::Recibido->value, 'prioridad' => 'normal']),
            'accion' => route('servicios.store'),
            'metodo' => 'POST',
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'tecnicos' => $usuario->esGerente() ? $this->tecnicos() : collect(),
            'puedeElegirSucursal' => $usuario->esGerente(),
            'puedeElegirTecnico' => $usuario->esGerente(),
            'puedeElegirCliente' => true,
        ]);
    }

    public function store(GuardarServicioRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

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
            'medios' => Venta::MEDIOS_PAGO,
        ]);
    }

    public function edit(Request $request, Servicio $servicio): View
    {
        $this->autorizarGestion($request, $servicio);

        $usuario = $request->user();
        $abierta = ! $servicio->cerrado();
        $esGerente = $usuario->esGerente();

        $clientes = collect();

        if ($abierta) {
            $clientes = Cliente::query()
                ->where('activo', true)
                ->orWhereKey($servicio->cliente_id)
                ->orderBy('nombre')
                ->get();
        }

        return view('servicios.form', [
            'servicio' => $servicio->load(['cliente', 'sucursal', 'venta']),
            'accion' => route('servicios.update', $servicio),
            'metodo' => 'PUT',
            'clientes' => $clientes,
            'sucursales' => $this->sucursales($request),
            'tecnicos' => $esGerente && $abierta ? $this->tecnicos() : collect(),
            'puedeElegirSucursal' => $esGerente && $abierta,
            'puedeElegirTecnico' => $esGerente && $abierta,
            'puedeElegirCliente' => $abierta,
        ]);
    }

    public function update(ActualizarServicioRequest $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarGestion($request, $servicio);

        $datos = $request->validated();

        if (! $request->user()->esGerente()) {
            unset($datos['sucursal_id'], $datos['tecnico_id']);
        }

        if ($servicio->cerrado()) {
            unset($datos['cliente_id'], $datos['sucursal_id'], $datos['tecnico_id']);
        }

        $this->servicios->actualizar($servicio, $datos);

        return redirect()->route('servicios.show', $servicio)->with('status', 'Datos de la orden actualizados.');
    }

    public function cambiarEstado(CambiarEstadoServicioRequest $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarSucursal($request, $servicio);

        $datos = $request->validated();

        $this->servicios->cambiarEstado(
            $servicio,
            EstadoServicio::from($datos['estado']),
            $request->user(),
            $datos['comentario'] ?? null,
            $datos['medio_pago'] ?? null,
        );

        return redirect()->route('servicios.show', $servicio)
            ->with('status', 'Estado de la orden actualizado.');
    }

    public function agregarRepuesto(AgregarRepuestoRequest $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarSucursal($request, $servicio);

        $datos = $request->validated();

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

        $this->servicios->eliminar($servicio, $request->user());

        return redirect()->route('servicios.index')
            ->with('status', 'Orden dada de baja. El historial se conserva.');
    }

    public function export(FiltrarServiciosRequest $request): StreamedResponse
    {
        $filas = $this->consulta($request->user(), $request->validated())->lazy()
            ->map(fn (Servicio $servicio): array => [
                $servicio->numero,
                $servicio->fecha_recepcion?->format('d/m/Y'),
                $servicio->fecha_entrega?->format('d/m/Y') ?? '—',
                $servicio->cliente?->nombre ?? 'Sin cliente',
                $servicio->equipo,
                $servicio->sucursal?->nombre,
                $servicio->tecnico?->nombre() ?? '—',
                Servicio::PRIORIDADES[$servicio->prioridad] ?? $servicio->prioridad,
                $servicio->estadoEnum()->etiqueta(),
                number_format((float) ($servicio->venta?->total ?? 0), 2, '.', ''),
                $servicio->venta?->medio_pago ?? '—',
            ]);

        return Csv::descargar(
            'servicios-'.now()->format('Ymd-His').'.csv',
            ['OT', 'Recepción', 'Entrega', 'Cliente', 'Equipo', 'Sucursal', 'Técnico', 'Prioridad', 'Estado', 'Total', 'Medio de pago'],
            $filas,
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(User $usuario, array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');

        return Servicio::query()
            ->with(['cliente', 'sucursal', 'tecnico', 'venta'])
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->when($filtros['estado'] ?? null, fn (Builder $q) => $q->where('estado', $filtros['estado']))
            ->when($filtros['prioridad'] ?? null, fn (Builder $q) => $q->where('prioridad', $filtros['prioridad']))
            ->when($usuario->esGerente() && ($filtros['sucursal_id'] ?? null), fn (Builder $q) => $q->where('sucursal_id', (int) $filtros['sucursal_id']))
            ->when($filtros['mias'] ?? false, fn (Builder $q) => $q->where('tecnico_id', $usuario->id))
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('numero', 'like', "%{$buscar}%")
                ->orWhere('equipo', 'like', "%{$buscar}%")
                ->orWhereHas('cliente', fn (Builder $c) => $c->where('nombre', 'like', "%{$buscar}%"))))
            ->when($filtros['desde'] ?? null, fn (Builder $q) => $q->whereDate('fecha_recepcion', '>=', $filtros['desde']))
            ->when($filtros['hasta'] ?? null, fn (Builder $q) => $q->whereDate('fecha_recepcion', '<=', $filtros['hasta']))
            ->latest('id');
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
            'Solo un gerente puede editar o dar de baja una orden entregada o cancelada.',
        );
    }
}
