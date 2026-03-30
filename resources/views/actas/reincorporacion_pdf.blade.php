<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .title { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 4px; }
        .subtitle { text-align: center; font-size: 11px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #444; padding: 4px; vertical-align: top; }
        .no-border td { border: none; }
        .section-title { font-weight: bold; background: #f2f2f2; }
        .muted { color: #666; }
        .checkbox { display: inline-block; width: 12px; height: 12px; border: 1px solid #111; text-align: center; line-height: 10px; font-size: 10px; }
        .mt-8 { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }
        .mb-6 { margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="title">ACTA DE REINCORPORACIÓN LABORAL</div>
    <div class="subtitle">GESTIÓN DE TALENTO HUMANO Y SEGURIDAD Y SALUD EN EL TRABAJO</div>

    <table>
        <tr>
            <td><strong>Fecha</strong></td>
            <td colspan="3">{{ data_get($acta, 'fecha_acta') }}</td>
        </tr>
        <tr>
            <td><strong>Nombre Completo</strong></td>
            <td colspan="3">{{ data_get($acta, 'nombre_completo') }}</td>
        </tr>
        <tr>
            <td><strong>Identificación</strong></td>
            <td colspan="3">{{ data_get($acta, 'identificacion') }}</td>
        </tr>
        <tr>
            <td><strong>Fecha Nacimiento</strong></td>
            <td>{{ data_get($acta, 'fecha_nacimiento') }}</td>
            <td><strong>Edad</strong></td>
            <td>{{ data_get($acta, 'edad') }}</td>
        </tr>
        <tr>
            <td><strong>Género</strong></td>
            <td>{{ data_get($acta, 'genero') }}</td>
            <td><strong>Lateralidad</strong></td>
            <td>{{ data_get($acta, 'lateralidad') }}</td>
        </tr>
        <tr>
            <td><strong>EPS</strong></td>
            <td>{{ data_get($acta, 'eps') }}</td>
            <td><strong>ARL</strong></td>
            <td>{{ data_get($acta, 'arl') }}</td>
        </tr>
        <tr>
            <td><strong>Fondo de Pensiones</strong></td>
            <td colspan="3">{{ data_get($acta, 'fondo_pensiones') }}</td>
        </tr>
        <tr>
            <td><strong>Teléfono Contacto</strong></td>
            <td>{{ data_get($acta, 'telefono_contacto') }}</td>
            <td><strong>Correo electrónico</strong></td>
            <td>{{ data_get($acta, 'correo_electronico') }}</td>
        </tr>
        <tr>
            <td><strong>Dirección Residencia</strong></td>
            <td colspan="3">{{ data_get($acta, 'direccion_residencia') }}</td>
        </tr>
        <tr>
            <td><strong>Fecha ingreso a la empresa</strong></td>
            <td colspan="3">{{ data_get($acta, 'fecha_ingreso_empresa') }}</td>
        </tr>
        <tr>
            <td><strong>Cargo actual que desempeña</strong></td>
            <td colspan="3">{{ data_get($acta, 'cargo_actual') }}</td>
        </tr>
        <tr>
            <td><strong>Antigüedad en el cargo</strong></td>
            <td colspan="3">{{ data_get($acta, 'antiguedad_cargo') }}</td>
        </tr>
    </table>

    <table class="mt-8">
        <tr>
            <td><strong>Origen del evento</strong></td>
            <td>
                <span class="checkbox">{{ data_get($acta, 'origen_evento') === 'Común' ? 'X' : '' }}</span> Común
            </td>
            <td>
                <span class="checkbox">{{ data_get($acta, 'origen_evento') === 'Accidente de Trabajo' ? 'X' : '' }}</span> Accidente de Trabajo
            </td>
            <td>
                <span class="checkbox">{{ data_get($acta, 'origen_evento') === 'Enfermedad Laboral' ? 'X' : '' }}</span> Enfermedad Laboral
            </td>
        </tr>
        <tr>
            <td><strong>Vigencia recomendaciones</strong></td>
            <td colspan="3">Fecha inicial: {{ data_get($acta, 'vigencia_fecha_inicial') }} &nbsp;&nbsp; Fecha final: {{ data_get($acta, 'vigencia_fecha_final') }}</td>
        </tr>
    </table>

    <table class="mt-8">
        <tr>
            <td class="section-title">RECOMENDACIONES MÉDICAS</td>
        </tr>
        <tr>
            <td style="height: 80px;">{!! nl2br(e(data_get($acta, 'recomendaciones_medicas'))) !!}</td>
        </tr>
    </table>

    <table class="mt-8">
        <tr>
            <td class="section-title">ACTIVIDADES LABORALES ASIGNADAS TENIENDO EN CUENTA RECOMENDACIONES MÉDICAS</td>
        </tr>
        <tr>
            <td style="height: 80px;">{!! nl2br(e(data_get($acta, 'actividades_asignadas'))) !!}</td>
        </tr>
    </table>

    <table class="mt-8">
        <tr>
            <td class="section-title">DECISIÓN ADOPTADA</td>
        </tr>
        <tr><td><span class="checkbox">{{ data_get($acta, 'decision_adoptada') === 'Reincorporación sin modificaciones' ? 'X' : '' }}</span> Reincorporación sin modificaciones, cargo habitual sin ningún cambio</td></tr>
        <tr><td><span class="checkbox">{{ data_get($acta, 'decision_adoptada') === 'Reincorporación con modificaciones' ? 'X' : '' }}</span> Reincorporación con modificaciones dentro su cargo habitual</td></tr>
        <tr><td><span class="checkbox">{{ data_get($acta, 'decision_adoptada') === 'Reubicación temporal' ? 'X' : '' }}</span> Reubicación laboral temporal</td></tr>
        <tr><td><span class="checkbox">{{ data_get($acta, 'decision_adoptada') === 'Reubicación permanente' ? 'X' : '' }}</span> Reubicación laboral permanente</td></tr>
        <tr><td><span class="checkbox">{{ data_get($acta, 'decision_adoptada') === 'Reconversión mano de obra' ? 'X' : '' }}</span> Reconversión mano de obra</td></tr>
    </table>

    <table class="mt-8">
        <tr>
            <td><strong>¿Se ajustaron las condiciones del puesto de trabajo?</strong></td>
            <td><span class="checkbox">{{ data_get($acta, 'ajuste_puesto') === 'SI' ? 'X' : '' }}</span> SI</td>
            <td><span class="checkbox">{{ data_get($acta, 'ajuste_puesto') === 'NO' ? 'X' : '' }}</span> NO</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Si marcó SI, describa los cambios:</strong><br>{!! nl2br(e(data_get($acta, 'ajuste_descripcion'))) !!}</td>
        </tr>
        <tr>
            <td><strong>¿Se cambió el cargo?</strong></td>
            <td><span class="checkbox">{{ data_get($acta, 'cambio_cargo') === 'SI' ? 'X' : '' }}</span> SI</td>
            <td><span class="checkbox">{{ data_get($acta, 'cambio_cargo') === 'NO' ? 'X' : '' }}</span> NO</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Nuevo cargo asignado (si aplica):</strong> {{ data_get($acta, 'nuevo_cargo') }}</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Área donde se reintegra:</strong> {{ data_get($acta, 'area_reintegra') }}</td>
        </tr>
    </table>

    <table class="mt-8">
        <tr>
            <td class="section-title">OBSERVACIONES</td>
        </tr>
        <tr>
            <td style="height: 70px;">{!! nl2br(e(data_get($acta, 'observaciones'))) !!}</td>
        </tr>
        <tr>
            <td><strong>Fecha del próximo seguimiento:</strong> {{ data_get($acta, 'fecha_proximo_seguimiento') }}</td>
        </tr>
    </table>

    <table class="mt-8">
        <tr>
            <td class="section-title" colspan="4">ASISTENTES</td>
        </tr>
        <tr>
            <th>Nombre completo</th>
            <th>Cédula</th>
            <th>Cargo</th>
            <th>Firma</th>
        </tr>
        <tr>
            <td>Jefe inmediato: {{ data_get($acta, 'asistente_jefe_nombre') }}</td>
            <td>{{ data_get($acta, 'asistente_jefe_cedula') }}</td>
            <td>{{ data_get($acta, 'asistente_jefe_cargo') }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Responsable SST: {{ data_get($acta, 'asistente_sst_nombre') }}</td>
            <td>{{ data_get($acta, 'asistente_sst_cedula') }}</td>
            <td>{{ data_get($acta, 'asistente_sst_cargo') }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Trabajador: {{ data_get($acta, 'asistente_trabajador_nombre') }}</td>
            <td>{{ data_get($acta, 'asistente_trabajador_cedula') }}</td>
            <td>{{ data_get($acta, 'asistente_trabajador_cargo') }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Otro: {{ data_get($acta, 'asistente_otro_nombre') }}</td>
            <td>{{ data_get($acta, 'asistente_otro_cedula') }}</td>
            <td>{{ data_get($acta, 'asistente_otro_cargo') }}</td>
            <td></td>
        </tr>
    </table>

    <p class="mt-12 muted">
        El proceso de reincorporación laboral se hace bajo lo establecido en la normatividad vigente Ley 776 de 2002,
        Decreto 1072 de 2015 y Resolución 3050 de 2022. El trabajador se compromete a dar cumplimiento a las recomendaciones
        tanto en el ámbito extralaboral, como en el intralaboral.
    </p>
</body>
</html>
