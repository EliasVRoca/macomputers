<?php

namespace Tests\Support\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Marca pruebas que dependen del motor real (collation, locks, FKs).
 *
 * El perfil por defecto usa SQLite en memoria por velocidad; estas pruebas se
 * saltan en ese perfil y se ejecutan con `phpunit.mariadb.xml`.
 */
trait RequiereMariaDB
{
    protected function requiereMariaDB(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requiere MariaDB/MySQL: ejecutar con phpunit.mariadb.xml.');
        }
    }
}
