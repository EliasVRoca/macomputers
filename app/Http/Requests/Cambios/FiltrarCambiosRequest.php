<?php

namespace App\Http\Requests\Cambios;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Cambio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Filtros del listado de cambios, compartidos por el índice y la exportación CSV.
 */
class FiltrarCambiosRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('cambios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', Rule::in([Cambio::TIPO_CAMBIO, Cambio::TIPO_DEVOLUCION])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'buscar' => $this->limpiarTexto($this->input('buscar')),
        ]);
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
