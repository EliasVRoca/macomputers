<?php

namespace App\Http\Requests\Usuarios;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Activación/desactivación rápida de un usuario desde el listado (baja lógica).
 */
class CambiarEstadoUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('usuarios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['activo' => ['required', 'boolean']];
    }
}
