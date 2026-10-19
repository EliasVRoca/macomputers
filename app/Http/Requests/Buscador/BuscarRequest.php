<?php

namespace App\Http\Requests\Buscador;

/**
 * Búsqueda en vivo: exige un término de al menos 2 caracteres.
 */
class BuscarRequest extends IndexBuscadorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'termino' => ['required', 'string', 'min:2', 'max:100'],
        ]);
    }
}
