<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Filtros del listado de compras, compartidos por el índice y la exportación CSV.
 */
class FiltrarComprasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('compras') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proveedor_id' => ['nullable', 'integer', Rule::exists('proveedores', 'id')],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'incluir_anuladas' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            if ($validador->errors()->has('desde') || $validador->errors()->has('hasta')) {
                return;
            }

            $desde = $this->input('desde');
            $hasta = $this->input('hasta');

            if ($desde && $hasta && $hasta < $desde) {
                $validador->errors()->add('hasta', 'La fecha «hasta» no puede ser anterior a «desde».');
            }
        });
    }
}
