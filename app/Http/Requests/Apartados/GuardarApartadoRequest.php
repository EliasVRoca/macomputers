<?php

namespace App\Http\Requests\Apartados;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de un apartado (RF13 · CU11): reserva stock con seña libre.
 *
 * Valida que la seña no supere el valor total (calculado en el servidor a
 * partir del precio del producto) y admite el alta del cliente en línea.
 */
class GuardarApartadoRequest extends FormRequest
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
        $esGerente = $this->user()?->esGerente() === true;

        return [
            'modo_cliente' => ['nullable', Rule::in(['existente', 'nuevo'])],
            'cliente_id' => ['nullable', 'required_if:modo_cliente,existente', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'cliente_nombre' => ['nullable', 'required_if:modo_cliente,nuevo', 'string', 'max:150'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')],
            'sucursal_id' => [$esGerente ? 'required' : 'nullable', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'cantidad' => ['required', 'integer', 'min:1'],
            'sena' => ['required', 'numeric', 'min:0'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sucursal_id' => 'sucursal',
            'cliente_id' => 'cliente',
            'cliente_nombre' => 'nombre del cliente',
            'cliente_telefono' => 'teléfono',
            'cliente_carnet' => 'carnet',
            'producto_id' => 'producto',
            'sena' => 'seña',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'modo_cliente' => $this->input('modo_cliente') ?: 'existente',
            'cliente_nombre' => $this->limpiarTexto($this->input('cliente_nombre')),
            'cliente_telefono' => $this->limpiarTexto($this->input('cliente_telefono')),
            'cliente_carnet' => $this->limpiarTexto($this->input('cliente_carnet')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            $precio = (float) (Producto::query()->whereKey($this->input('producto_id'))->value('precio_venta') ?? 0);
            $total = round((int) $this->input('cantidad') * $precio, 2);

            if ((float) $this->input('sena') > $total) {
                $validador->errors()->add('sena', 'La seña no puede ser mayor al valor total del apartado.');
            }
        });
    }
}
