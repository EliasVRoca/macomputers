<?php

namespace App\Http\Requests\Categorias;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de una categoría del catálogo (RF5 · CU4).
 *
 * Tipa y sanea la entrada: nombre único y descripción opcional, ambos con el
 * texto libre limpiado (sin etiquetas ni caracteres de control).
 */
class GuardarCategoriaRequest extends FormRequest
{
    /** Caracteres permitidos en el texto (letras, números, espacio y puntuación común). */
    private const PATRON_TEXTO = '/^[\p{L}\p{N}\s.,;:\-_\/#()°%+*\'"&@!?¡¿×·|]*$/u';

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('categorias') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoria = $this->route('categoria');

        return [
            'nombre' => ['required', 'string', 'max:100', 'regex:'.self::PATRON_TEXTO, Rule::unique('categorias', 'nombre')->ignore($categoria?->id)],
            'descripcion' => ['nullable', 'string', 'max:255', 'regex:'.self::PATRON_TEXTO],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'descripcion' => $this->limpiarTexto($this->input('descripcion')),
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
