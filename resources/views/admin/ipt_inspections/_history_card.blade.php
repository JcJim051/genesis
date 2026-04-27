<div class="{{ $cardClass ?? 'card p-4 mt-4' }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Historial IPT</h5>
        @if(!empty($createInitialUrl))
            <a class="btn btn-sm btn-outline-primary" href="{{ $createInitialUrl }}">
                <i class="la la-plus"></i> Crear inspección inicial
            </a>
        @endif
    </div>

    @if($iptInspections->isEmpty())
        <div class="text-muted">No hay inspecciones IPT registradas.</div>
    @else
        <div class="table-responsive ipt-history-wrap">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    @if(!empty($showPersonaColumn))
                        <th>Persona</th>
                    @endif
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Puntaje</th>
                    <th>Riesgo</th>
                    <th>Próximo sugerido</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach($iptInspections as $ipt)
                    <tr>
                        <td>{{ $ipt->id }}</td>
                        @if(!empty($showPersonaColumn))
                            <td>{!! \App\Support\EmpleadoLink::render($ipt->empleado, trim(($ipt->empleado?->nombre ?? '') . ' · ' . ($ipt->empleado?->cedula ?? ''))) !!}</td>
                        @endif
                        <td>{{ $ipt->tipo === 'followup' ? 'Seguimiento' : 'Inicial' }}</td>
                        <td>{{ optional($ipt->fecha_inspeccion)->format('Y-m-d') }}</td>
                        <td>{{ $ipt->puntaje_total }}</td>
                        <td>{{ strtoupper((string) $ipt->nivel_riesgo) }}</td>
                        <td>{{ optional($ipt->fecha_proximo_seguimiento_sugerida)->format('Y-m-d') ?: '—' }}</td>
                        <td>{{ ucfirst((string) $ipt->estado) }}</td>
                        <td class="ipt-actions">
                            <a class="ipt-link" href="{{ backpack_url('ipt-inspection/' . $ipt->id . '/show') }}">Ver</a>
                            <a class="ipt-link" href="{{ backpack_url('ipt/' . $ipt->id . '/edit') }}">Editar</a>
                            @if($ipt->tipo === 'initial')
                                <a class="ipt-link" href="{{ backpack_url('ipt/' . $ipt->id . '/create-followup') }}">Seguimiento</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <style>
            .ipt-history-wrap .table { width: 100%; table-layout: fixed; }
            .ipt-history-wrap th,
            .ipt-history-wrap td {
                white-space: nowrap;
                font-size: .82rem;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
                padding: .35rem .45rem;
            }
            .ipt-history-wrap .ipt-actions { display: flex; gap: .45rem; align-items: center; }
            .ipt-history-wrap .ipt-link { font-size: .78rem; font-weight: 600; text-decoration: none; }
            .ipt-history-wrap th:nth-child(1), .ipt-history-wrap td:nth-child(1) { width: 6%; }
            .ipt-history-wrap th:nth-child(2), .ipt-history-wrap td:nth-child(2) { width: 11%; }
            .ipt-history-wrap th:nth-child(3), .ipt-history-wrap td:nth-child(3) { width: 11%; }
            .ipt-history-wrap th:nth-child(4), .ipt-history-wrap td:nth-child(4) { width: 10%; text-align: right; }
            .ipt-history-wrap th:nth-child(5), .ipt-history-wrap td:nth-child(5) { width: 10%; }
            .ipt-history-wrap th:nth-child(6), .ipt-history-wrap td:nth-child(6) { width: 17%; }
            .ipt-history-wrap th:nth-child(7), .ipt-history-wrap td:nth-child(7) { width: 10%; }
            .ipt-history-wrap th:nth-child(8), .ipt-history-wrap td:nth-child(8) { width: 25%; }
            @media (max-width: 1650px) {
                .ipt-history-wrap th:nth-child(6), .ipt-history-wrap td:nth-child(6) { display: none; }
                .ipt-history-wrap th:nth-child(1), .ipt-history-wrap td:nth-child(1) { width: 8%; }
                .ipt-history-wrap th:nth-child(2), .ipt-history-wrap td:nth-child(2) { width: 14%; }
                .ipt-history-wrap th:nth-child(3), .ipt-history-wrap td:nth-child(3) { width: 14%; }
                .ipt-history-wrap th:nth-child(4), .ipt-history-wrap td:nth-child(4) { width: 12%; }
                .ipt-history-wrap th:nth-child(5), .ipt-history-wrap td:nth-child(5) { width: 12%; }
                .ipt-history-wrap th:nth-child(7), .ipt-history-wrap td:nth-child(7) { width: 14%; }
                .ipt-history-wrap th:nth-child(8), .ipt-history-wrap td:nth-child(8) { width: 26%; }
            }
            @media (max-width: 1350px) {
                .ipt-history-wrap th:nth-child(7), .ipt-history-wrap td:nth-child(7) { display: none; }
                .ipt-history-wrap th:nth-child(1), .ipt-history-wrap td:nth-child(1) { width: 8%; }
                .ipt-history-wrap th:nth-child(2), .ipt-history-wrap td:nth-child(2) { width: 18%; }
                .ipt-history-wrap th:nth-child(3), .ipt-history-wrap td:nth-child(3) { width: 18%; }
                .ipt-history-wrap th:nth-child(4), .ipt-history-wrap td:nth-child(4) { width: 14%; }
                .ipt-history-wrap th:nth-child(5), .ipt-history-wrap td:nth-child(5) { width: 14%; }
                .ipt-history-wrap th:nth-child(8), .ipt-history-wrap td:nth-child(8) { width: 28%; }
            }
            @media (max-width: 1180px) {
                .ipt-history-wrap th:nth-child(1), .ipt-history-wrap td:nth-child(1) { display: none; }
                .ipt-history-wrap th:nth-child(2), .ipt-history-wrap td:nth-child(2) { width: 20%; }
                .ipt-history-wrap th:nth-child(3), .ipt-history-wrap td:nth-child(3) { width: 20%; }
                .ipt-history-wrap th:nth-child(4), .ipt-history-wrap td:nth-child(4) { width: 16%; }
                .ipt-history-wrap th:nth-child(5), .ipt-history-wrap td:nth-child(5) { width: 16%; }
                .ipt-history-wrap th:nth-child(8), .ipt-history-wrap td:nth-child(8) { width: 28%; }
            }
        </style>
    @endif
</div>
