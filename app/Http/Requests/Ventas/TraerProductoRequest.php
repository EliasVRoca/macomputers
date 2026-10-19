<?php

namespace App\Http\Requests\Ventas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Atajo "traer de la otra sucursal" desde el formulario de venta: crea un
 * traspaso inmediato hacia la sucursal de la venta.
 */
class TraerProductoRequest extends FormRequest
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
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'sucursal_origen_id' => ['required', 'integer', Rule::exists('sucursales', 'id')],
            'sucursal_destino_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')],
            'cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'producto_id' => 'producto',
            'sucursal_origen_id' => 'sucursal de origen',
            'sucursal_destino_id' => 'sucursal de destino',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            $destino = $this->user()->esGerente()
                ? (int) $this->input('sucursal_destino_id')
                : (int) $this->user()->sucursal_id;

            if ((int) $this->input('sucursal_origen_id') === $destino) {
                $validador->errors()->add('sucursal_origen_id', 'La sucursal de origen y la de destino son la misma.');
            }
        });
    }
}
