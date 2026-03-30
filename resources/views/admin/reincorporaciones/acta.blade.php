@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('backpack::crud.preview') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    $acta = is_array($entry->acta_payload ?? null) ? $entry->acta_payload : [];
    $empleado = $entry->empleado;
    $val = function(string $key, $fallback = '') use ($acta) {
        $value = $acta[$key] ?? null;
        return ($value === null || $value === '') ? $fallback : $value;
    };
    $fmtDate = function($value) {
        if ($value instanceof \Carbon\CarbonInterface) return $value->format('Y-m-d');
        if (is_string($value)) return $value;
        return '';
    };
@endphp

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">Acta de Reincorporación</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">Vista previa</p>
            @if ($crud->hasAccess('list'))
                <p class="ms-2 ml-2 mb-0" bp-section="page-subheading-back-button">
                    <small><a href="{{ url($crud->route) }}" class="font-sm"><i class="la la-angle-double-left"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
                </p>
            @endif
        </section>
        <div class="d-flex gap-2">
            <a href="{{ backpack_url('reincorporacion/' . $entry->id . '/acta-pdf') }}" class="btn btn-sm btn-primary"><i class="la la-file-pdf"></i> Descargar Acta</a>
            <a href="javascript: window.print();" class="btn btn-sm btn-outline-secondary"><i class="la la-print"></i> Imprimir</a>
        </div>
    </div>
@endsection

@section('content')
<div class="row" bp-section="crud-operation-show">
    <div class="col-12">
        <div class="card p-4">
            <style>
                .acta-title { text-align: center; font-weight: 700; font-size: 18px; margin-bottom: 4px; }
                .acta-subtitle { text-align: center; font-size: 12px; margin-bottom: 16px; }
                .acta-table { width: 100%; border-collapse: collapse; }
                .acta-table td, .acta-table th { border: 1px solid #444; padding: 6px; vertical-align: top; }
                .acta-section { font-weight: 700; background: #f5f5f5; }
                .acta-muted { color: #666; }
                .acta-checkbox { display: inline-block; width: 12px; height: 12px; border: 1px solid #111; text-align: center; line-height: 10px; font-size: 10px; }
                .mt-8 { margin-top: 8px; }
                .mt-12 { margin-top: 12px; }
            </style>

            <div class="acta-title">ACTA DE REINCORPORACIÓN LABORAL</div>
            <div class="acta-subtitle">GESTIÓN DE TALENTO HUMANO Y SEGURIDAD Y SALUD EN EL TRABAJO</div>

            <table class="acta-table">
                <tr><td><strong>Fecha</strong></td><td colspan="3">{{ $val('fecha_acta', $fmtDate($entry->fecha_ingreso)) }}</td></tr>
                <tr><td><strong>Nombre Completo</strong></td><td colspan="3">{{ $val('nombre_completo', $empleado?->nombre ?? '') }}</td></tr>
                <tr><td><strong>Identificación</strong></td><td colspan="3">{{ $val('identificacion', $empleado?->cedula ?? '') }}</td></tr>
                <tr>
                    <td><strong>Fecha Nacimiento</strong></td>
                    <td>{{ $val('fecha_nacimiento', $fmtDate($empleado?->fecha_nacimiento)) }}</td>
                    <td><strong>Edad</strong></td>
                    <td>{{ $val('edad', $empleado?->edad ?? '') }}</td>
                </tr>
                <tr>
                    <td><strong>Género</strong></td>
                    <td>{{ $val('genero', $empleado?->genero ?? '') }}</td>
                    <td><strong>Lateralidad</strong></td>
                    <td>{{ $val('lateralidad', $empleado?->lateralidad ?? '') }}</td>
                </tr>
                <tr>
                    <td><strong>EPS</strong></td>
                    <td>{{ $val('eps', $empleado?->eps ?? '') }}</td>
                    <td><strong>ARL</strong></td>
                    <td>{{ $val('arl', $empleado?->arl ?? '') }}</td>
                </tr>
                <tr><td><strong>Fondo de Pensiones</strong></td><td colspan="3">{{ $val('fondo_pensiones', $empleado?->fondo_pensiones ?? '') }}</td></tr>
                <tr>
                    <td><strong>Teléfono Contacto</strong></td>
                    <td>{{ $val('telefono_contacto', $empleado?->telefono ?? '') }}</td>
                    <td><strong>Correo electrónico</strong></td>
                    <td>{{ $val('correo_electronico', $empleado?->correo_electronico ?? '') }}</td>
                </tr>
                <tr><td><strong>Dirección Residencia</strong></td><td colspan="3">{{ $val('direccion_residencia', $empleado?->direccion ?? '') }}</td></tr>
                <tr><td><strong>Fecha ingreso a la empresa</strong></td><td colspan="3">{{ $val('fecha_ingreso_empresa', $fmtDate($empleado?->fecha_ingreso)) }}</td></tr>
                <tr><td><strong>Cargo actual que desempeña</strong></td><td colspan="3">{{ $val('cargo_actual', $empleado?->getCargoActual() ?? '') }}</td></tr>
                <tr><td><strong>Antigüedad en el cargo</strong></td><td colspan="3">{{ $val('antiguedad_cargo', $empleado?->getAntiguedadCargoActual() ?? '') }}</td></tr>
            </table>

            <table class="acta-table mt-8">
                <tr>
                    <td><strong>Origen del evento</strong></td>
                    <td><span class="acta-checkbox">{{ $val('origen_evento') === 'Común' ? 'X' : '' }}</span> Común</td>
                    <td><span class="acta-checkbox">{{ $val('origen_evento') === 'Accidente de Trabajo' ? 'X' : '' }}</span> Accidente de Trabajo</td>
                    <td><span class="acta-checkbox">{{ $val('origen_evento') === 'Enfermedad Laboral' ? 'X' : '' }}</span> Enfermedad Laboral</td>
                </tr>
                <tr>
                    <td><strong>Vigencia recomendaciones</strong></td>
                    <td colspan="3">Fecha inicial: {{ $val('vigencia_fecha_inicial') }} &nbsp;&nbsp; Fecha final: {{ $val('vigencia_fecha_final') }}</td>
                </tr>
            </table>

            <table class="acta-table mt-8">
                <tr><td class="acta-section">RECOMENDACIONES MÉDICAS</td></tr>
                <tr><td style="height: 80px;">{!! nl2br(e($val('recomendaciones_medicas'))) !!}</td></tr>
            </table>

            <table class="acta-table mt-8">
                <tr><td class="acta-section">ACTIVIDADES LABORALES ASIGNADAS TENIENDO EN CUENTA RECOMENDACIONES MÉDICAS</td></tr>
                <tr><td style="height: 80px;">{!! nl2br(e($val('actividades_asignadas'))) !!}</td></tr>
            </table>

            <table class="acta-table mt-8">
                <tr><td class="acta-section">DECISIÓN ADOPTADA</td></tr>
                <tr><td><span class="acta-checkbox">{{ $val('decision_adoptada') === 'Reincorporación sin modificaciones' ? 'X' : '' }}</span> Reincorporación sin modificaciones, cargo habitual sin ningún cambio</td></tr>
                <tr><td><span class="acta-checkbox">{{ $val('decision_adoptada') === 'Reincorporación con modificaciones' ? 'X' : '' }}</span> Reincorporación con modificaciones dentro su cargo habitual</td></tr>
                <tr><td><span class="acta-checkbox">{{ $val('decision_adoptada') === 'Reubicación temporal' ? 'X' : '' }}</span> Reubicación laboral temporal</td></tr>
                <tr><td><span class="acta-checkbox">{{ $val('decision_adoptada') === 'Reubicación permanente' ? 'X' : '' }}</span> Reubicación laboral permanente</td></tr>
                <tr><td><span class="acta-checkbox">{{ $val('decision_adoptada') === 'Reconversión mano de obra' ? 'X' : '' }}</span> Reconversión mano de obra</td></tr>
            </table>

            <table class="acta-table mt-8">
                <tr>
                    <td><strong>¿Se ajustaron las condiciones del puesto de trabajo?</strong></td>
                    <td><span class="acta-checkbox">{{ $val('ajuste_puesto') === 'SI' ? 'X' : '' }}</span> SI</td>
                    <td><span class="acta-checkbox">{{ $val('ajuste_puesto') === 'NO' ? 'X' : '' }}</span> NO</td>
                </tr>
                <tr>
                    <td colspan="3"><strong>Si marcó SI, describa los cambios:</strong><br>{!! nl2br(e($val('ajuste_descripcion'))) !!}</td>
                </tr>
                <tr>
                    <td><strong>¿Se cambió el cargo?</strong></td>
                    <td><span class="acta-checkbox">{{ $val('cambio_cargo') === 'SI' ? 'X' : '' }}</span> SI</td>
                    <td><span class="acta-checkbox">{{ $val('cambio_cargo') === 'NO' ? 'X' : '' }}</span> NO</td>
                </tr>
                <tr>
                    <td colspan="3"><strong>Nuevo cargo asignado (si aplica):</strong> {{ $val('nuevo_cargo') }}</td>
                </tr>
                <tr>
                    <td colspan="3"><strong>Área donde se reintegra:</strong> {{ $val('area_reintegra', $empleado?->getAreaActual() ?? '') }}</td>
                </tr>
            </table>

            <table class="acta-table mt-8">
                <tr><td class="acta-section">OBSERVACIONES</td></tr>
                <tr><td style="height: 70px;">{!! nl2br(e($val('observaciones'))) !!}</td></tr>
                <tr><td><strong>Fecha del próximo seguimiento:</strong> {{ $val('fecha_proximo_seguimiento') }}</td></tr>
            </table>

            <table class="acta-table mt-8">
                <tr><td class="acta-section" colspan="4">ASISTENTES</td></tr>
                <tr><th>Nombre completo</th><th>Cédula</th><th>Cargo</th><th>Firma</th></tr>
                <tr>
                    <td>Jefe inmediato: {{ $val('asistente_jefe_nombre') }}</td>
                    <td>{{ $val('asistente_jefe_cedula') }}</td>
                    <td>{{ $val('asistente_jefe_cargo') }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Responsable SST: {{ $val('asistente_sst_nombre') }}</td>
                    <td>{{ $val('asistente_sst_cedula') }}</td>
                    <td>{{ $val('asistente_sst_cargo') }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Trabajador: {{ $val('asistente_trabajador_nombre', $empleado?->nombre ?? '') }}</td>
                    <td>{{ $val('asistente_trabajador_cedula', $empleado?->cedula ?? '') }}</td>
                    <td>{{ $val('asistente_trabajador_cargo', $empleado?->getCargoActual() ?? '') }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Otro: {{ $val('asistente_otro_nombre') }}</td>
                    <td>{{ $val('asistente_otro_cedula') }}</td>
                    <td>{{ $val('asistente_otro_cargo') }}</td>
                    <td></td>
                </tr>
            </table>

            <p class="mt-12 acta-muted">
                El proceso de reincorporación laboral se hace bajo lo establecido en la normatividad vigente Ley 776 de 2002,
                Decreto 1072 de 2015 y Resolución 3050 de 2022. El trabajador se compromete a dar cumplimiento a las recomendaciones
                tanto en el ámbito extralaboral, como en el intralaboral.
            </p>
        </div>
    </div>
</div>
@endsection
