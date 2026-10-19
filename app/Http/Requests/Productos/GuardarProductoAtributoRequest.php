<?php

namespace App\Http\Requests\Productos;

use App\Http\Requests\Concerns\SaneaTexto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta o actualización de un atributo de la ficha técnica del producto.
 */
class GuardarProductoAtributoRequest extends FormRequest
{
    use SaneaTexto;

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
            'atributo' => ['required', 'string', 'max:50'],
            'valor' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $atributo = $this->limpiarTexto($this->input('atributo'));

        $this->merge([
            'atributo' => $atributo !== null ? mb_strtolower($atributo) : null,
            'valor' => $this->limpiarTexto($this->input('valor')),
        ]);
    }
}
