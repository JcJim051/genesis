@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('backpack::crud.preview') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    $entry->loadMissing(['envio.pausa', 'empleado', 'items.pregunta', 'items.opcion']);
@endphp

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">Participación</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">{{ $entry->envio?->pausa?->nombre }}</p>
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
                    <h5 class="mb-3">Persona</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Nombre</dt>
                        <dd class="col-8">{{ $entry->empleado?->nombre }}</dd>
                        <dt class="col-4">Cédula</dt>
                        <dd class="col-8">{{ $entry->empleado?->cedula }}</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Pausa</h5>
                    <dl class="row mb-0">
                        <dt class="col-4">Nombre</dt>
                        <dd class="col-8">{{ $entry->envio?->pausa?->nombre }}</dd>
                        <dt class="col-4">Estado</dt>
                        <dd class="col-8">{{ $entry->estado }}</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Métricas</h5>
                    <dl class="row mb-0">
                        <dt class="col-5">Tiempo activo</dt>
                        <dd class="col-7">{{ $entry->tiempo_activo_total }} s</dd>
                        <dt class="col-5">Cambios pestaña</dt>
                        <dd class="col-7">{{ $entry->tab_switch_count }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <h5 class="mb-3">Respuestas</h5>
            @if ($entry->items->isEmpty())
                <div class="text-muted">Sin respuestas.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Pregunta</th>
                                <th>Respuesta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entry->items as $item)
                                <tr>
                                    <td>{{ $item->pregunta?->texto }}</td>
                                    <td>{{ $item->opcion?->texto ?? $item->respuesta_texto }}</td>
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
