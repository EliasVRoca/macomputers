<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;

class GuardarImagenInternetRequest extends FormRequest
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
            'url' => ['required', 'string', 'max:1000', 'regex:/^https?:\/\//i'],
            'miniatura' => ['nullable', 'string', 'max:1000', 'regex:/^https?:\/\//i'],
            'origen_url' => ['nullable', 'string', 'max:1000', 'regex:/^https?:\/\//i'],
            'proveedor' => ['nullable', 'string', 'in:brave,openverse,wikimedia'],
            'titulo' => ['nullable', 'string', 'max:200'],
            'autor' => ['nullable', 'string', 'max:150'],
            'licencia' => ['nullable', 'string', 'max:100'],
            'licencia_url' => ['nullable', 'string', 'max:1000', 'regex:/^https?:\/\//i'],
            'es_principal' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'url' => 'URL de la imagen',
            'miniatura' => 'URL de la miniatura',
            'origen_url' => 'URL de origen',
            'proveedor' => 'proveedor',
            'titulo' => 'título',
            'autor' => 'autor',
            'licencia' => 'licencia',
            'licencia_url' => 'URL de la licencia',
            'es_principal' => 'imagen principal',
        ];
    }

    protected function prepareForValidation(): void
    {
        $proveedor = strtolower(trim((string) $this->input('proveedor')));

        $this->merge([
            'url' => trim((string) $this->input('url')),
            'miniatura' => filled($this->input('miniatura')) ? trim((string) $this->input('miniatura')) : null,
            'origen_url' => filled($this->input('origen_url')) ? trim((string) $this->input('origen_url')) : null,
            'proveedor' => $proveedor !== '' ? $proveedor : null,
            'titulo' => $this->limpiarTexto($this->input('titulo')),
            'autor' => $this->limpiarTexto($this->input('autor')),
            'licencia' => $this->limpiarTexto($this->input('licencia')),
            'licencia_url' => filled($this->input('licencia_url')) ? trim((string) $this->input('licencia_url')) : null,
            'es_principal' => $this->boolean('es_principal'),
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
