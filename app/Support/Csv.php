<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Escritura segura de archivos CSV.
 *
 * Incluye BOM UTF-8 (para que Excel reconozca los acentos) y neutraliza la
 * inyección de fórmulas: una celda de texto que empiece con =, +, -, @, tab o
 * retorno de carro se prefija con un apóstrofo para que Excel/Sheets no la
 * ejecute. Los valores numéricos se conservan tal cual.
 */
class Csv
{
    public const DELIMITADOR = ';';

    /**
     * Genera una descarga CSV transmitida en streaming.
     *
     * @param  array<int, string>  $encabezados
     * @param  iterable<int, array<int, string|int|float|null>>  $filas
     */
    public static function descargar(string $nombreArchivo, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas): void {
            $salida = fopen('php://output', 'wb');

            // BOM UTF-8: hace que Excel detecte la codificación.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, array_map([self::class, 'celda'], $encabezados), self::DELIMITADOR, '"', '\\');

            foreach ($filas as $fila) {
                fputcsv($salida, array_map([self::class, 'celda'], $fila), self::DELIMITADOR, '"', '\\');
            }

            fclose($salida);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Prepara una celda para el CSV, neutralizando fórmulas peligrosas.
     */
    public static function celda(mixed $valor): string|int|float
    {
        if (is_int($valor) || is_float($valor)) {
            return $valor;
        }

        $texto = (string) ($valor ?? '');

        if ($texto !== '' && ! is_numeric($texto) && preg_match('/^[=+\-@\t\r]/', $texto) === 1) {
            return "'".$texto;
        }

        return $texto;
    }
}
