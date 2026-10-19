<?php

namespace App\Http\Requests\Productos;

use App\Http\Requests\Concerns\SaneaTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Búsqueda remota del selector de productos de ventas, apartados y encargos.
 */
class BuscarOpcionesProductoRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => 'término de búsqueda',
            'sucursal_id' => 'sucursal',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->limpiarTexto($this->input('q')),
        ]);
    }
}
