<?php

namespace App\Services;

use App\Models\Cliente;

/**
 * Resolución del cliente de una operación (patrón `modo_cliente`).
 *
 * Compartido por Ventas, Apartados, Encargos y Servicio técnico: si llega
 * `modo_cliente = nuevo` (o no hay `cliente_id`) se da de alta el cliente en
 * línea; en caso contrario se usa el cliente existente.
 */
class ClienteService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function resolver(array $datos): ?int
    {
        $clienteId = $datos['cliente_id'] ?? null;

        if (($datos['modo_cliente'] ?? 'existente') === 'nuevo') {
            return Cliente::create([
                'nombre' => $datos['cliente_nombre'],
                'telefono' => $datos['cliente_telefono'] ?? null,
                'carnet' => $datos['cliente_carnet'] ?? null,
            ])->id;
        }

        return $clienteId ? (int) $clienteId : null;
    }
}
