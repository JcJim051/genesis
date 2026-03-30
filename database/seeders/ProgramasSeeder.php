<?php

namespace Database\Seeders;

use App\Models\Programa;
use App\Models\ProgramaCaso;
use App\Models\Empleado;
use Illuminate\Database\Seeder;

class ProgramasSeeder extends Seeder
{
    public function run(): void
    {
        $programas = [
            ['nombre' => 'Osteomuscular', 'slug' => 'osteomuscular'],
            ['nombre' => 'Visual', 'slug' => 'visual'],
            ['nombre' => 'Psicosocial', 'slug' => 'psicosocial'],
            ['nombre' => 'Auditivo', 'slug' => 'auditivo'],
            ['nombre' => 'Cardiovascular', 'slug' => 'cardiovascular'],
            ['nombre' => 'Reincorporación', 'slug' => 'reincorporacion', 'tipo' => 'reincorporacion'],
        ];

        foreach ($programas as $data) {
            Programa::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'nombre' => $data['nombre'],
                    'tipo' => $data['tipo'] ?? 'programa',
                ]
            );
        }

        $baseProgramas = Programa::where('slug', '!=', 'reincorporacion')->get();
        $empleados = Empleado::whereNull('fecha_retiro')->pluck('id');

        foreach ($empleados as $empleadoId) {
            foreach ($baseProgramas as $programa) {
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
}
