<?php

namespace App\Services;

use App\Enums\TipoMovimiento;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Altas y anulaciones de compras. Único punto donde se arma el ingreso de
 * mercadería (suma stock vía StockService) y su reverso, para que el
 * controlador quede delgado y la lógica sea reutilizable y testeable.
 */
class CompraService
{
    public function __construct(private StockService $stock) {}

    /**
     * Registra la compra: crea la cabecera y los ítems, suma el stock a la
     * sucursal, actualiza el costo del producto (si viene informado) y calcula
     * el total en el servidor.
     *
     * @param  array<int, array{producto_id: int|string, cantidad: int|string, precio_compra: int|float|string}>  $items
     */
    public function registrar(int $proveedorId, int $sucursalId, array $items, User $usuario, string $fecha): Compra
    {
        return DB::transaction(function () use ($proveedorId, $sucursalId, $items, $usuario, $fecha): Compra {
            $compra = Compra::create([
                'proveedor_id' => $proveedorId,
                'sucursal_id' => $sucursalId,
                'user_id' => $usuario->id,
                'fecha' => $fecha,
                'total' => 0,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (int) $item['cantidad'];
                $precio = round((float) $item['precio_compra'], 2);
                $subtotal = round($cantidad * $precio, 2);
                $total += $subtotal;

                CompraItem::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'precio_compra' => $precio,
                    'subtotal' => $subtotal,
                ]);

                $this->stock->mover($productoId, $sucursalId, $cantidad, TipoMovimiento::Compra->value, "Compra #{$compra->id}", $usuario->id);

                // Deja el costo del catálogo al día, salvo que venga en 0 (default del formulario).
                if ($precio > 0) {
                    Producto::query()->whereKey($productoId)->update(['precio_compra' => $precio]);
                }
            }

            $compra->update(['total' => $total]);

            return $compra;
        });
    }

    /**
     * Anula una compra: revierte el stock que ingresó. Se prohíbe si el saldo
     * ya no alcanza (la mercadería se vendió o traspasó) para no dejar el
     * inventario inconsistente; en ese caso no se mueve nada.
     */
    public function anular(Compra $compra, User $usuario, ?string $motivo = null): void
    {
        if ($compra->estaAnulada()) {
            throw ValidationException::withMessages([
                'compra' => 'Esta compra ya fue anulada.',
            ]);
        }

        $compra->load('items.producto');

        $faltantes = $compra->items->filter(fn (CompraItem $item): bool => $this->stock->disponible((int) $item->producto_id, (int) $compra->sucursal_id) < $item->cantidad);

        if ($faltantes->isNotEmpty()) {
            $nombres = $faltantes
                ->map(fn (CompraItem $item): string => $item->producto?->nombre ?? "producto #{$item->producto_id}")
                ->unique()
                ->implode(', ');

            throw ValidationException::withMessages([
                'compra' => "No se puede anular: el stock ya no alcanza para revertir {$nombres}.",
            ]);
        }

        DB::transaction(function () use ($compra, $usuario, $motivo): void {
            $referencia = "Anulación compra #{$compra->id}";

            foreach ($compra->items as $item) {
                $this->stock->mover((int) $item->producto_id, (int) $compra->sucursal_id, -(int) $item->cantidad, TipoMovimiento::AnulacionCompra->value, $referencia, $usuario->id);
            }

            $compra->update([
                'anulada_at' => now(),
                'anulada_por_id' => $usuario->id,
                'motivo_anulacion' => $motivo,
            ]);
        });
    }
}
