<?php

namespace App\Http\Requests\Servicios;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de una orden de trabajo (RF16 · CU14).
 *
 * Valida y sanea la recepción del equipo, el cliente (existente o en línea) y
 * la asignación de sucursal/técnico.
 */
class GuardarServicioRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('servicios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $esGerente = $this->user()?->esGerente() === true;

        return [
            'modo_cliente' => ['required', Rule::in(['existente', 'nuevo'])],
            'cliente_id' => ['nullable', 'required_if:modo_cliente,existente', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'cliente_nombre' => ['nullable', 'required_if:modo_cliente,nuevo', 'string', 'max:150'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')],
            'sucursal_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'tecnico_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('activo', true)],
            'equipo' => ['required', 'string', 'max:150'],
            'diagnostico' => ['nullable', 'string', 'max:1000'],
            'fecha_recepcion' => ['required', 'date'],
            'fecha_prometida' => ['nullable', 'date', 'after_or_equal:fecha_recepcion'],
            'prioridad' => ['required', Rule::in(array_keys(Servicio::PRIORIDADES))],
            'accesorios_recibidos' => ['nullable', 'string', 'max:500'],
            'mano_de_obra' => ['required', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'garantia_dias' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'modo_cliente' => 'tipo de cliente',
            'cliente_id' => 'cliente',
            'cliente_nombre' => 'nombre del cliente',
            'cliente_telefono' => 'teléfono',
            'cliente_carnet' => 'carnet',
            'sucursal_id' => 'sucursal',
            'tecnico_id' => 'técnico',
            'equipo' => 'equipo',
            'mano_de_obra' => 'mano de obra',
            'fecha_recepcion' => 'fecha de recepción',
            'fecha_prometida' => 'fecha prometida',
            'accesorios_recibidos' => 'accesorios recibidos',
            'garantia_dias' => 'días de garantía',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'equipo' => $this->limpiarTexto($this->input('equipo')),
            'diagnostico' => $this->limpiarTexto($this->input('diagnostico')),
            'accesorios_recibidos' => $this->limpiarTexto($this->input('accesorios_recibidos')),
            'observaciones' => $this->limpiarTexto($this->input('observaciones')),
            'cliente_nombre' => $this->limpiarTexto($this->input('cliente_nombre')),
            'cliente_telefono' => $this->limpiarTexto($this->input('cliente_telefono')),
            'cliente_carnet' => $this->limpiarTexto($this->input('cliente_carnet')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            $tecnicoId = (int) $this->input('tecnico_id');

            if ($tecnicoId === 0) {
                return;
            }

            $tecnico = User::find($tecnicoId);

            if ($tecnico && ! $tecnico->tienePermiso('servicios')) {
                $validador->errors()->add('tecnico_id', 'El usuario seleccionado no tiene acceso al servicio técnico.');
            }
        });
    }
}
