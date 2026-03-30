@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('backpack::crud.preview') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    $entry->loadMissing(['programa', 'empleado', 'empleado.cliente', 'empleado.sucursal', 'incapacidades', 'historial.usuario']);

    $origen = strtolower((string) ($entry->origen ?? ''));
    $sugerido = strtolower((string) ($entry->sugerido_por ?? ''));
    $fuente = 'Manual';
    if (str_contains($origen, 'encuesta') || str_contains($sugerido, 'encuesta')) $fuente = 'Encuesta';
    elseif (str_contains($origen, 'incapacidad') || str_contains($sugerido, 'incapacidad')) $fuente = 'Incapacidad';
    elseif (str_contains($origen, 'examen') || str_contains($sugerido, 'examen')) $fuente = 'Examen periódico';
    elseif (str_contains($origen, 'cie10') || str_contains($sugerido, 'cie10')) $fuente = 'CIE10';
    elseif ($origen !== '') $fuente = ucfirst($origen);

    $alertaEncuesta = null;
    $respuestaEncuesta = null;
    if ($fuente === 'Encuesta') {
        $alertaEncuesta = \App\Models\EncuestaAlerta::where('empleado_id', $entry->empleado_id)
            ->where('programa_id', $entry->programa_id)
            ->latest('id')
            ->first();
        if ($alertaEncuesta) {
            $respuestaEncuesta = \App\Models\EncuestaRespuesta::with(['encuesta'])
                ->where('empleado_id', $entry->empleado_id)
                ->where('encuesta_id', $alertaEncuesta->encuesta_id)
                ->latest('respondido_en')
                ->first();
        }
    }
@endphp

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">Caso - {{ $entry->programa?->nombre }}</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">Estado: {{ $entry->estado }}</p>
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
                <div class="col-md-4">
                    <h5 class="mb-3">Información personal</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Nombre</dt>
                        <dd class="col-8">{{ $entry->empleado?->nombre }}</dd>
                        <dt class="col-4">Cédula</dt>
                        <dd class="col-8">{{ $entry->empleado?->cedula }}</dd>
                        <dt class="col-4">Empresa</dt>
                        <dd class="col-8">{{ $entry->empleado?->cliente?->nombre }}</dd>
                        <dt class="col-4">Planta</dt>
                        <dd class="col-8">{{ $entry->empleado?->sucursal?->nombre }}</dd>
                        <dt class="col-4">Cargo</dt>
                        <dd class="col-8">{{ $entry->empleado?->getCargoActual() }}</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Programa</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Programa</dt>
                        <dd class="col-8">{{ $entry->programa?->nombre }}</dd>
                        <dt class="col-4">Estado</dt>
                        <dd class="col-8">{{ $entry->estado }}</dd>
                        <dt class="col-4">Desde</dt>
                        <dd class="col-8">{{ $entry->fecha_inicio?->format('Y-m-d') ?? '—' }}</dd>
                        <dt class="col-4">Hasta</dt>
                        <dd class="col-8">{{ $entry->fecha_fin?->format('Y-m-d') ?? '—' }}</dd>
                        <dt class="col-4">Origen</dt>
                        <dd class="col-8">{{ $entry->origen }}</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Origen de alerta</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Sugerido por</dt>
                        <dd class="col-8">{{ $entry->sugerido_por }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        @if ($fuente === 'Encuesta')
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Encuesta</h5>
                    @if ($alertaEncuesta)
                        <a class="btn btn-sm btn-outline-primary" href="{{ backpack_url('encuesta-alerta/' . $alertaEncuesta->id . '/show') }}">Ver resultados</a>
                    @endif
                </div>
                @if (! $respuestaEncuesta)
                    <div class="text-muted">No se encontró respuesta para esta alerta.</div>
                @else
                    <div class="mb-2"><strong>Título:</strong> {{ $respuestaEncuesta->encuesta?->titulo }}</div>
                    <div class="mb-2"><strong>Respondido:</strong> {{ $respuestaEncuesta->respondido_en?->format('Y-m-d H:i') ?? '—' }}</div>
                    <div class="mb-2"><strong>Puntaje total:</strong> {{ $respuestaEncuesta->puntaje_total }}</div>
                @endif
            </div>
        @else
            <div class="card p-4">
                <h5 class="mb-3">Incapacidades asociadas</h5>
                @if ($entry->incapacidades->isEmpty())
                    <div class="text-muted">No hay incapacidades asociadas.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha inicio</th>
                                    <th>Fecha fin</th>
                                    <th>Días</th>
                                    <th>CIE10</th>
                                    <th>Diagnóstico</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entry->incapacidades->sortByDesc('fecha_inicio') as $inc)
                                    <tr>
                                        <td>{{ $inc->fecha_inicio?->format('Y-m-d') }}</td>
                                        <td>{{ $inc->fecha_fin?->format('Y-m-d') }}</td>
                                        <td>{{ $inc->dias ?? $inc->dias_incapacidad ?? '' }}</td>
                                        <td>{{ $inc->codigo_cie10 }}</td>
                                        <td>{{ $inc->diagnostico }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if ($entry->historial->isNotEmpty())
            <div class="card p-4 mt-4">
                <h5 class="mb-3">Historial de cambios</h5>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>De</th>
                                <th>A</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entry->historial->sortByDesc('created_at') as $item)
                                <tr>
                                    <td>{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ $item->estado_anterior ?? '—' }}</td>
                                    <td>{{ $item->estado_nuevo ?? '—' }}</td>
                                    <td>{{ $item->usuario?->name ?? '—' }}</td>
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
