<?php

namespace App\Http\Requests\Inventario;

use App\Enums\TipoMovimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de un movimiento manual de inventario (entrada, salida o ajuste).
 *
 * Tipa y sanea toda la entrada: ids validados contra su tabla, tipo restringido
 * a la lista blanca y referencia de texto libre limpiada y acotada a un juego de
 * caracteres seguro.
 */
class RegistrarMovimientoRequest extends FormRequest
{
    /** Caracteres permitidos en la referencia (letras, números, espacio y puntuación común). */
    private const PATRON_REFERENCIA = '/^[\p{L}\p{N}\s.,\-_\/#()°%:]*$/u';

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('inventario') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $esGerente = $this->user()?->esGerente() === true;

        return [
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'sucursal_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')],
            'tipo' => ['required', Rule::in(TipoMovimiento::valoresManuales())],
            'cantidad' => ['required', 'integer', 'min:0'],
            'referencia' => ['nullable', 'string', 'max:150', 'regex:'.self::PATRON_REFERENCIA],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'referencia' => $this->limpiarTexto($this->input('referencia')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            $cantidad = (int) $this->input('cantidad');

            if ($this->input('tipo') !== TipoMovimiento::Ajuste->value && $cantidad < 1) {
                $validador->errors()->add('cantidad', 'La cantidad debe ser al menos 1 para entradas y salidas.');
            }
        });
    }

    private function limpiarTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = strip_tags(trim((string) $valor));
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';
        $texto = trim($texto);

        return $texto === '' ? null : $texto;
    }
}
