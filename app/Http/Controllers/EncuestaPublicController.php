<?php

namespace App\Http\Controllers;

use App\Models\EncuestaAlerta;
use App\Models\EncuestaRespuesta;
use App\Models\EncuestaRespuestaItem;
use App\Models\ProgramaCaso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EncuestaPublicController extends Controller
{
    public function show(string $token)
    {
        $respuesta = EncuestaRespuesta::with([
            'encuesta.preguntas.opciones',
            'empleado',
        ])->where('token', $token)->firstOrFail();

        if ($respuesta->estado === 'completada') {
            return view('encuestas.gracias', ['mensaje' => 'Encuesta ya diligenciada.']);
        }

        return view('encuestas.show', [
            'respuesta' => $respuesta,
            'encuesta' => $respuesta->encuesta,
            'empleado' => $respuesta->empleado,
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $respuesta = EncuestaRespuesta::with([
            'encuesta.preguntas.opciones',
            'empleado.cliente',
        ])->where('token', $token)->firstOrFail();

        if ($respuesta->estado === 'completada') {
            return view('encuestas.gracias', ['mensaje' => 'Encuesta ya diligenciada.']);
        }

        $preguntas = $respuesta->encuesta->preguntas;
        $rules = [];
        foreach ($preguntas as $pregunta) {
            $rules['pregunta_' . $pregunta->id] = 'required|integer';
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($respuesta, $preguntas, $validated) {
            $puntajeTotal = 0;

            foreach ($preguntas as $pregunta) {
                $opcionId = (int) $validated['pregunta_' . $pregunta->id];
                $opcion = $pregunta->opciones->firstWhere('id', $opcionId);
                $puntaje = $opcion?->puntaje ?? 0;
                $puntajeTotal += $puntaje;

                EncuestaRespuestaItem::create([
                    'respuesta_id' => $respuesta->id,
                    'pregunta_id' => $pregunta->id,
                    'opcion_id' => $opcionId,
                    'puntaje' => $puntaje,
                ]);
            }

            $respuesta->update([
                'estado' => 'completada',
                'puntaje_total' => $puntajeTotal,
                'respondido_en' => now(),
            ]);

            if ($puntajeTotal >= $respuesta->encuesta->umbral_puntaje) {
                EncuestaAlerta::create([
                    'encuesta_id' => $respuesta->encuesta_id,
                    'programa_id' => $respuesta->encuesta->programa_id,
                    'empleado_id' => $respuesta->empleado_id,
                    'cliente_id' => $respuesta->empleado->cliente_id,
                    'sucursal_id' => $respuesta->empleado->sucursal_id,
                    'puntaje' => $puntajeTotal,
                    'estado' => 'pendiente',
                ]);

                // Crear/asegurar caso en la bandeja de evaluación (sin confirmar automáticamente).
                $caso = ProgramaCaso::firstOrCreate(
                    [
                        'empleado_id' => $respuesta->empleado_id,
                        'programa_id' => $respuesta->encuesta->programa_id,
                    ],
                    [
                        'estado' => 'No evaluado',
                        'origen' => 'encuesta',
                        'sugerido_por' => 'encuesta',
                    ]
                );

                if (! in_array($caso->estado, ['Probable', 'Confirmado'], true)) {
                    $caso->update([
                        'estado' => 'No evaluado',
                        'origen' => 'encuesta',
                        'sugerido_por' => 'encuesta',
                    ]);
                }
            }
        });

        return view('encuestas.gracias', ['mensaje' => 'Gracias por diligenciar la encuesta.']);
    }
}
