<?php

namespace App\Services;

use App\Enums\EstadoServicio;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\ServicioHistorial;
use App\Models\ServicioRepuesto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orden de trabajo del servicio técnico (RF16 / CU14).
 *
 * La orden siempre se registra como una venta de servicio. Los repuestos que se
 * instalan consumen inventario real de la sucursal del taller a través de StockService.
 */
class ServicioService
{
    public function __construct(private StockService $stock) {}

    /**
     * Crea la orden: venta de servicio + ítem + servicio + historial inicial.
     */
    public function crear(array $datos, User $usuario): Servicio
    {
        return DB::transaction(function () use ($datos, $usuario) {
            $clienteId = $this->resolverCliente($datos);
            $sucursalId = (int) $datos['sucursal_id'];
            $manoDeObra = round((float) ($datos['mano_de_obra'] ?? 0), 2);

            $venta = Venta::create([
                'sucursal_id' => $sucursalId,
                'cliente_id' => $clienteId,
                'user_id' => $usuario->id,
                'tipo' => Venta::TIPO_SERVICIO,
                'fecha' => now(),
                'medio_pago' => null,
                'total' => $manoDeObra,
                'estado' => Venta::ESTADO_ACTIVA,
            ]);

            VentaItem::create([
                'venta_id' => $venta->id,
                'producto_id' => null,
                'descripcion' => 'Servicio técnico: '.$datos['equipo'],
                'cantidad' => 1,
                'precio_unitario' => $manoDeObra,
                'subtotal' => $manoDeObra,
            ]);

            $servicio = Servicio::create([
                'venta_id' => $venta->id,
                'cliente_id' => $clienteId,
                'sucursal_id' => $sucursalId,
                'tecnico_id' => $datos['tecnico_id'] ?? $usuario->id,
                'equipo' => $datos['equipo'],
                'diagnostico' => $datos['diagnostico'] ?? null,
                'estado' => EstadoServicio::Recibido->value,
                'fecha_recepcion' => $datos['fecha_recepcion'] ?? now()->toDateString(),
                'fecha_prometida' => $datos['fecha_prometida'] ?? null,
                'prioridad' => $datos['prioridad'] ?? 'normal',
                'accesorios_recibidos' => $datos['accesorios_recibidos'] ?? null,
                'mano_de_obra' => $manoDeObra,
                'descuento' => 0,
                'garantia_dias' => (int) ($datos['garantia_dias'] ?? 0),
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $servicio->update(['numero' => 'OT-'.str_pad((string) $servicio->id, 6, '0', STR_PAD_LEFT)]);

            $this->registrarHistorial($servicio, null, EstadoServicio::Recibido, $usuario, 'Recepción del equipo.');

            return $servicio;
        });
    }

    /**
     * Edición completa de los datos de la orden. No modifica el estado.
     */
    public function actualizar(Servicio $servicio, array $datos): Servicio
    {
        $servicio->update([
            'equipo' => $datos['equipo'],
            'diagnostico' => $datos['diagnostico'] ?? null,
            'fecha_recepcion' => $datos['fecha_recepcion'] ?? $servicio->fecha_recepcion,
            'fecha_prometida' => $datos['fecha_prometida'] ?? null,
            'prioridad' => $datos['prioridad'] ?? $servicio->prioridad,
            'accesorios_recibidos' => $datos['accesorios_recibidos'] ?? null,
            'mano_de_obra' => round((float) ($datos['mano_de_obra'] ?? 0), 2),
            'descuento' => round((float) ($datos['descuento'] ?? 0), 2),
            'garantia_dias' => (int) ($datos['garantia_dias'] ?? 0),
            'observaciones' => $datos['observaciones'] ?? null,
        ]);

        return $this->sincronizarVenta($servicio->fresh());
    }

    /**
     * Cambia el estado validando la transición y dejando constancia en el historial.
     */
    public function cambiarEstado(Servicio $servicio, EstadoServicio $nuevo, User $usuario, ?string $comentario = null): Servicio
    {
        $actual = $servicio->estadoEnum();

        if ($actual === $nuevo) {
            throw ValidationException::withMessages([
                'estado' => "La orden ya se encuentra en estado «{$nuevo->etiqueta()}».",
            ]);
        }

        if (! $actual->puedeTransicionarA($nuevo)) {
            throw ValidationException::withMessages([
                'estado' => "No se puede pasar de «{$actual->etiqueta()}» a «{$nuevo->etiqueta()}».",
            ]);
        }

        if ($nuevo === EstadoServicio::Listo && blank($servicio->diagnostico)) {
            throw ValidationException::withMessages([
                'estado' => 'Registre el diagnóstico del trabajo antes de marcar la orden como lista.',
            ]);
        }

        return DB::transaction(function () use ($servicio, $actual, $nuevo, $usuario, $comentario) {
            $cambios = ['estado' => $nuevo->value];

            if ($nuevo === EstadoServicio::Entregado) {
                $cambios['fecha_entrega'] = now()->toDateString();
            }

            if ($nuevo === EstadoServicio::Cancelado) {
                $this->liberarRepuestos($servicio, $usuario);
            }

            $servicio->update($cambios);

            $this->registrarHistorial($servicio, $actual, $nuevo, $usuario, $comentario);

            return $servicio;
        });
    }

    /**
     * Agrega un repuesto a la orden consumiendo inventario de la sucursal del taller.
     */
    public function agregarRepuesto(Servicio $servicio, int $productoId, int $cantidad, User $usuario): ServicioRepuesto
    {
        $this->asegurarEditable($servicio);

        return DB::transaction(function () use ($servicio, $productoId, $cantidad, $usuario) {
            $producto = Producto::findOrFail($productoId);
            $precio = round((float) $producto->precio_venta, 2);

            $this->stock->mover(
                $producto->id,
                $servicio->sucursal_id,
                -$cantidad,
                'consumo_servicio',
                "OT {$servicio->numero}",
                $usuario->id,
            );

            $repuesto = $servicio->repuestos()->where('producto_id', $producto->id)->first();

            if ($repuesto) {
                $nuevaCantidad = $repuesto->cantidad + $cantidad;
                $repuesto->update([
                    'cantidad' => $nuevaCantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => round($nuevaCantidad * $precio, 2),
                ]);
            } else {
                $repuesto = $servicio->repuestos()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => round($cantidad * $precio, 2),
                ]);
            }

            $this->sincronizarVenta($servicio->fresh());

            return $repuesto;
        });
    }

    /**
     * Quita un repuesto de la orden y devuelve su existencia al inventario.
     */
    public function quitarRepuesto(Servicio $servicio, ServicioRepuesto $repuesto, User $usuario): void
    {
        $this->asegurarEditable($servicio);

        DB::transaction(function () use ($servicio, $repuesto, $usuario) {
            $this->stock->mover(
                $repuesto->producto_id,
                $servicio->sucursal_id,
                (int) $repuesto->cantidad,
                'devolucion_servicio',
                "OT {$servicio->numero}",
                $usuario->id,
            );

            $repuesto->delete();

            $this->sincronizarVenta($servicio->fresh());
        });
    }

    /**
     * Recalcula el total de la orden y lo refleja en la venta de servicio.
     */
    private function sincronizarVenta(Servicio $servicio): Servicio
    {
        $total = $servicio->totalCalculado();

        $venta = $servicio->venta;
        $venta->update(['total' => $total]);

        $venta->items()->update([
            'descripcion' => 'Servicio técnico: '.$servicio->equipo,
            'cantidad' => 1,
            'precio_unitario' => $total,
            'subtotal' => $total,
        ]);

        return $servicio;
    }

    /**
     * Devuelve al inventario todos los repuestos de la orden (al cancelarla).
     */
    private function liberarRepuestos(Servicio $servicio, User $usuario): void
    {
        foreach ($servicio->repuestos as $repuesto) {
            $this->stock->mover(
                $repuesto->producto_id,
                $servicio->sucursal_id,
                (int) $repuesto->cantidad,
                'devolucion_servicio',
                "OT {$servicio->numero} cancelada",
                $usuario->id,
            );
        }
    }

    private function registrarHistorial(Servicio $servicio, ?EstadoServicio $anterior, EstadoServicio $nuevo, User $usuario, ?string $comentario): void
    {
        ServicioHistorial::create([
            'servicio_id' => $servicio->id,
            'user_id' => $usuario->id,
            'estado_anterior' => $anterior?->value,
            'estado_nuevo' => $nuevo->value,
            'comentario' => $comentario,
        ]);
    }

    private function asegurarEditable(Servicio $servicio): void
    {
        if ($servicio->cerrado()) {
            throw ValidationException::withMessages([
                'repuesto' => "La orden está «{$servicio->estadoEnum()->etiqueta()}» y ya no admite cambios de repuestos.",
            ]);
        }
    }

    private function resolverCliente(array $datos): int
    {
        $clienteId = $datos['cliente_id'] ?? null;

        if (($datos['modo_cliente'] ?? 'existente') === 'nuevo' || ! $clienteId) {
            return Cliente::create([
                'nombre' => $datos['cliente_nombre'],
                'telefono' => $datos['cliente_telefono'] ?? null,
                'carnet' => $datos['cliente_carnet'] ?? null,
            ])->id;
        }

        return (int) $clienteId;
    }
}
