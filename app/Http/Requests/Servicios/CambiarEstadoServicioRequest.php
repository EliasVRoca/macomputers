<?php

namespace App\Http\Requests\Servicios;

use App\Enums\EstadoServicio;
use App\Models\Venta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cambio de estado de la orden de trabajo.
 *
 * Al entregar se exige el medio de pago: el cobro queda registrado en la venta
 * de servicio (decisión RF16 · CU14).
 */
class CambiarEstadoServicioRequest extends FormRequest
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
            'estado' => ['required', Rule::enum(EstadoServicio::class)],
            'comentario' => ['nullable', 'string', 'max:500'],
            'medio_pago' => [
                'nullable',
                'required_if:estado,'.EstadoServicio::Entregado->value,
                Rule::in(Venta::MEDIOS_PAGO),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'medio_pago' => 'medio de pago',
            'comentario' => 'comentario',
        ];
    }
}
