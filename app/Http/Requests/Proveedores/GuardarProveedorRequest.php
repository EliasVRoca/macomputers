<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de un proveedor (RF10 · CU8).
 *
 * Tipa y sanea la entrada: textos opcionales salvo el nombre, todos limpiados
 * (sin etiquetas ni caracteres de control).
 */
class GuardarProveedorRequest extends FormRequest
{
    /** Caracteres permitidos en el texto (letras, números, espacio y puntuación común). */
    private const PATRON_TEXTO = '/^[\p{L}\p{N}\s.,;:\-_\/#()°%+*\'"&@!?¡¿×·|]*$/u';

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('proveedores') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150', 'regex:'.self::PATRON_TEXTO],
            'contacto' => ['nullable', 'string', 'max:150', 'regex:'.self::PATRON_TEXTO],
            'direccion' => ['nullable', 'string', 'max:255', 'regex:'.self::PATRON_TEXTO],
            'pais_origen' => ['nullable', 'string', 'max:100', 'regex:'.self::PATRON_TEXTO],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'contacto' => $this->limpiarTexto($this->input('contacto')),
            'direccion' => $this->limpiarTexto($this->input('direccion')),
            'pais_origen' => $this->limpiarTexto($this->input('pais_origen')),
        ]);
    }

    private function limpiarTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = strip_tags(trim((string) $valor));
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

        return $texto === '' ? null : $texto;
    }
}
