<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Traspaso extends Model
{
    protected $table = 'traspasos';

    protected $fillable = ['sucursal_origen_id', 'sucursal_destino_id', 'user_id', 'fecha'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TraspasoItem::class);
    }
}
