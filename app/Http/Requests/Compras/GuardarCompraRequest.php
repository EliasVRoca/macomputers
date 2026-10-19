<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de una compra: tipa y valida la cabecera y las líneas de mercadería
 * recibida. La sucursal es obligatoria solo para el gerente; el resto queda
 * forzado a la suya en el controlador.
 */
class GuardarCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('compras') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $esGerente = $this->user()?->esGerente() === true;

        return [
            'proveedor_id' => ['required', 'integer', Rule::exists('proveedores', 'id')],
            'sucursal_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_compra' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Agregue al menos un producto a la compra.',
            'items.min' => 'Agregue al menos un producto a la compra.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'proveedor_id' => 'proveedor',
            'sucursal_id' => 'sucursal',
        ];
    }
}
