<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de una imagen de producto: archivo (disco público) o URL externa.
 */
class GuardarProductoImagenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('productos') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'required_without:url'],
            'url' => ['nullable', 'string', 'max:500', 'regex:/^https?:\/\//i', 'required_without:archivo'],
            'es_principal' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'archivo' => 'archivo de imagen',
            'url' => 'URL de la imagen',
            'es_principal' => 'imagen principal',
            'orden' => 'orden',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'url' => filled($this->input('url')) ? trim((string) $this->input('url')) : null,
            'es_principal' => $this->boolean('es_principal'),
        ]);
    }
}
