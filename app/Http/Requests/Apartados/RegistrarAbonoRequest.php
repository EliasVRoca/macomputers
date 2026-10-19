<?php

namespace App\Http\Requests\Apartados;

use App\Models\Venta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro de un abono al saldo de un apartado activo.
 *
 * El `medio_pago` es opcional: cuando el abono completa el saldo se usa para la
 * venta que se genera automáticamente. Si no se indica, se asume efectivo.
 */
class RegistrarAbonoRequest extends FormRequest
{
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
            'abono' => ['required', 'numeric', 'gt:0', 'lte:'.$apartado->saldo],
            'medio_pago' => ['nullable', Rule::in(Venta::MEDIOS_PAGO)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $apartado = $this->route('apartado');

        return [
            'abono.lte' => 'El pago no puede ser mayor al saldo pendiente ('.number_format((float) $apartado->saldo, 2, ',', '.').').',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['abono' => 'pago del saldo', 'medio_pago' => 'medio de pago'];
    }
}
