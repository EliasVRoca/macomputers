<?php

namespace App\Http\Requests\Servicios;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edición de una orden de trabajo (RF16 · CU14).
 *
 * Los datos descriptivos se pueden corregir siempre; la reasignación de
 * cliente/sucursal/técnico solo aplica a órdenes abiertas y con las reglas del
 * módulo (sucursal/técnico solo gerente, sucursal con repuestos bloqueada).
 */
class ActualizarServicioRequest extends FormRequest
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
        $servicio = $this->route('servicio');
        $esGerente = $this->user()?->esGerente() === true;

        $reglas = [
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

        if ($servicio instanceof Servicio && ! $servicio->cerrado()) {
            $reglas['cliente_id'] = ['nullable', 'integer', Rule::exists('clientes', 'id')];

            if ($esGerente) {
                $reglas['sucursal_id'] = ['nullable', 'integer', Rule::exists('sucursales', 'id')];
                $reglas['tecnico_id'] = ['nullable', 'integer', Rule::exists('users', 'id')->where('activo', true)];
            }
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'equipo' => 'equipo',
            'mano_de_obra' => 'mano de obra',
            'fecha_recepcion' => 'fecha de recepción',
            'fecha_prometida' => 'fecha prometida',
            'accesorios_recibidos' => 'accesorios recibidos',
            'garantia_dias' => 'días de garantía',
            'cliente_id' => 'cliente',
            'sucursal_id' => 'sucursal',
            'tecnico_id' => 'técnico',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'equipo' => $this->limpiarTexto($this->input('equipo')),
            'diagnostico' => $this->limpiarTexto($this->input('diagnostico')),
            'accesorios_recibidos' => $this->limpiarTexto($this->input('accesorios_recibidos')),
            'observaciones' => $this->limpiarTexto($this->input('observaciones')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            $servicio = $this->route('servicio');

            if (! $servicio instanceof Servicio) {
                return;
            }

            if ($servicio->cerrado()) {
                $this->validarSinReasignacion($validador, $servicio);

                return;
            }

            $nuevaSucursal = (int) $this->input('sucursal_id');

            if ($nuevaSucursal !== 0 && $nuevaSucursal !== (int) $servicio->sucursal_id && $servicio->repuestos()->exists()) {
                $validador->errors()->add('sucursal_id', 'No se puede cambiar la sucursal de una orden con repuestos.');
            }

            $tecnicoId = (int) $this->input('tecnico_id');

            if ($tecnicoId !== 0) {
                $tecnico = User::find($tecnicoId);

                if ($tecnico && ! $tecnico->tienePermiso('servicios')) {
                    $validador->errors()->add('tecnico_id', 'El usuario seleccionado no tiene acceso al servicio técnico.');
                }
            }
        });
    }

    private function validarSinReasignacion(Validator $validador, Servicio $servicio): void
    {
        if ((int) $this->input('cliente_id') !== 0 && (int) $this->input('cliente_id') !== (int) $servicio->cliente_id) {
            $validador->errors()->add('cliente_id', 'La orden está cerrada y no admite reasignación de cliente.');
        }

        if ((int) $this->input('sucursal_id') !== 0 && (int) $this->input('sucursal_id') !== (int) $servicio->sucursal_id) {
            $validador->errors()->add('sucursal_id', 'La orden está cerrada y no admite reasignación de sucursal.');
        }

        if ((int) $this->input('tecnico_id') !== 0 && (int) $this->input('tecnico_id') !== (int) $servicio->tecnico_id) {
            $validador->errors()->add('tecnico_id', 'La orden está cerrada y no admite reasignación de técnico.');
        }
    }
}
