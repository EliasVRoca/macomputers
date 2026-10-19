<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único punto de escritura del stock por sucursal (RNF6).
 * Cada cambio bloquea la fila, valida que no quede negativa y deja registro en el kardex.
 */
class StockService
{
    public function disponible(int $productoId, int $sucursalId): int
    {
        return (int) (Inventario::query()
            ->where('producto_id', $productoId)
            ->where('sucursal_id', $sucursalId)
            ->value('cantidad') ?? 0);
    }

    /**
     * Suma o resta stock. $delta lleva signo: positivo ingresa, negativo sale.
     */
    public function mover(int $productoId, int $sucursalId, int $delta, string $tipo, ?string $referencia = null, ?int $userId = null): void
    {
        if ($delta === 0) {
            return;
        }

        DB::transaction(function () use ($productoId, $sucursalId, $delta, $tipo, $referencia, $userId) {
            $inventario = Inventario::query()
                ->where('producto_id', $productoId)
                ->where('sucursal_id', $sucursalId)
                ->lockForUpdate()
                ->first();

            if (! $inventario) {
                try {
                    Inventario::create([
                        'producto_id' => $productoId,
                        'sucursal_id' => $sucursalId,
                        'cantidad' => 0,
                    ]);
                } catch (QueryException $e) {
                    $existe = Inventario::query()
                        ->where('producto_id', $productoId)
                        ->where('sucursal_id', $sucursalId)
                        ->exists();

                    if (! $existe) {
                        throw $e;
                    }
                }

                $inventario = Inventario::query()
                    ->where('producto_id', $productoId)
                    ->where('sucursal_id', $sucursalId)
                    ->lockForUpdate()
                    ->first();
            }

            $nuevoSaldo = $inventario->cantidad + $delta;

            if ($nuevoSaldo < 0) {
                $nombre = Producto::query()->whereKey($productoId)->value('nombre') ?? 'el producto';

                throw ValidationException::withMessages([
                    'stock' => "Stock insuficiente de {$nombre}. Disponible en la sucursal: {$inventario->cantidad}.",
                ]);
            }

            $inventario->update(['cantidad' => $nuevoSaldo]);

            MovimientoInventario::create([
                'producto_id' => $productoId,
                'sucursal_id' => $sucursalId,
                'user_id' => $userId,
                'tipo' => $tipo,
                'cantidad' => $delta,
                'saldo' => $nuevoSaldo,
                'referencia' => $referencia,
            ]);
        });
    }
}
