<?php

namespace App\Http\Requests\Encargos;

use App\Http\Requests\Concerns\SaneaTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de un encargo (RF14 · CU12): producto a conseguir, con anticipo y saldo.
 *
 * El `producto_id` es opcional (un encargo puede ser de algo fuera del catálogo).
 * El saldo se calcula en el servidor como `total − anticipo`.
 */
class GuardarEncargoRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('encargos') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $esGerente = $this->user()?->esGerente() === true;

        return [
            'modo_cliente' => ['nullable', Rule::in(['existente', 'nuevo'])],
            'cliente_id' => ['nullable', 'required_if:modo_cliente,existente', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'cliente_nombre' => ['nullable', 'required_if:modo_cliente,nuevo', 'string', 'max:150'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')],
            'sucursal_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'producto_id' => ['nullable', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'descripcion' => ['nullable', 'required_without:producto_id', 'string', 'max:255'],
            'total' => ['required', 'numeric', 'min:0'],
            'anticipo' => ['required', 'numeric', 'min:0'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sucursal_id' => 'sucursal',
            'cliente_id' => 'cliente',
            'cliente_nombre' => 'nombre del cliente',
            'cliente_telefono' => 'teléfono',
            'cliente_carnet' => 'carnet',
            'producto_id' => 'producto',
            'descripcion' => 'descripción del producto',
            'anticipo' => 'anticipo',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'modo_cliente' => $this->input('modo_cliente') ?: 'existente',
            'cliente_nombre' => $this->limpiarTexto($this->input('cliente_nombre')),
            'cliente_telefono' => $this->limpiarTexto($this->input('cliente_telefono')),
            'cliente_carnet' => $this->limpiarTexto($this->input('cliente_carnet')),
            'descripcion' => $this->limpiarTexto($this->input('descripcion')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            if ((float) $this->input('anticipo') > (float) $this->input('total')) {
                $validador->errors()->add('anticipo', 'El anticipo no puede ser mayor al total del encargo.');
            }
        });
    }
}
