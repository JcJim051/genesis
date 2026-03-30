<?php

namespace App\Observers;

use App\Models\Empleado;
use App\Models\Programa;
use App\Models\ProgramaCaso;

class EmpleadoObserver
{
    public function created(Empleado $empleado): void
    {
        if ($empleado->fecha_retiro) {
            return;
        }

        $programas = Programa::query()
            ->where('slug', '!=', 'reincorporacion')
            ->get();

        foreach ($programas as $programa) {
            ProgramaCaso::firstOrCreate([
                'empleado_id' => $empleado->id,
                'programa_id' => $programa->id,
            ], [
                'estado' => 'No caso',
                'origen' => 'manual',
            ]);
        }
    }
}
