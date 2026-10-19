<?php

namespace App\Http\Requests\Productos;

use App\Enums\CondicionProducto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del listado de productos, compartidos por el índice y la exportación CSV.
 */
class FiltrarProductosRequest extends FormRequest
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
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria_id' => ['nullable', 'integer', Rule::exists('categorias', 'id')],
            'marca' => ['nullable', 'string', 'max:100'],
            'condicion' => ['nullable', Rule::in(CondicionProducto::valores())],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['categoria_id' => 'categoría'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'buscar' => $this->has('buscar') ? trim((string) $this->input('buscar')) : null,
            'marca' => $this->has('marca') ? trim((string) $this->input('marca')) : null,
        ]);
    }
}
