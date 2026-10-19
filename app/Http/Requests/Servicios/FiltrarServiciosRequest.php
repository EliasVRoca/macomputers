<?php

namespace App\Http\Requests\Servicios;

use App\Enums\EstadoServicio;
use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Servicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del listado y del export de órdenes de trabajo.
 */
class FiltrarServiciosRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('servicios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoServicio::class)],
            'prioridad' => ['nullable', Rule::in(array_keys(Servicio::PRIORIDADES))],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'mias' => ['nullable', 'boolean'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'buscar' => $this->limpiarTexto($this->input('buscar')),
        ]);
    }
}
