@extends(backpack_view('blank'))

@section('content')
@php
    $entry = $crud->entry->loadMissing(['cliente', 'sections.fields']);
@endphp
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ $entry->nombre_publico }}</h4>
        <a class="btn btn-sm btn-primary" href="{{ backpack_url('osteo-template/' . $entry->id . '/builder') }}">Constructor</a>
    </div>
    <div class="mb-3">
        <span class="badge bg-light text-dark">{{ $entry->cliente?->nombre }}</span>
        @if($entry->codigo)<span class="badge bg-light text-dark">{{ $entry->codigo }}</span>@endif
        @if($entry->segmento)<span class="badge bg-light text-dark">{{ $entry->segmento }}</span>@endif
        <span class="badge {{ $entry->activo ? 'bg-success' : 'bg-secondary' }}">{{ $entry->activo ? 'Activa' : 'Inactiva' }}</span>
    </div>

    @foreach($entry->sections->sortBy('orden') as $section)
        <div class="card border p-3 mb-3">
            <h6 class="mb-2">{{ $section->titulo }}</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Campo</th><th>Tipo</th><th>Key</th><th>Req.</th></tr></thead>
                    <tbody>
                    @foreach($section->fields->sortBy('orden') as $field)
                        <tr>
                            <td>{{ $field->label }}</td>
                            <td>{{ $field->tipo }}</td>
                            <td>{{ $field->key_name }}</td>
                            <td>{{ $field->required ? 'Sí' : 'No' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection

