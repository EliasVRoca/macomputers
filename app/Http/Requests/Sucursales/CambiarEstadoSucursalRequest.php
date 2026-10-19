<?php

namespace App\Http\Requests\Sucursales;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Activación/desactivación rápida de una sucursal desde el listado (baja lógica).
 */
class CambiarEstadoSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('sucursales') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['activa' => ['required', 'boolean']];
    }
}
