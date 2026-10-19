<?php

namespace App\Http\Requests\Traspasos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de un traspaso entre sucursales (varios productos).
 */
class GuardarTraspasoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('traspasos') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $esGerente = $this->user()?->esGerente() === true;

        return [
            'sucursal_origen_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')],
            'sucursal_destino_id' => ['required', 'integer', Rule::exists('sucursales', 'id')],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Agregue al menos un producto al traspaso.',
            'items.min' => 'Agregue al menos un producto al traspaso.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sucursal_origen_id' => 'sucursal de origen',
            'sucursal_destino_id' => 'sucursal de destino',
            'items.*.producto_id' => 'producto',
            'items.*.cantidad' => 'cantidad',
        ];
    }
}
