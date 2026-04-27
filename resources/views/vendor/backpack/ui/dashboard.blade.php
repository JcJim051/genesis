@extends(backpack_view('blank'))

@php
    $metrics = app(\App\Support\DashboardMetrics::class)->build();
@endphp

@push('after_styles')
<style>
    .programs-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: 1fr;
    }
    @media (min-width: 768px) {
        .programs-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 1200px) {
        .programs-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }
    .dashboard-metric-label {
        white-space: nowrap;
        font-size: .78rem;
        line-height: 1.2;
    }
    .dashboard-section-header {
        background: #fff;
        border: 0;
        padding: .9rem 1rem .7rem;
    }
    .dashboard-section-title {
        margin: 0;
        text-align: center;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.2;
    }
</style>
@endpush

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-light border mb-0 d-flex justify-content-between align-items-center">
                <div>
                    <strong>Resumen Ejecutivo SST</strong>
                    <div class="text-muted small">Indicadores dinámicos según la vista seleccionada.</div>
                </div>
                <span class="badge bg-light text-dark border">{{ $metrics['scope_label'] }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 dashboard-metric-label">Personal activo</div>
                    <div class="fs-2 fw-bold">{{ number_format($metrics['active_employees']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 dashboard-metric-label">Casos confirmados</div>
                    <div class="fs-2 fw-bold text-success">{{ number_format($metrics['cases_confirmado_total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 dashboard-metric-label">Casos no evaluados</div>
                    <div class="fs-2 fw-bold text-warning">{{ number_format($metrics['cases_no_evaluado']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 dashboard-metric-label">Reincorp. activas</div>
                    <div class="fs-2 fw-bold text-info">{{ number_format($metrics['reincorporaciones']['active']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 dashboard-metric-label">Inspecciones IPT</div>
                    <div class="fs-2 fw-bold text-success">{{ number_format($metrics['ipt']['total']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header dashboard-section-header">
                    <h5 class="dashboard-section-title">Casos por Programa</h5>
                </div>
                <div class="card-body">
                    @if(empty($metrics['programs']))
                        <p class="text-muted mb-0">Sin datos para la vista seleccionada.</p>
                    @else
                        <div class="programs-grid">
                            @foreach($metrics['programs'] as $program)
                                <div class="border rounded p-3 h-100 bg-light-subtle">
                                    <div class="fw-semibold mb-2">{{ $program['programa_nombre'] }}</div>
                                    <div class="small text-muted">No evaluado: <strong>{{ number_format($program['no_evaluado']) }}</strong></div>
                                    <div class="small text-muted">Confirmados: <strong>{{ number_format($program['confirmado']) }}</strong></div>
                                    <div class="small text-muted">Probables: <strong>{{ number_format($program['probable']) }}</strong></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-section-header">
                    <h5 class="dashboard-section-title">Reincorporaciones - Seguimiento</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Total activas</div>
                                <div class="fs-4 fw-bold text-info">{{ number_format($metrics['reincorporaciones']['active']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Con seguimiento</div>
                                <div class="fs-4 fw-bold">{{ number_format($metrics['reincorporaciones']['with_followup']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Vencidos</div>
                                <div class="fs-4 fw-bold text-danger">{{ number_format($metrics['reincorporaciones']['overdue']) }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Vencen en 30 días</div>
                                <div class="fs-4 fw-bold text-warning">{{ number_format($metrics['reincorporaciones']['due_30']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-section-header">
                    <h5 class="dashboard-section-title">IPT - Seguimiento</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Total inspecciones</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format($metrics['ipt']['total']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Con seguimiento</div>
                                <div class="fs-4 fw-bold">{{ number_format($metrics['ipt']['with_followup']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Vencidos</div>
                                <div class="fs-4 fw-bold text-danger">{{ number_format($metrics['ipt']['overdue']) }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted dashboard-metric-label">Vencen en 30 días</div>
                                <div class="fs-4 fw-bold text-warning">{{ number_format($metrics['ipt']['due_30']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header dashboard-section-header">
                    <h5 class="dashboard-section-title">Pausas Activas - Cobertura y Participación</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Pausas activas creadas</div>
                                <div class="fs-3 fw-bold text-primary">{{ number_format($metrics['pausas']['active_created']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Personas enviadas</div>
                                <div class="fs-3 fw-bold">{{ number_format($metrics['pausas']['sent_people']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Participaciones completadas</div>
                                <div class="fs-3 fw-bold text-success">{{ number_format($metrics['pausas']['participated']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Tasa de participación</div>
                                <div class="fs-3 fw-bold text-info">{{ number_format($metrics['pausas']['participation_rate'], 1) }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header dashboard-section-header">
                    <h5 class="dashboard-section-title">Encuestas - Cobertura y Participación</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Encuestas activas creadas</div>
                                <div class="fs-3 fw-bold text-primary">{{ number_format($metrics['encuestas']['active_created']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Personas enviadas</div>
                                <div class="fs-3 fw-bold">{{ number_format($metrics['encuestas']['sent_people']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Participaciones completadas</div>
                                <div class="fs-3 fw-bold text-success">{{ number_format($metrics['encuestas']['participated']) }}</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted dashboard-metric-label">Tasa de participación</div>
                                <div class="fs-3 fw-bold text-info">{{ number_format($metrics['encuestas']['participation_rate'], 1) }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
