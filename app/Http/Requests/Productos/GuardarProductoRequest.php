<?php

namespace App\Http\Requests\Productos;

use App\Enums\CondicionProducto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de un producto del catálogo (RF6 · CU5).
 *
 * Tipa y sanea la entrada: ids validados, código único, precios numéricos,
 * condición en lista blanca y texto libre limpiado. El stock NO se toca aquí.
 */
class GuardarProductoRequest extends FormRequest
{
    /** Caracteres permitidos en el texto (letras, números, espacio y puntuación común). */
    private const PATRON_TEXTO = '/^[\p{L}\p{N}\s.,;:\-_\/#()°%+*\'"&@!?¡¿×·|]*$/u';

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('productos') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')],
            'nombre' => ['required', 'string', 'max:150', 'regex:'.self::PATRON_TEXTO],
            'marca' => ['required', 'string', 'max:100', 'regex:'.self::PATRON_TEXTO],
            'modelo' => ['nullable', 'string', 'max:100', 'regex:'.self::PATRON_TEXTO],
            'codigo' => ['required', 'string', 'max:50', 'regex:'.self::PATRON_TEXTO, Rule::unique('productos', 'codigo')->ignore($producto?->id)],
            'condicion' => ['nullable', Rule::in(CondicionProducto::valores())],
            'precio_compra' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['categoria_id' => 'categoría'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'marca' => $this->limpiarTexto($this->input('marca')),
            'modelo' => $this->limpiarTexto($this->input('modelo')),
            'codigo' => $this->limpiarTexto($this->input('codigo')),
            'condicion' => $this->input('condicion') ?: CondicionProducto::Nuevo->value,
            'activo' => $this->boolean('activo'),
        ]);
    }

    private function limpiarTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = strip_tags(trim((string) $valor));
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

        return $texto === '' ? null : $texto;
    }
}
