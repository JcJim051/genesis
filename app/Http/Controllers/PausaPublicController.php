<?php

namespace App\Http\Controllers;

use App\Models\PausaEvento;
use App\Models\PausaParticipacion;
use App\Models\PausaParticipacionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PausaPublicController extends Controller
{
    public function show(string $token)
    {
        $participacion = PausaParticipacion::with([
            'envio.pausa.formulario.preguntas.opciones',
            'empleado',
        ])->where('token', $token)->firstOrFail();

        if ($participacion->estado === 'completada') {
            return view('pausas.gracias', ['mensaje' => 'Pausa activa ya diligenciada.']);
        }

        return view('pausas.show', [
            'participacion' => $participacion,
            'pausa' => $participacion->envio->pausa,
            'empleado' => $participacion->empleado,
            'formulario' => $participacion->envio->pausa->formulario,
        ]);
    }

    public function event(Request $request, string $token)
    {
        $participacion = PausaParticipacion::where('token', $token)->firstOrFail();

        $request->validate([
            'tipo' => 'required|string',
            'timestamp' => 'required|date',
            'metadata' => 'nullable|array',
        ]);

        PausaEvento::create([
            'participacion_id' => $participacion->id,
            'tipo' => $request->input('tipo'),
            'timestamp' => $request->input('timestamp'),
            'metadata' => $request->input('metadata'),
        ]);

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, string $token)
    {
        $participacion = PausaParticipacion::with([
            'envio.pausa.formulario.preguntas.opciones',
            'empleado',
        ])->where('token', $token)->firstOrFail();

        if ($participacion->estado === 'completada') {
            return view('pausas.gracias', ['mensaje' => 'Pausa activa ya diligenciada.']);
        }

        $preguntas = $participacion->envio->pausa->formulario?->preguntas ?? collect();
        $rules = [
            'tiempo_activo_total' => 'required|integer|min:0',
            'tab_switch_count' => 'required|integer|min:0',
        ];
        foreach ($preguntas as $pregunta) {
            if ($pregunta->tipo === 'opcion') {
                $rules['pregunta_' . $pregunta->id] = 'required|integer';
            } else {
                $rules['pregunta_' . $pregunta->id] = 'required|string';
            }
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($participacion, $preguntas, $validated) {
            foreach ($preguntas as $pregunta) {
                if ($pregunta->tipo === 'opcion') {
                    $opcionId = (int) $validated['pregunta_' . $pregunta->id];
                    PausaParticipacionItem::create([
                        'participacion_id' => $participacion->id,
                        'pregunta_id' => $pregunta->id,
                        'opcion_id' => $opcionId,
                        'respuesta_texto' => null,
                    ]);
                } else {
                    PausaParticipacionItem::create([
                        'participacion_id' => $participacion->id,
                        'pregunta_id' => $pregunta->id,
                        'opcion_id' => null,
                        'respuesta_texto' => $validated['pregunta_' . $pregunta->id],
                    ]);
                }
            }

            $participacion->update([
                'tiempo_activo_total' => (int) $validated['tiempo_activo_total'],
                'tab_switch_count' => (int) $validated['tab_switch_count'],
                'respondido_en' => now(),
            ]);

            $minimo = $participacion->envio->pausa->tiempo_minimo_segundos ?? 60;
            $estado = ((int) $validated['tiempo_activo_total'] >= (int) $minimo) ? 'completada' : 'pendiente';
            $participacion->update(['estado' => $estado]);
        });

        if ($participacion->estado !== 'completada') {
            return view('pausas.gracias', ['mensaje' => 'Gracias. Tu participación quedó registrada, pero debes permanecer más tiempo en pantalla para completar la pausa activa.']);
        }

        return view('pausas.gracias', ['mensaje' => 'Gracias por completar la pausa activa.']);
    }
}
