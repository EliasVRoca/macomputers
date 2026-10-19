<?php

namespace App\Http\Requests\Encargos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registro de un abono al saldo de un encargo pendiente.
 */
class RegistrarAbonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('encargos') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $encargo = $this->route('encargo');

        return [
            'abono' => ['required', 'numeric', 'gt:0', 'lte:'.$encargo->saldo],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $encargo = $this->route('encargo');

        return [
            'abono.lte' => 'El pago no puede ser mayor al saldo pendiente ('.number_format((float) $encargo->saldo, 2, ',', '.').').',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['abono' => 'pago del saldo'];
    }
}
