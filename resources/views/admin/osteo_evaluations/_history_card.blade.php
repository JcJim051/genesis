<div class="{{ $cardClass ?? 'card p-4 mt-4' }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Historial Valoración Osteomuscular</h5>
        @if(!empty($createUrl))
            <a class="btn btn-sm btn-outline-primary" href="{{ $createUrl }}">
                <i class="la la-plus"></i> Crear valoración
            </a>
        @endif
    </div>

    @if($evaluations->isEmpty())
        <div class="text-muted">No hay valoraciones registradas.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    @if(!empty($showPersonaColumn))
                        <th>Persona</th>
                    @endif
                    <th>Fecha</th>
                    <th>Plantilla</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach($evaluations as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        @if(!empty($showPersonaColumn))
                            <td>{!! \App\Support\EmpleadoLink::render($item->empleado, trim(($item->empleado?->nombre ?? '') . ' · ' . ($item->empleado?->cedula ?? ''))) !!}</td>
                        @endif
                        <td>{{ optional($item->fecha_valoracion)->format('Y-m-d') }}</td>
                        <td>{{ $item->template?->nombre_publico ?? '—' }}</td>
                        <td>{{ ucfirst((string) $item->estado) }}</td>
                        <td>
                            <a href="{{ backpack_url('osteo-evaluation/' . $item->id . '/show') }}">Ver</a>
                            <a href="{{ backpack_url('osteo-evaluation/' . $item->id . '/edit') }}" class="ms-2">Editar</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

