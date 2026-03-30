<?php

namespace App\Observers;

use App\Models\Empleado;
use App\Models\Programa;
use App\Models\ProgramaCaso;

class ProgramaObserver
{
    public function created(Programa $programa): void
    {
        if ($programa->slug === 'reincorporacion') {
            return;
        }

        $empleados = Empleado::query()->whereNull('fecha_retiro')->pluck('id');

        foreach ($empleados as $empleadoId) {
            ProgramaCaso::firstOrCreate([
                'empleado_id' => $empleadoId,
                'programa_id' => $programa->id,
            ], [
                'estado' => 'No caso',
                'origen' => 'manual',
            ]);
        }
    }
}
