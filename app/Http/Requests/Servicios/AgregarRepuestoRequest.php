<?php

namespace App\Http\Requests\Servicios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de un repuesto en la orden (consume inventario de la sucursal del taller).
 */
class AgregarRepuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('servicios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'producto_id' => 'repuesto',
            'cantidad' => 'cantidad',
        ];
    }
}
