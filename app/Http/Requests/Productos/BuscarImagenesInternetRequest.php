<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;

class BuscarImagenesInternetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('productos') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['q' => 'búsqueda'];
    }

    protected function prepareForValidation(): void
    {
        $texto = trim((string) $this->input('q'));
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

        $this->merge(['q' => $texto]);
    }
}
