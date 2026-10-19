<?php

namespace App\Http\Requests\Sucursales;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Sucursal;
use App\Services\SucursalService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de una sucursal (RF4 · CU3).
 *
 * La baja es lógica (`activa`); desde el formulario también se valida que la
 * sucursal pueda desactivarse sin dejar stock o usuarios inaccesibles.
 */
class GuardarSucursalRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('sucursales') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sucursal = $this->route('sucursal');

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('sucursales', 'nombre')->ignore($sucursal?->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'activa' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'direccion' => $this->limpiarTexto($this->input('direccion')),
            'telefono' => $this->limpiarTexto($this->input('telefono')),
            'activa' => $this->boolean('activa'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            $sucursal = $this->route('sucursal');

            if (! $sucursal instanceof Sucursal || $this->boolean('activa')) {
                return;
            }

            $motivo = app(SucursalService::class)->motivoNoDesactivable($sucursal);

            if ($motivo !== null) {
                $validador->errors()->add('activa', 'No se puede desactivar: '.$motivo);
            }
        });
    }
}
