<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
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
        if (! $this->user()?->esGerente()) {
            return [];
        }

        return [
            'sucursal_id' => [
                'nullable',
                'integer',
                Rule::exists('sucursales', 'id')->where('activa', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['sucursal_id' => 'sucursal'];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sucursal_id') && blank($this->input('sucursal_id'))) {
            $this->merge(['sucursal_id' => null]);
        }
    }
}
