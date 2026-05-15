@extends(backpack_view('blank'))

@section('content')
@php
    $entry = $crud->entry->loadMissing(['programaCaso.empleado.cliente', 'programaCaso.empleado.sucursal', 'template.sections.fields', 'answers.field']);
    $answers = $entry->answers->groupBy('field_id');
@endphp
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Valoración Osteomuscular #{{ $entry->id }}</h4>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="{{ backpack_url('osteo-evaluation/' . $entry->id . '/edit') }}">Editar</a>
            <a class="btn btn-sm btn-primary" href="{{ backpack_url('osteo-evaluation/' . $entry->id . '/pdf') }}">PDF</a>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4"><strong>Persona:</strong> {!! \App\Support\EmpleadoLink::render($entry->empleado, trim(($entry->empleado?->nombre ?? '') . ' · ' . ($entry->empleado?->cedula ?? ''))) !!}</div>
        <div class="col-md-3"><strong>Empresa:</strong> {{ $entry->empleado?->cliente?->nombre ?? '—' }}</div>
        <div class="col-md-3"><strong>Planta:</strong> {{ $entry->empleado?->sucursal?->nombre ?? '—' }}</div>
        <div class="col-md-2"><strong>Fecha:</strong> {{ optional($entry->fecha_valoracion)->format('Y-m-d') }}</div>
        <div class="col-md-3"><strong>Estado:</strong> {{ ucfirst((string) $entry->estado) }}</div>
        <div class="col-md-3"><strong>Evaluador:</strong> {{ $entry->evaluador ?: '—' }}</div>
        <div class="col-md-3"><strong>Cargo:</strong> {{ $entry->cargo_profesional ?: '—' }}</div>
        <div class="col-md-3"><strong>Licencia:</strong> {{ $entry->licencia ?: '—' }}</div>
    </div>

    @foreach($entry->template->sections->sortBy('orden') as $section)
        <div class="card border p-3 mb-3">
            <h6>{{ $section->titulo }}</h6>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Campo</th><th>Valor</th><th>Observación</th></tr></thead>
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
                            <tr>
                                <td>{{ $field->label }}</td>
                                <td>{{ $single->valor ?? '—' }}</td>
                                <td>{{ $single->observacion ?? '—' }}</td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="card border p-3">
        <h6>Observaciones generales</h6>
        <div>{{ $entry->observaciones ?: '—' }}</div>
    </div>
</div>
@endsection

