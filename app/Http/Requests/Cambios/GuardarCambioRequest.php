<?php

namespace App\Http\Requests\Cambios;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\Cambio;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\StockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de un cambio o devolución sobre una venta existente (RF15 · CU13).
 *
 * Verifica que el producto pertenezca a la venta, que la cantidad no supere lo
 * vendido (menos lo ya cambiado/devuelto) y que el reemplazo tenga stock en la
 * sucursal de la venta.
 */
class GuardarCambioRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('cambios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'venta_id' => ['required', 'integer', Rule::exists('ventas', 'id')],
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'tipo' => ['required', Rule::in([Cambio::TIPO_CAMBIO, Cambio::TIPO_DEVOLUCION])],
            'producto_reemplazo_id' => ['required_if:tipo,'.Cambio::TIPO_CAMBIO, 'nullable', 'integer', Rule::exists('productos', 'id')],
            'cantidad' => ['required', 'integer', 'min:1'],
            'monto_diferencia' => ['nullable', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', 'max:500'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'producto_reemplazo_id.required_if' => 'Seleccione el producto de reemplazo para un cambio.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'venta_id' => 'venta',
            'producto_id' => 'producto',
            'producto_reemplazo_id' => 'producto de reemplazo',
            'monto_diferencia' => 'monto de la diferencia',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'monto_diferencia' => $this->input('monto_diferencia', 0),
            'motivo' => $this->limpiarTexto($this->input('motivo')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            $venta = Venta::find($this->input('venta_id'));

            if (! $venta || $venta->estado !== Venta::ESTADO_ACTIVA || $venta->tipo !== Venta::TIPO_VENTA) {
                $validador->errors()->add('venta_id', 'Solo se pueden registrar cambios sobre ventas activas de productos.');

                return;
            }

            $productoId = (int) $this->input('producto_id');
            $cantidad = (int) $this->input('cantidad');

            $pertenece = VentaItem::where('venta_id', $venta->id)->where('producto_id', $productoId)->exists();

            if (! $pertenece) {
                $validador->errors()->add('producto_id', 'El producto no pertenece a la venta seleccionada.');

                return;
            }

            $vendida = (int) VentaItem::where('venta_id', $venta->id)->where('producto_id', $productoId)->sum('cantidad');
            $procesada = (int) Cambio::where('venta_id', $venta->id)->where('producto_id', $productoId)->sum('cantidad');

            if ($cantidad > ($vendida - $procesada)) {
                $validador->errors()->add('cantidad', 'La cantidad supera lo pendiente de esa venta (máximo '.max(0, $vendida - $procesada).').');
            }

            if ($this->input('tipo') === Cambio::TIPO_CAMBIO) {
                $reemplazoId = (int) $this->input('producto_reemplazo_id');
                $disponible = app(StockService::class)->disponible($reemplazoId, $venta->sucursal_id);

                if ($reemplazoId === $productoId) {
                    $validador->errors()->add('producto_reemplazo_id', 'El producto de reemplazo debe ser distinto al producto con falla.');
                } elseif ($disponible < $cantidad) {
                    $nombre = Producto::whereKey($reemplazoId)->value('nombre') ?? 'el reemplazo';
                    $validador->errors()->add('producto_reemplazo_id', "Sin stock suficiente de {$nombre} en la sucursal (disponible: {$disponible}).");
                }
            }
        });
    }
}
