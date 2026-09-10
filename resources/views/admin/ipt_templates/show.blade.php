@extends(backpack_view('blank'))

@php
    $entry->loadMissing(['cliente', 'sections.questions', 'riskRules', 'requirements']);
@endphp

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ $entry->nombre_publico }}</h4>
                <a href="{{ backpack_url('ipt-template/' . $entry->id . '/builder') }}" class="btn btn-sm btn-outline-primary">Editar en constructor</a>
            </div>
            <div class="mt-2 text-muted">
                Empresa: {{ $entry->cliente?->nombre }} · Estado: {{ $entry->activo ? 'Activa' : 'Inactiva' }}
            </div>
            <div class="mt-1 text-muted">
                Código: {{ $entry->codigo ?: '—' }} · Segmento: {{ $entry->segmento ?: '—' }}
            </div>
            <div class="mt-3">
                <strong>Campos visibles en la inspección</strong>
                <ul class="mb-0 mt-2">
                    <li>Hallazgos: {{ ($entry->mostrar_hallazgos ?? true) ? 'Sí' : 'No' }}</li>
                    <li>Observaciones: {{ ($entry->mostrar_observaciones ?? true) ? 'Sí' : 'No' }}</li>
                    <li>Recomendaciones: {{ ($entry->mostrar_recomendaciones ?? true) ? 'Sí' : 'No' }}</li>
                    <li>Acción: {{ ($entry->mostrar_accion ?? true) ? 'Sí' : 'No' }}</li>
                    <li>Responsable: {{ ($entry->mostrar_responsable ?? true) ? 'Sí' : 'No' }}</li>
                    <li>Estado: {{ ($entry->mostrar_estado ?? true) ? 'Sí' : 'No' }}</li>
                </ul>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <h5>Secciones y preguntas</h5>
            @foreach($entry->sections->sortBy('orden') as $section)
                <div class="mt-3">
                    <strong>{{ $section->orden }}. {{ $section->titulo }}</strong>
                    <ol class="mt-2">
                        @foreach($section->questions->sortBy('orden') as $question)
                            <li>{{ $question->texto }} <small class="text-muted">(SI={{ $question->si_score }})</small></li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>

        <div class="card p-4 mb-4">
            <h5>Reglas de riesgo</h5>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Nivel</th>
                        <th>Rango</th>
                        <th>Meses seguimiento</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($entry->riskRules->sortBy('orden') as $rule)
                        <tr>
                            <td>{{ strtoupper($rule->nivel) }}</td>
                            <td>{{ $rule->min_score }} - {{ $rule->max_score }}</td>
                            <td>{{ $rule->followup_months }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-4">
            <h5>Requerimientos</h5>
            <ul class="mb-0">
                @forelse($entry->requirements->sortBy('orden') as $req)
                    <li>{{ $req->nombre }} @if(! $req->activo)<small class="text-muted">(inactivo)</small>@endif</li>
                @empty
                    <li class="text-muted">No hay requerimientos definidos.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
