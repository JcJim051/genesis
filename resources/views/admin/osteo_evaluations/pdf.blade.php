@php
    $evaluation->loadMissing(['programaCaso.empleado.cliente', 'programaCaso.empleado.sucursal', 'template.sections.fields', 'answers.field']);
    $answers = $evaluation->answers->groupBy('field_id');
    $genesisLogoPath = public_path('images/brand/genesis-email.png');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        h2 { font-size: 13px; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .meta td { width: 25%; }
        .muted { color: #6b7280; }
        .genesis-watermark { position: fixed; right: 10px; bottom: 8px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>Valoración Osteomuscular #{{ $evaluation->id }}</h1>

    <table class="meta">
        <tr><th>Persona</th><td>{{ $evaluation->empleado?->nombre }} · {{ $evaluation->empleado?->cedula }}</td><th>Fecha</th><td>{{ optional($evaluation->fecha_valoracion)->format('Y-m-d') }}</td></tr>
        <tr><th>Empresa</th><td>{{ $evaluation->empleado?->cliente?->nombre ?? '—' }}</td><th>Planta</th><td>{{ $evaluation->empleado?->sucursal?->nombre ?? '—' }}</td></tr>
        <tr><th>Estado</th><td>{{ ucfirst((string) $evaluation->estado) }}</td><th>Plantilla</th><td>{{ $evaluation->template?->nombre_publico ?? '—' }}</td></tr>
        <tr><th>Evaluador</th><td>{{ $evaluation->evaluador ?: '—' }}</td><th>Cargo / Licencia</th><td>{{ $evaluation->cargo_profesional ?: '—' }}{{ $evaluation->licencia ? (' · ' . $evaluation->licencia) : '' }}</td></tr>
    </table>

    @foreach($evaluation->template->sections->sortBy('orden') as $section)
        <h2>{{ $section->titulo }}</h2>
        <table>
            <thead><tr><th style="width:34%">Campo</th><th style="width:26%">Valor</th><th>Observación</th></tr></thead>
            <tbody>
            @foreach($section->fields->sortBy('orden') as $field)
                @php $ans = $answers->get($field->id, collect()); @endphp
                @if(in_array($field->tipo, ['laterality_pair','plus_minus_pair'], true))
                    <tr>
                        <td>{{ $field->label }}</td>
                        <td>
                            D: {{ optional($ans->firstWhere('lado', 'D'))->valor ?? '—' }}<br>
                            I: {{ optional($ans->firstWhere('lado', 'I'))->valor ?? '—' }}
                        </td>
                        <td>
                            D: {{ optional($ans->firstWhere('lado', 'D'))->observacion ?? '—' }}<br>
                            I: {{ optional($ans->firstWhere('lado', 'I'))->observacion ?? '—' }}
                        </td>
                    </tr>
                @else
                    @php $single = $ans->first(); @endphp
                    <tr><td>{{ $field->label }}</td><td>{{ $single->valor ?? '—' }}</td><td>{{ $single->observacion ?? '—' }}</td></tr>
                @endif
            @endforeach
            </tbody>
        </table>
    @endforeach

    <h2>Observaciones generales</h2>
    <table><tr><td>{{ $evaluation->observaciones ?: '—' }}</td></tr></table>

    <div class="genesis-watermark">
        @if(file_exists($genesisLogoPath))
            <img src="{{ $genesisLogoPath }}" alt="Genesis" style="height:10px; vertical-align:middle; margin-right:4px;">
        @endif
        <span style="vertical-align:middle;">Generado por plataforma Genesis</span>
    </div>
</body>
</html>

