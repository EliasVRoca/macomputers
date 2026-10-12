<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table = 'personas';

    protected $fillable = ['nombre', 'ci', 'telefono', 'direccion'];

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
