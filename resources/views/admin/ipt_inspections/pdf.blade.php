@php
    $inspection->loadMissing(['template.sections.questions', 'answers', 'requirements.requirement', 'programaCaso.empleado.cliente', 'programaCaso.empleado.sucursal']);
    $answersByQuestion = $inspection->answers->keyBy('question_id');

    $imageData = function (?string $path) {
        if (! $path) return null;
        $full = public_path('storage/' . ltrim($path, '/'));
        if (! file_exists($full)) return null;
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($full));
    };

    $fotoAntes = $imageData($inspection->foto_antes);
    $fotoDespues = $imageData($inspection->foto_despues);
    $fotoGeneral = $imageData($inspection->foto_general);
    $genesisLogoPath = public_path('images/brand/genesis-email.png');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .meta td { width: 25%; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 12px; background: #eef2ff; }
        .images { width: 100%; }
        .images td { width: 50%; text-align: center; }
        .image-box { border: 1px solid #d1d5db; min-height: 180px; padding: 8px; }
        .image-box img { max-width: 100%; max-height: 260px; }
        .muted { color: #6b7280; }
        .genesis-watermark {
            position: fixed;
            right: 10px;
            bottom: 8px;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <h1>Inspección IPT #{{ $inspection->id }}</h1>
    <div>
        <span class="pill">{{ $inspection->tipo === 'followup' ? 'Seguimiento' : 'Inicial' }}</span>
        <span class="pill">{{ $inspection->template?->nombre_publico }}</span>
    </div>

    <table class="meta">
        <tr>
            <th>Persona</th>
            <td>{{ $inspection->programaCaso?->empleado?->nombre }} · {{ $inspection->programaCaso?->empleado?->cedula }}</td>
            <th>Fecha inspección</th>
            <td>{{ optional($inspection->fecha_inspeccion)->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <th>Empresa</th>
            <td>{{ $inspection->programaCaso?->empleado?->cliente?->nombre ?? '—' }}</td>
            <th>Planta</th>
            <td>{{ $inspection->programaCaso?->empleado?->sucursal?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <th>Puntaje</th>
            <td>{{ $inspection->puntaje_total }}</td>
            <th>Riesgo</th>
            <td>{{ strtoupper((string) $inspection->nivel_riesgo) }}</td>
        </tr>
        <tr>
            <th>Próximo seguimiento</th>
            <td>{{ optional($inspection->fecha_proximo_seguimiento_sugerida)->format('Y-m-d') ?: '—' }}</td>
            <th>Estado</th>
            <td>{{ ucfirst((string) $inspection->estado) }}</td>
        </tr>
    </table>

    @if(($inspection->template?->evidencia_fotografica_modo ?? 'none') !== 'none')
        <h2>Evidencia fotográfica</h2>
        @if(($inspection->template?->evidencia_fotografica_modo ?? 'none') === 'general')
            <table class="images">
                <tr><th>Evidencia general</th></tr>
                <tr>
                    <td>
                        <div class="image-box">
                            @if($fotoGeneral)
                                <img src="{{ $fotoGeneral }}" alt="Evidencia general">
                            @else
                                <div class="muted">Sin evidencia</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        @else
            <table class="images">
                <tr>
                    <th>Antes</th>
                    <th>Después</th>
                </tr>
                <tr>
                    <td>
                        <div class="image-box">
                            @if($fotoAntes)
                                <img src="{{ $fotoAntes }}" alt="Foto antes">
                            @else
                                <div class="muted">Sin evidencia</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="image-box">
                            @if($fotoDespues)
                                <img src="{{ $fotoDespues }}" alt="Foto después">
                            @else
                                <div class="muted">Sin evidencia</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        @endif
    @endif

    <h2>Respuestas</h2>
    @foreach($inspection->template->sections->sortBy('orden') as $section)
        <div style="margin-top:10px; font-weight:bold;">{{ $section->titulo }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width:70%;">Pregunta</th>
                    <th style="width:15%;">Respuesta</th>
                    <th style="width:15%;">Puntaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section->questions->sortBy('orden') as $question)
                    @php $ans = $answersByQuestion->get($question->id); @endphp
                    <tr>
                        <td>{{ $question->texto }}</td>
                        <td>{{ strtoupper((string) ($ans->respuesta ?? '')) }}</td>
                        <td>{{ $ans->score ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <h2>Requerimientos de estación</h2>
    <table>
        <thead>
            <tr>
                <th>Requerimiento</th>
                <th style="width:20%;">Aplica</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inspection->requirements as $req)
                <tr>
                    <td>{{ $req->requirement?->nombre }}</td>
                    <td>{{ $req->aplica ? 'Sí' : 'No' }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="muted">Sin registros</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Hallazgos y plan</h2>
    <table>
        <tr>
            <th style="width:30%;">Hallazgos</th>
            <td>{{ $inspection->hallazgos ?: '—' }}</td>
        </tr>
        <tr>
            <th>Recomendaciones</th>
            <td>{{ $inspection->recomendaciones ?: '—' }}</td>
        </tr>
        @if($inspection->template?->mostrar_accion)
            <tr>
                <th>Acción</th>
                <td>{{ $inspection->accion ?: '—' }}</td>
            </tr>
        @endif
        @if($inspection->template?->mostrar_responsable)
            <tr>
                <th>Responsable</th>
                <td>{{ $inspection->responsable ?: '—' }}</td>
            </tr>
        @endif
    </table>
    <div class="genesis-watermark">
        @if(file_exists($genesisLogoPath))
            <img src="{{ $genesisLogoPath }}" alt="Genesis" style="height:10px; vertical-align:middle; margin-right:4px;">
        @endif
        <span style="vertical-align:middle;">Generado por plataforma Genesis</span>
    </div>
</body>
</html>
