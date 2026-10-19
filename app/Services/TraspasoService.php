<?php

namespace App\Services;

use App\Models\Traspaso;
use App\Models\TraspasoItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creación de traspasos entre sucursales. Único punto donde se arma un traspaso
 * con sus ítems y se mueve el stock (vía StockService), para que tanto el
 * formulario de Traspasos como el atajo "Traer de la otra sucursal" del módulo
 * de Inventario compartan la misma lógica.
 */
class TraspasoService
{
    public function __construct(private StockService $stock) {}

    /**
     * @param  array<int, array{producto_id: int|string, cantidad: int|string}>  $items
     */
    public function crear(int $origenId, int $destinoId, array $items, User $usuario, ?string $fecha = null): Traspaso
    {
        if ($origenId === $destinoId) {
            throw ValidationException::withMessages([
                'sucursal_destino_id' => 'La sucursal de origen y la de destino son la misma.',
            ]);
        }

        return DB::transaction(function () use ($origenId, $destinoId, $items, $usuario, $fecha): Traspaso {
            $traspaso = Traspaso::create([
                'sucursal_origen_id' => $origenId,
                'sucursal_destino_id' => $destinoId,
                'user_id' => $usuario->id,
                'fecha' => $fecha ?? now()->toDateString(),
            ]);

            foreach ($items as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (int) $item['cantidad'];
                $referencia = "Traspaso #{$traspaso->id}";

                TraspasoItem::create([
                    'traspaso_id' => $traspaso->id,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                ]);

                $this->stock->mover($productoId, $origenId, -$cantidad, 'traspaso_salida', $referencia, $usuario->id);
                $this->stock->mover($productoId, $destinoId, $cantidad, 'traspaso_entrada', $referencia, $usuario->id);
            }

            return $traspaso;
        });
    }
}
