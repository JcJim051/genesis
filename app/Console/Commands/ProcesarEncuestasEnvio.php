<?php

namespace App\Console\Commands;

use App\Models\Empleado;
use App\Models\EncuestaEnvio;
use App\Models\EncuestaRespuesta;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProcesarEncuestasEnvio extends Command
{
    protected $signature = 'encuestas:procesar-envios';
    protected $description = 'Genera respuestas para envíos de encuestas programados.';

    public function handle(): int
    {
        $envios = EncuestaEnvio::query()
            ->whereNull('procesado_en')
            ->where(function ($q) {
                $q->whereNull('fecha_envio')
                    ->orWhere('fecha_envio', '<=', now()->toDateString());
            })
            ->get();

        foreach ($envios as $envio) {
            $query = Empleado::query()
                ->whereNull('fecha_retiro')
                ->where('cliente_id', $envio->cliente_id);

            if ($envio->sucursal_id) {
                $query->where('sucursal_id', $envio->sucursal_id);
            }

            $empleados = $query->get(['id']);

            foreach ($empleados as $empleado) {
                EncuestaRespuesta::firstOrCreate([
                    'encuesta_id' => $envio->encuesta_id,
                    'envio_id' => $envio->id,
                    'empleado_id' => $empleado->id,
                ], [
                    'token' => (string) Str::uuid(),
                    'estado' => 'pendiente',
                ]);
            }

            $envio->update(['procesado_en' => now()]);
        }

        $this->info('Procesamiento de envíos completado.');
        return Command::SUCCESS;
    }
}
