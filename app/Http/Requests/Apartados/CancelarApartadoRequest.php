<?php

namespace App\Http\Requests\Apartados;

use App\Http\Requests\Concerns\SaneaTexto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelación de un apartado activo: libera el stock reservado y retiene una
 * seña (monto informativo, sin movimiento de caja).
 */
class CancelarApartadoRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('apartados') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $apartado = $this->route('apartado');

        return [
            'motivo_cancelacion' => ['nullable', 'string', 'max:255'],
            'sena_retenida' => ['nullable', 'numeric', 'min:0', 'lte:'.$apartado->sena],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $apartado = $this->route('apartado');

        return [
            'sena_retenida.lte' => 'La seña retenida no puede superar la seña pagada ('.number_format((float) $apartado->sena, 2, ',', '.').').',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['motivo_cancelacion' => 'motivo', 'sena_retenida' => 'seña retenida'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'motivo_cancelacion' => $this->limpiarTexto($this->input('motivo_cancelacion')),
            'sena_retenida' => $this->input('sena_retenida', 0),
        ]);
    }
}
