<?php

namespace App\Models;

use App\Enums\CondicionProducto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id', 'nombre', 'marca', 'modelo', 'codigo', 'condicion',
        'precio_compra', 'precio_venta', 'stock_minimo', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'condicion' => CondicionProducto::class,
            'precio_compra' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'stock_minimo' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class)->orderBy('orden')->orderBy('id');
    }

    public function imagenPrincipal(): HasOne
    {
        return $this->hasOne(ProductoImagen::class)->where('es_principal', true)->orderBy('orden');
    }

    public function atributos(): HasMany
    {
        return $this->hasMany(ProductoAtributo::class);
    }

    public function stockTotal(): int
    {
        return (int) $this->inventarios()->sum('cantidad');
    }
}
