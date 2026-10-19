<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del módulo de Disponibilidad (consulta por sucursal), compartidos por
 * el índice y por la exportación CSV.
 */
class FiltrarDisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('disponibilidad') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('buscar')) {
            $this->merge(['buscar' => trim((string) $this->input('buscar'))]);
        }
    }
}
