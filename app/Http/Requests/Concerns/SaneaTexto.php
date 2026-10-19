<?php

namespace App\Http\Requests\Concerns;

/**
 * Saneado del texto libre de formularios (política en docs/modulos/00-contexto.md §13).
 *
 * Quita etiquetas y caracteres de control, colapsa espacios y recorta. Devuelve
 * null cuando el resultado queda vacío, para que las reglas `nullable` lo traten
 * como ausente.
 */
trait SaneaTexto
{
    protected function limpiarTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = strip_tags(trim((string) $valor));
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

        return trim($texto) === '' ? null : trim($texto);
    }
}
