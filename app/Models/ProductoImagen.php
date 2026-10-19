<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen de un producto para el buscador: archivo en el disco público o URL externa.
 */
class ProductoImagen extends Model
{
    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'ruta',
        'url',
        'orden',
        'es_principal',
        'proveedor',
        'origen_url',
        'titulo',
        'autor',
        'licencia',
        'licencia_url',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'es_principal' => 'boolean',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function src(): ?string
    {
        if ($this->ruta) {
            return Storage::disk('public')->url($this->ruta);
        }

        return $this->url;
    }

    public function origenDominio(): ?string
    {
        $host = parse_url((string) $this->origen_url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return preg_replace('/^www\./i', '', $host);
    }
}
