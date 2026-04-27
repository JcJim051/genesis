@extends(backpack_view('blank'))

@php
    $entry->loadMissing([
        'programaCaso.programa',
        'programaCaso.empleado.cliente',
        'programaCaso.empleado.sucursal',
        'template.sections.questions',
        'answers',
        'requirements.requirement',
        'initialInspection',
        'followups',
    ]);

    $answersByQuestion = $entry->answers->keyBy('question_id');
@endphp

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Inspección IPT #{{ $entry->id }}</h4>
                <div class="d-flex gap-2">
                    <a href="{{ backpack_url('ipt/' . $entry->id . '/edit') }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    @if($entry->tipo === 'initial')
                        <a href="{{ backpack_url('ipt/' . $entry->id . '/create-followup') }}" class="btn btn-sm btn-outline-success">Crear seguimiento</a>
                    @endif
                </div>
            </div>
            <div class="mt-2 text-muted">
                {{ $entry->template?->nombre_publico }} · {{ $entry->tipo === 'followup' ? 'Seguimiento' : 'Inicial' }}
            </div>
        </div>

        <div class="card p-4 mb-4">
            <div class="row">
                <div class="col-md-3"><strong>Persona:</strong> {{ $entry->programaCaso?->empleado?->nombre }} · {{ $entry->programaCaso?->empleado?->cedula }}</div>
                <div class="col-md-3"><strong>Empresa:</strong> {{ $entry->programaCaso?->empleado?->cliente?->nombre }}</div>
                <div class="col-md-3"><strong>Planta:</strong> {{ $entry->programaCaso?->empleado?->sucursal?->nombre }}</div>
                <div class="col-md-3"><strong>Fecha:</strong> {{ optional($entry->fecha_inspeccion)->format('Y-m-d') }}</div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3"><strong>Puntaje:</strong> {{ $entry->puntaje_total }}</div>
                <div class="col-md-3"><strong>Riesgo:</strong> {{ strtoupper((string) $entry->nivel_riesgo) }}</div>
                <div class="col-md-3"><strong>Próximo seguimiento:</strong> {{ optional($entry->fecha_proximo_seguimiento_sugerida)->format('Y-m-d') ?: '—' }}</div>
                <div class="col-md-3"><strong>Estado:</strong> {{ ucfirst((string) $entry->estado) }}</div>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <h5>Respuestas</h5>
            @foreach($entry->template->sections->sortBy('orden') as $section)
                <div class="mt-3">
                    <h6>{{ $section->titulo }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                            <tr>
                                <th>Pregunta</th>
                                <th>Respuesta</th>
                                <th>Score</th>
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
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card p-4 mb-4">
            <h5>Requerimientos</h5>
            <ul class="mb-0">
                @forelse($entry->requirements as $req)
                    <li>{{ $req->requirement?->nombre }}: {{ $req->aplica ? 'Sí' : 'No' }}</li>
                @empty
                    <li class="text-muted">Sin requerimientos registrados.</li>
                @endforelse
            </ul>
        </div>

        <div class="card p-4 mb-4">
            <h5>Observaciones y plan</h5>
            <p><strong>Hallazgos:</strong><br>{{ $entry->hallazgos ?: '—' }}</p>
            <p><strong>Recomendaciones:</strong><br>{{ $entry->recomendaciones ?: '—' }}</p>
            <p><strong>Acción:</strong><br>{{ $entry->accion ?: '—' }}</p>
            <p class="mb-0"><strong>Responsable:</strong> {{ $entry->responsable ?: '—' }}</p>
        </div>

        @if($entry->tipo === 'initial' && $entry->followups->isNotEmpty())
            <div class="card p-4">
                <h5>Seguimientos</h5>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Puntaje</th>
                            <th>Riesgo</th>
                            <th>Éxito</th>
                            <th>Detalle</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entry->followups->sortByDesc('fecha_inspeccion') as $followup)
                            <tr>
                                <td>{{ $followup->id }}</td>
                                <td>{{ optional($followup->fecha_inspeccion)->format('Y-m-d') }}</td>
                                <td>{{ $followup->puntaje_total }}</td>
                                <td>{{ strtoupper((string) $followup->nivel_riesgo) }}</td>
                                <td>{{ is_null($followup->seguimiento_exitoso) ? '—' : ($followup->seguimiento_exitoso ? 'Sí' : 'No') }}</td>
                                <td><a href="{{ backpack_url('ipt-inspection/' . $followup->id . '/show') }}" class="btn btn-sm btn-outline-primary">Ver</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
