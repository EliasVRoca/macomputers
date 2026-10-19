<?php

namespace App\Http\Requests\Clientes;

use App\Http\Requests\Concerns\SaneaTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de un cliente (RF17 · CU15).
 *
 * Sanea el texto libre y valida el carnet único. La baja es lógica (`activo`).
 */
class GuardarClienteRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('clientes') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cliente = $this->route('cliente');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')->ignore($cliente?->id)],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'telefono' => $this->limpiarTexto($this->input('telefono')),
            'direccion' => $this->limpiarTexto($this->input('direccion')),
            'carnet' => $this->limpiarTexto($this->input('carnet')),
            'activo' => $this->has('activo') ? $this->boolean('activo') : true,
        ]);
    }
}
