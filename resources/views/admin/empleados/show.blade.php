@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('backpack::crud.preview') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    $entry->loadMissing([
        'cliente',
        'sucursal',
        'cargos',
        'areas',
        'programaCasos.programa',
        'programaCasos.incapacidades',
        'reincorporaciones',
        'pausaParticipaciones.envio.pausa',
        'encuestaRespuestas.encuesta',
    ]);

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

    $programaCasos = $entry->programaCasos
        ->sortBy(function ($caso) {
            return $caso->programa?->nombre ?? '';
        })
        ->values();
    $reincorporaciones = $entry->reincorporaciones->sortByDesc('fecha_ingreso')->values();
    $totalCasos = $programaCasos->count() + $reincorporaciones->count();
    $pausas = $entry->pausaParticipaciones->sortByDesc('created_at')->values();
    $encuestas = $entry->encuestaRespuestas->sortByDesc('created_at')->values();
@endphp

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">Persona</h1>
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
                <div class="col-md-4">
                    <h5 class="mb-3">Información personal</h5>
                    <dl class="row mb-0">
                        <dt class="col-5">Nombre</dt>
                        <dd class="col-7">{{ $entry->nombre }}</dd>
                        <dt class="col-5">Cédula</dt>
                        <dd class="col-7">{{ $entry->cedula }}</dd>
                        <dt class="col-5">Género</dt>
                        <dd class="col-7">{{ $entry->genero }}</dd>
                        <dt class="col-5">Edad</dt>
                        <dd class="col-7">{{ $entry->edad }}</dd>
                        <dt class="col-5">Lateralidad</dt>
                        <dd class="col-7">{{ $entry->lateralidad }}</dd>
                        <dt class="col-5">Nacimiento</dt>
                        <dd class="col-7">{{ $fmtDate($entry->fecha_nacimiento) }}</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Contacto</h5>
                    <dl class="row mb-0">
                        <dt class="col-5">Teléfono</dt>
                        <dd class="col-7">{{ $entry->telefono }}</dd>
                        <dt class="col-5">Correo</dt>
                        <dd class="col-7">{{ $entry->correo_electronico }}</dd>
                        <dt class="col-5">Dirección</dt>
                        <dd class="col-7">{{ $entry->direccion }}</dd>
                        <dt class="col-5">EPS</dt>
                        <dd class="col-7">{{ $entry->eps }}</dd>
                        <dt class="col-5">ARL</dt>
                        <dd class="col-7">{{ $entry->arl }}</dd>
                        <dt class="col-5">Fondo</dt>
                        <dd class="col-7">{{ $entry->fondo_pensiones }}</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Empresa y laboral</h5>
                    <dl class="row mb-0">
                        <dt class="col-5">Empresa</dt>
                        <dd class="col-7">{{ $entry->cliente?->nombre }}</dd>
                        <dt class="col-5">Planta</dt>
                        <dd class="col-7">{{ $entry->sucursal?->nombre }}</dd>
                        <dt class="col-5">Tipo contrato</dt>
                        <dd class="col-7">{{ $entry->tipo_contrato }}</dd>
                        <dt class="col-5">Ingreso</dt>
                        <dd class="col-7">{{ $fmtDate($entry->fecha_ingreso) }}</dd>
                        <dt class="col-5">Retiro</dt>
                        <dd class="col-7">{{ $fmtDate($entry->fecha_retiro) }}</dd>
                        <dt class="col-5">Cargo actual</dt>
                        <dd class="col-7">{{ $entry->getCargoActual() }}</dd>
                        <dt class="col-5">Área actual</dt>
                        <dd class="col-7">{{ $entry->getAreaActual() }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <h5 class="mb-3">Historial</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-2">Cargos</h6>
                    @if (trim(strip_tags($entry->getCargosHistorial())) === '')
                        <div class="text-muted">Sin historial de cargos.</div>
                    @else
                        {!! $entry->getCargosHistorial() !!}
                    @endif
                </div>
                <div class="col-md-6">
                    <h6 class="mb-2">Áreas</h6>
                    @if (trim(strip_tags($entry->getAreasHistorial())) === '')
                        <div class="text-muted">Sin historial de áreas.</div>
                    @else
                        {!! $entry->getAreasHistorial() !!}
                    @endif
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Programas y casos</h5>
                <span class="text-muted">Total: {{ $totalCasos }}</span>
            </div>

            @if ($programaCasos->isEmpty() && $reincorporaciones->isEmpty())
                <div class="text-muted">Esta persona no tiene casos asociados.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Programa</th>
                                <th>Estado</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Origen</th>
                                <th>Incapacidades origen</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($programaCasos as $caso)
                                @php
                                    $incaps = $caso->incapacidades ?? collect();
                                    $incapText = $incaps->take(3)->map(function ($incap) use ($fmtDate) {
                                        $cie10 = $incap->codigo_cie10 ? $incap->codigo_cie10 . ' - ' : '';
                                        $diag = $incap->diagnostico ?? '';
                                        $fecha = $fmtDate($incap->fecha_inicio);
                                        return trim($cie10 . $diag) . ($fecha ? ' (' . $fecha . ')' : '');
                                    })->filter()->values();
                                    $extra = $incaps->count() - $incapText->count();
                                @endphp
                                <tr>
                                    <td>{{ $caso->programa?->nombre }}</td>
                                    <td>{{ $caso->estado }}</td>
                                    <td>{{ $caso->fecha_inicio?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $caso->fecha_fin?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $caso->origen }}</td>
                                    <td>
                                        @if ($incapText->isEmpty())
                                            <span class="text-muted">Sin incapacidad</span>
                                        @else
                                            {{ $incapText->join(' | ') }}
                                            @if ($extra > 0)
                                                <span class="text-muted">(+{{ $extra }} más)</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ backpack_url('programa-caso/' . $caso->id . '/show') }}">
                                            Ver historial
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($reincorporaciones as $reincorporacion)
                                <tr>
                                    <td>Reincorporación</td>
                                    <td>{{ $reincorporacion->estado }}</td>
                                    <td>{{ $reincorporacion->origen }}</td>
                                    <td><span class="text-muted">—</span></td>
                                    <td><span class="text-muted">—</span></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ backpack_url('reincorporacion/' . $reincorporacion->id . '/show') }}">
                                            Ver acta
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card p-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Pausas activas</h5>
                <span class="text-muted">Total: {{ $pausas->count() }}</span>
            </div>

            @if ($pausas->isEmpty())
                <div class="text-muted">Esta persona no tiene participaciones en pausas activas.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Pausa</th>
                                <th>Estado</th>
                                <th>Tiempo activo (s)</th>
                                <th>Cambios pestaña</th>
                                <th>Fecha</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pausas as $p)
                                <tr>
                                    <td>{{ $p->envio?->pausa?->nombre }}</td>
                                    <td>{{ $p->estado }}</td>
                                    <td>{{ $p->tiempo_activo_total }}</td>
                                    <td>{{ $p->tab_switch_count }}</td>
                                    <td>{{ $p->respondido_en?->format('Y-m-d H:i') ?? $p->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ backpack_url('pausa-participacion/' . $p->id . '/show') }}">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card p-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Encuestas contestadas</h5>
                <span class="text-muted">Total: {{ $encuestas->count() }}</span>
            </div>

            @if ($encuestas->isEmpty())
                <div class="text-muted">Esta persona no tiene encuestas contestadas.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Encuesta</th>
                                <th>Estado</th>
                                <th>Puntaje</th>
                                <th>Fecha</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($encuestas as $r)
                                <tr>
                                    <td>{{ $r->encuesta?->titulo }}</td>
                                    <td>{{ $r->estado }}</td>
                                    <td>{{ $r->puntaje_total }}</td>
                                    <td>{{ $r->respondido_en?->format('Y-m-d H:i') ?? $r->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ backpack_url('encuesta-participacion/' . $r->id . '/show') }}">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($entry->telegram_chat_id)
            <div class="card p-4 mt-4">
                <h5 class="mb-2">Telegram</h5>
                <div class="text-muted mb-2">chat_id registrado: {{ $entry->telegram_chat_id }}</div>
                <form method="POST" action="{{ backpack_url('empleado/' . $entry->id . '/telegram-unlink') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger" type="submit">Desvincular Telegram</button>
                </form>
            </div>
        @else
            <div class="card p-4 mt-4">
                <h5 class="mb-2">Activar Telegram</h5>
                <div class="text-muted mb-2">El empleado aún no tiene chat_id registrado.</div>
                @php
                    $startLink = $entry->getTelegramActivationUrl();
                @endphp
                <button class="btn btn-sm btn-outline-success" type="button" data-copy-link="{{ $startLink }}">
                    Copiar link
                </button>
                <span class="text-success small ms-2 d-none" id="copy-link-success">Copiado</span>
            </div>
        @endif
    </div>
</div>

@push('after_scripts')
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-copy-link]');
    if (!btn) return;
    const link = btn.getAttribute('data-copy-link');
    const ok = document.getElementById('copy-link-success');

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link).then(() => {
            if (ok) {
                ok.classList.remove('d-none');
                setTimeout(() => ok.classList.add('d-none'), 2000);
            }
        });
    } else {
        const input = document.createElement('input');
        input.value = link;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
        if (ok) {
            ok.classList.remove('d-none');
            setTimeout(() => ok.classList.add('d-none'), 2000);
        }
    }
});
</script>
@endpush
@endsection
