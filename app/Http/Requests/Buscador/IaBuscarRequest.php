<?php

namespace App\Http\Requests\Buscador;

/**
 * Consulta al Modo IA del buscador: una pregunta en lenguaje natural.
 */
class IaBuscarRequest extends IndexBuscadorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'termino' => ['required', 'string', 'min:4', 'max:300'],
        ]);
    }
}
