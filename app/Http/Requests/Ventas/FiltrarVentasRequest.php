<?php

namespace App\Http\Requests\Ventas;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Venta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del listado de ventas, compartidos por el índice y la exportación CSV.
 */
class FiltrarVentasRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('ventas') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'estado' => ['nullable', Rule::in([Venta::ESTADO_ACTIVA, Venta::ESTADO_ANULADA])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['cliente_id' => 'cliente', 'sucursal_id' => 'sucursal'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'buscar' => $this->limpiarTexto($this->input('buscar')),
        ]);
    }
}
