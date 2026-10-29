<?php

namespace App\Http\Requests\Buscador;

use App\Enums\CondicionProducto;
use App\Http\Requests\Concerns\SaneaTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros iniciales de la pantalla del buscador (término opcional).
 */
class IndexBuscadorRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('buscador') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'termino' => ['nullable', 'string', 'max:300'],
            'categoria_id' => ['nullable', 'integer', Rule::exists('categorias', 'id')],
            'marca' => ['nullable', 'string', 'max:100'],
            'condicion' => ['nullable', Rule::in(CondicionProducto::valores())],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'solo_stock' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'termino' => $this->limpiarTexto($this->input('termino')),
            'marca' => $this->limpiarTexto($this->input('marca')),
        ]);
    }
}
