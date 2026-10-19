<?php

namespace App\Models;

use App\Enums\EstadoServicio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    public const PRIORIDADES = [
        'baja' => 'Baja',
        'normal' => 'Normal',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    protected $table = 'servicios';

    protected $fillable = [
        'venta_id', 'cliente_id', 'sucursal_id', 'tecnico_id', 'numero', 'equipo',
        'diagnostico', 'estado', 'fecha_recepcion', 'fecha_prometida', 'fecha_entrega',
        'prioridad', 'accesorios_recibidos', 'mano_de_obra', 'descuento', 'garantia_dias',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'date',
            'fecha_prometida' => 'date',
            'fecha_entrega' => 'date',
            'mano_de_obra' => 'decimal:2',
            'descuento' => 'decimal:2',
            'garantia_dias' => 'integer',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(ServicioHistorial::class)->latest('id');
    }

    public function repuestos(): HasMany
    {
        return $this->hasMany(ServicioRepuesto::class);
    }

    public function estadoEnum(): EstadoServicio
    {
        return EstadoServicio::desde($this->estado);
    }

    public function empezado(): bool
    {
        return $this->fecha_recepcion !== null;
    }

    /** La orden ya no admite cambios de datos ni de repuestos. */
    public function cerrado(): bool
    {
        return $this->estadoEnum()->esFinal();
    }

    public function repuestosTotal(): float
    {
        return (float) $this->repuestos()->sum('subtotal');
    }

    public function totalCalculado(): float
    {
        $total = (float) $this->mano_de_obra + $this->repuestosTotal() - (float) $this->descuento;

        return round(max($total, 0), 2);
    }
}
