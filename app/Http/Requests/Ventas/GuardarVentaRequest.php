<?php

namespace App\Http\Requests\Ventas;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Venta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de una venta de productos (RF12 · CU10).
 *
 * Tipa y sanea la entrada: ids validados, medio de pago en lista blanca, al
 * menos un ítem y, opcionalmente, el alta del cliente en línea (patrón
 * `modo_cliente`, igual que el servicio técnico).
 */
class GuardarVentaRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('ventas') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $esGerente = $this->user()?->esGerente() === true;

        return [
            'modo_cliente' => ['nullable', Rule::in(['existente', 'nuevo'])],
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'cliente_nombre' => ['nullable', 'required_if:modo_cliente,nuevo', 'string', 'max:150'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')],
            'sucursal_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'medio_pago' => ['required', Rule::in(Venta::MEDIOS_PAGO)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Agregue al menos un producto a la venta.',
            'items.min' => 'Agregue al menos un producto a la venta.',
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
            'medio_pago' => 'medio de pago',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'modo_cliente' => $this->input('modo_cliente') ?: 'existente',
            'cliente_nombre' => $this->limpiarTexto($this->input('cliente_nombre')),
            'cliente_telefono' => $this->limpiarTexto($this->input('cliente_telefono')),
            'cliente_carnet' => $this->limpiarTexto($this->input('cliente_carnet')),
        ]);
    }
}
