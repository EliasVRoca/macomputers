<?php

namespace App\Http\Requests\Inventario;

use App\Enums\TipoMovimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Filtros del módulo de Inventario (existencias y kardex), compartidos por el
 * índice y por las exportaciones CSV.
 */
class FiltrarInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('inventario') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'producto_id' => ['nullable', 'integer', Rule::exists('productos', 'id')],
            'tipo' => ['nullable', Rule::in(TipoMovimiento::valores())],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'bajo_minimo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('buscar')) {
            $this->merge(['buscar' => trim((string) $this->input('buscar'))]);
        }
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
