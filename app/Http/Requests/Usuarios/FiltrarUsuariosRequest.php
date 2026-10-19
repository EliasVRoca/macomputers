<?php

namespace App\Http\Requests\Usuarios;

use App\Http\Requests\Concerns\SaneaTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del listado de usuarios.
 */
class FiltrarUsuariosRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('usuarios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'buscar' => $this->limpiarTexto($this->input('buscar')),
        ]);
    }
}
