<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros del listado de proveedores, compartidos por el índice y la exportación CSV.
 */
class FiltrarProveedoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('proveedores') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('buscar')) {
            $this->merge(['buscar' => trim((string) $this->input('buscar'))]);
        }
    }
}
