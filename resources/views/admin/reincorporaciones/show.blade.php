@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('backpack::crud.preview') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    $entry->loadMissing([
        'empleado',
        'empleado.cliente',
        'empleado.sucursal',
        'actasIngreso.createdBy',
        'actasSeguimiento.createdBy',
    ]);
    $empleado = $entry->empleado;

    $fmtDate = function ($value) {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }
        if (is_string($value) && $value !== '') {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }
        return '';
    };

    $evidenciaUrl = null;
    if (! empty($entry->evidencia_pdf_path)) {
        $baseUrl = request()->getSchemeAndHttpHost();
        $evidenciaUrl = $baseUrl . '/storage/' . ltrim($entry->evidencia_pdf_path, '/');
    }

    $eventos = collect()
        ->push([
            'id' => $entry->id,
            'tipo' => 'Acta de reincorporación laboral',
            'fecha' => data_get($entry->acta_payload ?? [], 'fecha_acta') ?: $entry->fecha_ingreso,
            'creado_por' => null,
            'ruta' => backpack_url('reincorporacion/' . $entry->id . '/acta'),
            'evidencia' => $evidenciaUrl,
        ])
        ->merge($entry->actasIngreso->map(function ($acta) {
            return [
                'id' => $acta->id,
                'tipo' => 'Acta de ingreso',
                'fecha' => $acta->fecha_acta,
                'creado_por' => $acta->createdBy?->name ?? null,
                'ruta' => backpack_url('acta-ingreso/' . $acta->id . '/show'),
                'evidencia' => null,
            ];
        }))
        ->merge($entry->actasSeguimiento->map(function ($acta) {
            return [
                'id' => $acta->id,
                'tipo' => 'Acta de seguimiento',
                'fecha' => $acta->fecha_acta,
                'creado_por' => $acta->createdBy?->name ?? null,
                'ruta' => backpack_url('acta-seguimiento/' . $acta->id . '/show'),
                'evidencia' => null,
            ];
        }))
        ->sortByDesc('fecha')
        ->values();
@endphp

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">Reincorporación</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">Detalle</p>
            @if ($crud->hasAccess('list'))
                <p class="ms-2 ml-2 mb-0" bp-section="page-subheading-back-button">
                    <small><a href="{{ url($crud->route) }}" class="font-sm"><i class="la la-angle-double-left"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
                </p>
            @endif
        </section>
        <div></div>
    </div>
@endsection

@section('content')
<div class="row" bp-section="crud-operation-show">
    <div class="col-12">
        <div class="card p-4 mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Información personal</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Nombre</dt>
                        <dd class="col-8">{{ $empleado?->nombre }}</dd>
                        <dt class="col-4">Cédula</dt>
                        <dd class="col-8">{{ $empleado?->cedula }}</dd>
                        <dt class="col-4">Empresa</dt>
                        <dd class="col-8">{{ $empleado?->cliente?->nombre }}</dd>
                        <dt class="col-4">Planta</dt>
                        <dd class="col-8">{{ $empleado?->sucursal?->nombre }}</dd>
                        <dt class="col-4">Cargo</dt>
                        <dd class="col-8">{{ $empleado?->getCargoActual() }}</dd>
                        <dt class="col-4">Teléfono</dt>
                        <dd class="col-8">{{ $empleado?->telefono }}</dd>
                        <dt class="col-4">Correo</dt>
                        <dd class="col-8">{{ $empleado?->correo_electronico }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Reincorporación</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Estado</dt>
                        <dd class="col-8">{{ $entry->estado }}</dd>
                        <dt class="col-4">Origen</dt>
                        <dd class="col-8">{{ $entry->origen }}</dd>
                        <dt class="col-4">Fecha ingreso</dt>
                        <dd class="col-8">{{ $fmtDate($entry->fecha_ingreso) }}</dd>
                        <dt class="col-4">Recomendación</dt>
                        <dd class="col-8">{{ $entry->recomendacion_medica }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="mb-0">Historial de eventos</h5>
                    <span class="text-muted">Total: {{ $eventos->count() }}</span>
                </div>
                <a href="{{ backpack_url('acta-seguimiento/create?reincorporacion_id=' . $entry->id) }}" class="btn btn-sm btn-success"><i class="la la-plus"></i> Crear seguimiento</a>
            </div>

            @if ($eventos->isEmpty())
                <div class="text-muted">Aún no hay actas asociadas a esta reincorporación.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Generado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($eventos as $evento)
                                <tr>
                                    <td>{{ $evento['tipo'] }}</td>
                                    <td>{{ $fmtDate($evento['fecha']) }}</td>
                                    <td>{{ $evento['creado_por'] ?? '—' }}</td>
                                    <td class="d-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ $evento['ruta'] }}">Ver acta</a>
                                        @if ($evento['tipo'] === 'Acta de reincorporación laboral')
                                            <a class="btn btn-sm btn-outline-warning" href="{{ backpack_url('reincorporacion/' . $evento['id'] . '/edit') }}">Editar</a>
                                        @elseif ($evento['tipo'] === 'Acta de ingreso')
                                            <a class="btn btn-sm btn-outline-warning" href="{{ backpack_url('acta-ingreso/' . $evento['id'] . '/edit') }}">Editar</a>
                                        @else
                                            <a class="btn btn-sm btn-outline-warning" href="{{ backpack_url('acta-seguimiento/' . $evento['id'] . '/edit') }}">Editar</a>
                                        @endif
                                        @if ($evento['tipo'] === 'Acta de ingreso')
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ backpack_url('acta-ingreso/' . $evento['id'] . '/pdf') }}">Descargar PDF</a>
                                        @elseif ($evento['tipo'] === 'Acta de reincorporación laboral')
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ backpack_url('reincorporacion/' . $evento['id'] . '/acta-pdf') }}">Descargar PDF</a>
                                            @if (! empty($evento['evidencia']))
                                                <a class="btn btn-sm btn-outline-success"
                                                   href="{{ backpack_url('reincorporacion/' . $evento['id'] . '/evidencia') }}"
                                                   target="_blank"
                                                   onclick="var w=window.open(this.href,'_blank'); if(!w){window.location=this.href;} return false;">Evidencia firmada</a>
                                            @endif
                                        @else
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ backpack_url('acta-seguimiento/' . $evento['id'] . '/pdf') }}">Descargar PDF</a>
                                        @endif
                                    </td>
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
