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
        'pausaStats',
        'pausaBadges',
        'encuestaRespuestas.encuesta',
        'iptInspections.template',
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
    $iptInspections = $entry->iptInspections->sortByDesc('fecha_inspeccion')->values();
    $pausaStats = $entry->pausaStats;
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
<div class="row persona-show" bp-section="crud-operation-show">
    <div class="col-12">
        <style>
            .persona-show dt { color: #6c757d; font-weight: 600; margin-bottom: .15rem; }
            .persona-show dd { margin-bottom: .5rem; }
            .persona-kpi { border: 1px solid #e9ecef; border-radius: .5rem; background: #fff; padding: .75rem .9rem; }
            .persona-kpi .value { font-size: 1.15rem; font-weight: 700; line-height: 1; }
            .persona-kpi .label { color: #6c757d; font-size: .8rem; margin-top: .35rem; }
            .persona-card-title { font-size: 1rem; font-weight: 700; margin-bottom: .75rem; }
            .table-compact th,
            .table-compact td { font-size: .82rem; vertical-align: middle; padding: .35rem .45rem; }
            .table-compact a.link-ver { font-size: .78rem; font-weight: 600; text-decoration: none; }
            .table-pausas th,
            .table-pausas td { font-size: .82rem; vertical-align: middle; padding: .35rem .45rem; }
            .table-pausas { table-layout: fixed; width: 100%; }
            .table-pausas th:nth-child(1),
            .table-pausas td:nth-child(1) {
                width: 28%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .table-pausas th:nth-child(2),
            .table-pausas td:nth-child(2) { width: 14%; white-space: nowrap; }
            .table-pausas th:nth-child(3),
            .table-pausas td:nth-child(3),
            .table-pausas th:nth-child(4),
            .table-pausas td:nth-child(4) { width: 14%; text-align: center; }
            .table-pausas th:nth-child(5),
            .table-pausas td:nth-child(5) { width: 20%; white-space: nowrap; }
            .table-pausas th:nth-child(6),
            .table-pausas td:nth-child(6) { width: 10%; text-align: center; white-space: nowrap; }
            .table-pausas a.detalle-link { font-size: .78rem; font-weight: 600; text-decoration: none; display: inline-block; }
            .table-casos-wrap .table { width: 100%; table-layout: fixed; }
            .table-casos-wrap th,
            .table-casos-wrap td {
                font-size: .82rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }
            .table-casos-wrap th:nth-child(1), .table-casos-wrap td:nth-child(1) { width: 18%; }
            .table-casos-wrap th:nth-child(2), .table-casos-wrap td:nth-child(2) { width: 12%; }
            .table-casos-wrap th:nth-child(3), .table-casos-wrap td:nth-child(3) { width: 12%; }
            .table-casos-wrap th:nth-child(4), .table-casos-wrap td:nth-child(4) { width: 12%; }
            .table-casos-wrap th:nth-child(5), .table-casos-wrap td:nth-child(5) { width: 12%; }
            .table-casos-wrap th:nth-child(6), .table-casos-wrap td:nth-child(6) { width: 24%; }
            .table-casos-wrap th:nth-child(7), .table-casos-wrap td:nth-child(7) { width: 10%; text-align: center; }
            @media (max-width: 1600px) {
                .table-casos-wrap th:nth-child(6), .table-casos-wrap td:nth-child(6) { display: none; }
                .table-casos-wrap th:nth-child(1), .table-casos-wrap td:nth-child(1) { width: 24%; }
                .table-casos-wrap th:nth-child(2), .table-casos-wrap td:nth-child(2) { width: 14%; }
                .table-casos-wrap th:nth-child(3), .table-casos-wrap td:nth-child(3) { width: 16%; }
                .table-casos-wrap th:nth-child(4), .table-casos-wrap td:nth-child(4) { width: 16%; }
                .table-casos-wrap th:nth-child(5), .table-casos-wrap td:nth-child(5) { width: 16%; }
                .table-casos-wrap th:nth-child(7), .table-casos-wrap td:nth-child(7) { width: 14%; }
            }
            @media (max-width: 1300px) {
                .table-casos-wrap th:nth-child(5), .table-casos-wrap td:nth-child(5) { display: none; }
                .table-casos-wrap th:nth-child(1), .table-casos-wrap td:nth-child(1) { width: 30%; }
                .table-casos-wrap th:nth-child(2), .table-casos-wrap td:nth-child(2) { width: 16%; }
                .table-casos-wrap th:nth-child(3), .table-casos-wrap td:nth-child(3) { width: 22%; }
                .table-casos-wrap th:nth-child(4), .table-casos-wrap td:nth-child(4) { width: 20%; }
                .table-casos-wrap th:nth-child(7), .table-casos-wrap td:nth-child(7) { width: 12%; }
            }
            @media (max-width: 1100px) {
                .table-casos-wrap th:nth-child(4), .table-casos-wrap td:nth-child(4) { display: none; }
                .table-casos-wrap th:nth-child(1), .table-casos-wrap td:nth-child(1) { width: 38%; }
                .table-casos-wrap th:nth-child(2), .table-casos-wrap td:nth-child(2) { width: 20%; }
                .table-casos-wrap th:nth-child(3), .table-casos-wrap td:nth-child(3) { width: 26%; }
                .table-casos-wrap th:nth-child(7), .table-casos-wrap td:nth-child(7) { width: 16%; }
            }
            .telegram-inline {
                border: 1px solid #e9ecef;
                border-radius: .6rem;
                background: #f8fafc;
                padding: .6rem .75rem;
                margin-top: .75rem;
            }
            .telegram-inline .telegram-label { font-size: .78rem; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: .02em; }
            .telegram-inline .telegram-state { font-size: .85rem; color: #495057; }
        </style>

        <div class="card p-4 mb-3 persona-header-card">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="mb-1">{{ $entry->nombre ?: 'Sin nombre' }}</h4>
                    <div class="text-muted">CC {{ $entry->cedula ?: '—' }} · {{ $entry->cliente?->nombre ?: 'Sin empresa' }} @if($entry->sucursal?->nombre) / {{ $entry->sucursal->nombre }} @endif</div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Tipo contrato</div>
                    <div>{{ $entry->tipo_contrato ?: '—' }}</div>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-lg-2 col-md-4 col-6"><div class="persona-kpi"><div class="value">{{ $programaCasos->count() }}</div><div class="label">Casos programa</div></div></div>
                <div class="col-lg-2 col-md-4 col-6"><div class="persona-kpi"><div class="value">{{ $reincorporaciones->count() }}</div><div class="label">Reincorporaciones</div></div></div>
                <div class="col-lg-2 col-md-4 col-6"><div class="persona-kpi"><div class="value">{{ $iptInspections->count() }}</div><div class="label">Inspecciones IPT</div></div></div>
                <div class="col-lg-2 col-md-4 col-6"><div class="persona-kpi"><div class="value">{{ $pausas->count() }}</div><div class="label">Pausas</div></div></div>
                <div class="col-lg-2 col-md-4 col-6"><div class="persona-kpi"><div class="value">{{ $encuestas->count() }}</div><div class="label">Encuestas</div></div></div>
                <div class="col-lg-2 col-md-4 col-6"><div class="persona-kpi"><div class="value">{{ $entry->telegram_chat_id ? 'Sí' : 'No' }}</div><div class="label">Telegram</div></div></div>
            </div>

            <div class="telegram-inline d-flex flex-wrap align-items-center gap-2">
                <span class="telegram-label">Telegram</span>
                @if ($entry->telegram_chat_id)
                    <span class="telegram-state">Vinculado · chat_id {{ $entry->telegram_chat_id }}</span>
                    <form class="ms-auto" method="POST" action="{{ backpack_url('empleado/' . $entry->id . '/telegram-unlink') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" type="submit">Desvincular</button>
                    </form>
                @else
                    @php $startLink = $entry->getTelegramActivationUrl(); @endphp
                    <span class="telegram-state">Pendiente de activación.</span>
                    <button class="btn btn-sm btn-outline-success" type="button" data-copy-link="{{ $startLink }}">Copiar link</button>
                    @if ($entry->correo_electronico)
                        <form method="POST" action="{{ backpack_url('empleado/' . $entry->id . '/telegram-email') }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit">Enviar correo</button>
                        </form>
                    @endif
                    <span class="text-success small d-none" id="copy-link-success">Copiado</span>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card p-4 h-100">
                    <div class="persona-card-title">Datos personales</div>
                    <dl class="row mb-0">
                        <dt class="col-6">Género</dt><dd class="col-6">{{ $entry->genero ?: '—' }}</dd>
                        <dt class="col-6">Edad</dt><dd class="col-6">{{ $entry->edad ?: '—' }}</dd>
                        <dt class="col-6">Lateralidad</dt><dd class="col-6">{{ $entry->lateralidad ?: '—' }}</dd>
                        <dt class="col-6">Nacimiento</dt><dd class="col-6">{{ $fmtDate($entry->fecha_nacimiento) ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card p-4 h-100">
                    <div class="persona-card-title">Contacto y seguridad social</div>
                    <dl class="row mb-0">
                        <dt class="col-6">Teléfono</dt><dd class="col-6">{{ $entry->telefono ?: '—' }}</dd>
                        <dt class="col-6">Correo</dt><dd class="col-6">{{ $entry->correo_electronico ?: '—' }}</dd>
                        <dt class="col-6">Dirección</dt><dd class="col-6">{{ $entry->direccion ?: '—' }}</dd>
                        <dt class="col-6">EPS</dt><dd class="col-6">{{ $entry->eps ?: '—' }}</dd>
                        <dt class="col-6">ARL</dt><dd class="col-6">{{ $entry->arl ?: '—' }}</dd>
                        <dt class="col-6">Fondo</dt><dd class="col-6">{{ $entry->fondo_pensiones ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card p-4 h-100">
                    <div class="persona-card-title">Laboral</div>
                    <dl class="row mb-0">
                        <dt class="col-6">Empresa</dt><dd class="col-6">{{ $entry->cliente?->nombre ?: '—' }}</dd>
                        <dt class="col-6">Planta</dt><dd class="col-6">{{ $entry->sucursal?->nombre ?: '—' }}</dd>
                        <dt class="col-6">Ingreso</dt><dd class="col-6">{{ $fmtDate($entry->fecha_ingreso) ?: '—' }}</dd>
                        <dt class="col-6">Retiro</dt><dd class="col-6">{{ $fmtDate($entry->fecha_retiro) ?: '—' }}</dd>
                        <dt class="col-6">Cargo actual</dt><dd class="col-6">{{ $entry->getCargoActual() ?: '—' }}</dd>
                        <dt class="col-6">Área actual</dt><dd class="col-6">{{ $entry->getAreaActual() ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="persona-card-title mb-0">Programas y casos</div>
                        <span class="text-muted">Total: {{ $totalCasos }}</span>
                    </div>

                    @if ($programaCasos->isEmpty() && $reincorporaciones->isEmpty())
                        <div class="text-muted">Esta persona no tiene casos asociados.</div>
                    @else
                        <div class="table-responsive table-casos-wrap">
                            <table class="table table-striped table-sm mb-0 table-compact">
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
                                            <td>{{ $caso->origen ?: '—' }}</td>
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
                                            <td><a class="link-ver" href="{{ backpack_url('programa-caso/' . $caso->id . '/show') }}">Ver</a></td>
                                        </tr>
                                    @endforeach
                                    @foreach ($reincorporaciones as $reincorporacion)
                                        <tr>
                                            <td>Reincorporación</td>
                                            <td>{{ $reincorporacion->estado }}</td>
                                            <td>{{ $fmtDate($reincorporacion->fecha_ingreso) ?: '—' }}</td>
                                            <td>—</td>
                                            <td>{{ $reincorporacion->origen ?: '—' }}</td>
                                            <td>—</td>
                                            <td><a class="link-ver" href="{{ backpack_url('reincorporacion/' . $reincorporacion->id . '/show') }}">Ver</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                @include('admin.ipt_inspections._history_card', [
                    'iptInspections' => $iptInspections,
                    'createInitialUrl' => null,
                    'showPersonaColumn' => false,
                    'cardClass' => 'card p-4 h-100',
                ])
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="persona-card-title mb-0">Pausas y gamificación</div>
                        <span class="text-muted">Total: {{ $pausas->count() }}</span>
                    </div>

                    @if ($pausas->isEmpty())
                        <div class="text-muted">Esta persona no tiene participaciones en pausas activas.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0 table-compact table-pausas">
                                <thead>
                                    <tr>
                                        <th>Pausa</th>
                                        <th>Estado</th>
                                        <th>Tiempo (s)</th>
                                        <th>Cambios</th>
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
                                            <td title="{{ $p->respondido_en?->format('Y-m-d H:i') ?? $p->created_at?->format('Y-m-d H:i') }}">
                                                {{ $p->respondido_en?->format('Y-m-d') ?? $p->created_at?->format('Y-m-d') }}
                                            </td>
                                            <td><a class="detalle-link" href="{{ backpack_url('pausa-participacion/' . $p->id . '/show') }}">Ver</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-3 pt-3 border-top">
                        <div class="text-muted small mb-2">Gamificación · Racha semanal (meta: 3 pausas)</div>
                        @if (! $pausaStats)
                            <div class="text-muted">Aún no hay datos de gamificación para esta persona.</div>
                        @else
                            <div class="row g-2">
                                <div class="col-md-3 col-6"><div class="persona-kpi"><div class="value">{{ $pausaStats->total_points }}</div><div class="label">Puntos</div></div></div>
                                <div class="col-md-3 col-6"><div class="persona-kpi"><div class="value">{{ $pausaStats->total_completadas }}</div><div class="label">Completadas</div></div></div>
                                <div class="col-md-3 col-6"><div class="persona-kpi"><div class="value">{{ $pausaStats->current_streak_weeks }}</div><div class="label">Racha actual</div></div></div>
                                <div class="col-md-3 col-6"><div class="persona-kpi"><div class="value">{{ $pausaStats->best_streak_weeks }}</div><div class="label">Mejor racha</div></div></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="persona-card-title mb-0">Encuestas contestadas</div>
                        <span class="text-muted">Total: {{ $encuestas->count() }}</span>
                    </div>

                    @if ($encuestas->isEmpty())
                        <div class="text-muted">Esta persona no tiene encuestas contestadas.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0 table-compact">
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
                                            <td><a class="link-ver" href="{{ backpack_url('encuesta-participacion/' . $r->id . '/show') }}">Ver</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3 mt-3 mb-3">
            <div class="col-12">
                <div class="card p-4 h-100">
                    <div class="persona-card-title">Historial de cargos y áreas</div>
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
            </div>
        </div>
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
