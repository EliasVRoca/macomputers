<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Atajo "Traer de la otra sucursal": crea un traspaso desde la sucursal que
 * tiene el stock hacia la sucursal del usuario.
 */
class RegistrarTraspasoRapidoRequest extends FormRequest
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
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'sucursal_origen_id' => ['required', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'sucursal_destino_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'cantidad' => ['required', 'integer', 'min:1'],
        ];
    }
}
