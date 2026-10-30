<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'agrupacion' => ['nullable', Rule::in(['dia', 'semana', 'mes'])],
        ];

        if ($this->user()?->esGerente()) {
            $rules['sucursal_id'] = [
                'nullable',
                'integer',
                Rule::exists('sucursales', 'id')->where('activa', true),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sucursal_id' => 'sucursal',
            'desde' => 'fecha desde',
            'hasta' => 'fecha hasta',
            'agrupacion' => 'agrupación',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sucursal_id') && blank($this->input('sucursal_id'))) {
            $this->merge(['sucursal_id' => null]);
        }
    }
}
