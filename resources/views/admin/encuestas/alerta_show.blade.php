@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('backpack::crud.preview') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    $entry->loadMissing(['empleado', 'programa', 'encuesta']);

    $respuesta = \App\Models\EncuestaRespuesta::with([
        'items.pregunta',
        'items.opcion',
        'encuesta',
    ])
        ->where('encuesta_id', $entry->encuesta_id)
        ->where('empleado_id', $entry->empleado_id)
        ->latest('respondido_en')
        ->first();
@endphp

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">Alerta de encuesta</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">Detalle</p>
            @if ($crud->hasAccess('list'))
                <p class="ms-2 ml-2 mb-0" bp-section="page-subheading-back-button">
                    <small><a href="{{ url($crud->route) }}" class="font-sm"><i class="la la-angle-double-left"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
                </p>
            @endif
        </section>
    </div>
@endsection

@section('content')
<div class="row" bp-section="crud-operation-show">
    <div class="col-12">
        <div class="card p-4 mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Resumen</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Persona</dt>
                        <dd class="col-8">{{ $entry->empleado?->nombre }}</dd>
                        <dt class="col-4">Programa</dt>
                        <dd class="col-8">{{ $entry->programa?->nombre }}</dd>
                        <dt class="col-4">Encuesta</dt>
                        <dd class="col-8">{{ $entry->encuesta?->titulo }}</dd>
                        <dt class="col-4">Puntaje</dt>
                        <dd class="col-8">{{ $entry->puntaje }}</dd>
                        <dt class="col-4">Estado</dt>
                        <dd class="col-8">{{ $entry->estado }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Resultado</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Respondido</dt>
                        <dd class="col-8">{{ $respuesta?->respondido_en?->format('Y-m-d H:i') ?? '—' }}</dd>
                        <dt class="col-4">Estado respuesta</dt>
                        <dd class="col-8">{{ $respuesta?->estado ?? '—' }}</dd>
                        <dt class="col-4">Umbral</dt>
                        <dd class="col-8">{{ $respuesta?->encuesta?->umbral_puntaje ?? '—' }}</dd>
                        <dt class="col-4">Puntaje total</dt>
                        <dd class="col-8">{{ $respuesta?->puntaje_total ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <h5 class="mb-3">Respuestas</h5>
            @if (! $respuesta)
                <div class="text-muted">No se encontró respuesta para esta alerta.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Pregunta</th>
                                <th>Respuesta</th>
                                <th>Puntaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($respuesta->items->sortBy('pregunta.orden') as $item)
                                <tr>
                                    <td>{{ $item->pregunta?->texto }}</td>
                                    <td>{{ $item->opcion?->texto }}</td>
                                    <td>{{ $item->puntaje }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
