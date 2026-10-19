<?php

namespace App\Http\Requests\Buscador;

use App\Enums\ResultadoBusqueda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del reporte "lo más buscado que no tenemos".
 */
class FiltrarBusquedasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('buscador') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'resultado' => ['nullable', Rule::in([
                ResultadoBusqueda::SinStock->value,
                ResultadoBusqueda::NoExiste->value,
            ])],
        ];
    }
}
