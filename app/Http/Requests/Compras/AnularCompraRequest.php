<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Anulación de una compra: solo un motivo de texto libre opcional, saneado.
 */
class AnularCompraRequest extends FormRequest
{
    /** Caracteres permitidos en el motivo (letras, números, espacio y puntuación común). */
    private const PATRON_MOTIVO = '/^[\p{L}\p{N}\s.,\-_\/#()°%:]*$/u';

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('compras') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:150', 'regex:'.self::PATRON_MOTIVO],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['motivo' => $this->limpiarTexto($this->input('motivo'))]);
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
