@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.list') => false,
  ];

  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

  $summary = $crud->getOperationSetting('ipt_alerts_summary') ?? [];
  $today = $summary['today'] ?? \Carbon\Carbon::today();
  $indicators = $summary['indicators'] ?? [];
  $alerts = $summary['alerts'] ?? collect();
  $scopeLabel = $summary['scope_label'] ?? 'Global';
@endphp

@section('content')
  <div class="mb-3 d-flex justify-content-between align-items-center">
    <h3 class="mb-0">Inspecciones IPT</h3>
    <span class="badge bg-light text-dark border">Alcance: {{ $scopeLabel }}</span>
  </div>

  @if(($indicators['vencidas'] ?? 0) > 0)
    <div class="alert alert-warning py-2">
      <strong>Atención:</strong> tienes {{ $indicators['vencidas'] }} seguimiento(s) IPT vencido(s).
    </div>
  @endif

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Inspecciones IPT</div><div class="h4 mb-0">{{ $indicators['total'] ?? 0 }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Abiertas</div><div class="h4 mb-0">{{ $indicators['abiertas'] ?? 0 }}</div></div></div>
    </div>
    <div class="col-md-2">
      <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Vencidas</div><div class="h4 mb-0 text-danger">{{ $indicators['vencidas'] ?? 0 }}</div></div></div>
    </div>
    <div class="col-md-2">
      <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Próx. 7 días</div><div class="h4 mb-0 text-warning">{{ $indicators['proximas_7'] ?? 0 }}</div></div></div>
    </div>
    <div class="col-md-2">
      <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Próx. 30 días</div><div class="h4 mb-0 text-primary">{{ $indicators['proximas_30'] ?? 0 }}</div></div></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="mb-3">Alertas IPT próximas a vencer</h5>
      @if($alerts->isEmpty())
        <div class="text-muted">No hay alertas de seguimiento en los próximos 30 días para tu alcance.</div>
      @else
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Persona</th>
                <th>Tipo</th>
                <th>Puntaje / Riesgo</th>
                <th>Fecha seguimiento</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($alerts as $ipt)
                @php
                  $fecha = $ipt->fecha_proximo_seguimiento_sugerida;
                  $days = $fecha ? $today->diffInDays($fecha, false) : null;
                @endphp
                <tr>
                  <td>{!! \App\Support\EmpleadoLink::render($ipt->empleado, trim(($ipt->empleado?->nombre ?? '') . ' · ' . ($ipt->empleado?->cedula ?? ''))) !!}</td>
                  <td>{{ $ipt->tipo === 'followup' ? 'Seguimiento' : 'Inicial' }}</td>
                  <td>{{ $ipt->puntaje_total }} · {{ strtoupper((string) $ipt->nivel_riesgo) }}</td>
                  <td>
                    {{ optional($fecha)->format('Y-m-d') }}
                    @if(!is_null($days))
                      @if($days < 0)
                        <span class="badge bg-danger ms-1">{{ abs($days) }}d vencida</span>
                      @elseif($days <= 7)
                        <span class="badge bg-warning text-dark ms-1">{{ $days }}d</span>
                      @else
                        <span class="badge bg-info text-dark ms-1">{{ $days }}d</span>
                      @endif
                    @endif
                  </td>
                  <td>{{ ucfirst((string) $ipt->estado) }}</td>
                  <td><a href="{{ backpack_url('ipt-inspection/' . $ipt->id . '/show') }}" class="btn btn-sm btn-outline-secondary">Ver</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <div class="row" bp-section="crud-operation-list">
    <div class="{{ $crud->getListContentClass() }}">
      <x-backpack::datatable :controller="$controller" :crud="$crud" :modifiesUrl="true" />
    </div>
  </div>
@endsection
