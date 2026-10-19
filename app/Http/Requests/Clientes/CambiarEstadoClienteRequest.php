<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Activación/desactivación rápida de un cliente desde el listado (baja lógica).
 */
class CambiarEstadoClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('clientes') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['activo' => ['required', 'boolean']];
    }
}
