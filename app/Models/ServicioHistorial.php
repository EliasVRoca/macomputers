<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioHistorial extends Model
{
    protected $table = 'servicio_historial';

    protected $fillable = ['servicio_id', 'user_id', 'estado_anterior', 'estado_nuevo', 'comentario'];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
