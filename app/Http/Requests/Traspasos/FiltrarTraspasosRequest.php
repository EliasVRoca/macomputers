<?php

namespace App\Http\Requests\Traspasos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Filtros del listado y de la exportación de traspasos.
 */
class FiltrarTraspasosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('traspasos') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'sucursal_origen_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'sucursal_destino_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'producto_id' => ['nullable', 'integer', Rule::exists('productos', 'id')],
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
