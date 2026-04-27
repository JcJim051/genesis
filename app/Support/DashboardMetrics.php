<?php

namespace App\Support;

use App\Models\ActaSeguimiento;
use App\Models\Empleado;
use App\Models\Encuesta;
use App\Models\EncuestaRespuesta;
use App\Models\IptInspection;
use App\Models\Pausa;
use App\Models\PausaParticipacion;
use App\Models\ProgramaCaso;
use App\Models\Reincorporacion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardMetrics
{
    public function build(): array
    {
        $today = Carbon::today();
        $next30 = $today->copy()->addDays(30);

        $activeEmployees = Empleado::query()
            ->when(true, function (Builder $query) {
                $this->applyEmployeeScope($query);
            })
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('fecha_retiro')
                    ->orWhereDate('fecha_retiro', '>', $today->toDateString());
            })
            ->count();

        $programRows = ProgramaCaso::query()
            ->join('programas', 'programas.id', '=', 'programa_casos.programa_id')
            ->join('empleados', 'empleados.id', '=', 'programa_casos.empleado_id')
            ->when(true, function (Builder $query) {
                $this->applyEmployeeJoinedScope($query, 'empleados');
            })
            ->selectRaw('programa_casos.programa_id')
            ->selectRaw('programas.nombre as programa_nombre')
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(programa_casos.estado)) = 'no evaluado' THEN 1 ELSE 0 END) as no_evaluado")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(programa_casos.estado)) = 'confirmado' THEN 1 ELSE 0 END) as confirmado")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(programa_casos.estado)) = 'probable' THEN 1 ELSE 0 END) as probable")
            ->groupBy('programa_casos.programa_id', 'programas.nombre')
            ->orderByRaw("CASE WHEN LOWER(TRIM(programas.nombre)) = 'osteomuscular' THEN 0 ELSE 1 END")
            ->orderBy('programas.nombre')
            ->get();

        $programs = $programRows->map(function ($row) {
            return [
                'programa_id' => (int) $row->programa_id,
                'programa_nombre' => (string) $row->programa_nombre,
                'no_evaluado' => (int) $row->no_evaluado,
                'confirmado' => (int) $row->confirmado,
                'probable' => (int) $row->probable,
            ];
        })->values()->all();

        $totalNoEvaluado = (int) $programRows->sum('no_evaluado');
        $totalConfirmado = (int) $programRows->sum('confirmado');

        $reincRows = Reincorporacion::query()
            ->join('empleados', 'empleados.id', '=', 'reincorporaciones.empleado_id')
            ->when(true, function (Builder $query) {
                $this->applyEmployeeJoinedScope($query, 'empleados');
            })
            ->select('reincorporaciones.id', 'reincorporaciones.estado', 'reincorporaciones.acta_payload')
            ->get();

        $activeReinc = $reincRows->filter(function ($row) {
            return $this->isActiveReincorporacion((string) ($row->estado ?? ''));
        })->values();

        $activeReincIds = $activeReinc->pluck('id')->all();
        $reincWithFollowup = empty($activeReincIds)
            ? 0
            : ActaSeguimiento::query()->whereIn('reincorporacion_id', $activeReincIds)->distinct('reincorporacion_id')->count('reincorporacion_id');

        $reincDue = $this->classifyDueDates(
            $activeReinc,
            fn ($row) => $this->extractReincorporacionNextFollowup($row->acta_payload),
            $today,
            $next30
        );

        $iptBase = IptInspection::query()->when(true, function (Builder $query) {
            $this->applyFieldScope($query, 'cliente_id', 'sucursal_id');
        });

        $iptTotal = (clone $iptBase)->count();
        $iptWithFollowups = (clone $iptBase)->whereNotNull('initial_inspection_id')->count();
        $iptDueOverdue = (clone $iptBase)
            ->whereNotNull('fecha_proximo_seguimiento_sugerida')
            ->whereDate('fecha_proximo_seguimiento_sugerida', '<', $today->toDateString())
            ->count();
        $iptDueSoon = (clone $iptBase)
            ->whereNotNull('fecha_proximo_seguimiento_sugerida')
            ->whereBetween('fecha_proximo_seguimiento_sugerida', [$today->toDateString(), $next30->toDateString()])
            ->count();

        $activePausesQuery = Pausa::query()
            ->leftJoin('pausa_envios', 'pausa_envios.pausa_id', '=', 'pausas.id')
            ->where('pausas.activa', true);

        if (! TenantSelection::isAdminBypass()) {
            $activePausesQuery->where(function (Builder $scopeQuery) {
                $this->applyFieldJoinedScope($scopeQuery, 'pausas', 'cliente_id', 'sucursal_id');
                $scopeQuery->orWhere(function (Builder $envioScope) {
                    $this->applyFieldJoinedScope($envioScope, 'pausa_envios', 'cliente_id', 'sucursal_id');
                });
            });
        }

        $activePausesCreated = (clone $activePausesQuery)
            ->distinct()
            ->count('pausas.id');

        $pauseParticipantsBase = PausaParticipacion::query()
            ->join('pausa_envios', 'pausa_envios.id', '=', 'pausa_participaciones.envio_id')
            ->join('pausas', 'pausas.id', '=', 'pausa_envios.pausa_id')
            ->where('pausas.activa', true)
            ->when(true, function (Builder $query) {
                $this->applyFieldJoinedScope($query, 'pausa_envios', 'cliente_id', 'sucursal_id');
            });

        $pauseSentPeople = (clone $pauseParticipantsBase)->count('pausa_participaciones.id');
        $pauseParticipated = (clone $pauseParticipantsBase)
            ->where('pausa_participaciones.estado', 'completada')
            ->count('pausa_participaciones.id');

        $activeSurveysQuery = Encuesta::query()
            ->leftJoin('encuesta_envios', 'encuesta_envios.encuesta_id', '=', 'encuestas.id')
            ->where('encuestas.activa', true);

        if (! TenantSelection::isAdminBypass()) {
            $activeSurveysQuery->where(function (Builder $scopeQuery) {
                $this->applyFieldJoinedScope($scopeQuery, 'encuestas', 'cliente_id', 'sucursal_id');
                $scopeQuery->orWhere(function (Builder $envioScope) {
                    $this->applyFieldJoinedScope($envioScope, 'encuesta_envios', 'cliente_id', 'sucursal_id');
                });
            });
        }

        $activeSurveysCreated = (clone $activeSurveysQuery)
            ->distinct()
            ->count('encuestas.id');

        $surveyResponsesBase = EncuestaRespuesta::query()
            ->join('encuesta_envios', 'encuesta_envios.id', '=', 'encuesta_respuestas.envio_id')
            ->join('encuestas', 'encuestas.id', '=', 'encuesta_envios.encuesta_id')
            ->where('encuestas.activa', true)
            ->when(true, function (Builder $query) {
                $this->applyFieldJoinedScope($query, 'encuesta_envios', 'cliente_id', 'sucursal_id');
            });

        $surveySentPeople = (clone $surveyResponsesBase)->count('encuesta_respuestas.id');
        $surveyParticipated = (clone $surveyResponsesBase)
            ->where('encuesta_respuestas.estado', 'completada')
            ->count('encuesta_respuestas.id');

        return [
            'scope_label' => TenantSelection::humanLabel(),
            'active_employees' => $activeEmployees,
            'cases_confirmado_total' => $totalConfirmado,
            'cases_no_evaluado' => $totalNoEvaluado,
            'programs' => $programs,
            'reincorporaciones' => [
                'active' => $activeReinc->count(),
                'with_followup' => (int) $reincWithFollowup,
                'overdue' => $reincDue['overdue'],
                'due_30' => $reincDue['due_30'],
            ],
            'ipt' => [
                'total' => (int) $iptTotal,
                'with_followup' => (int) $iptWithFollowups,
                'overdue' => (int) $iptDueOverdue,
                'due_30' => (int) $iptDueSoon,
            ],
            'pausas' => [
                'active_created' => (int) $activePausesCreated,
                'sent_people' => (int) $pauseSentPeople,
                'participated' => (int) $pauseParticipated,
                'participation_rate' => $pauseSentPeople > 0
                    ? round(($pauseParticipated / $pauseSentPeople) * 100, 1)
                    : 0.0,
            ],
            'encuestas' => [
                'active_created' => (int) $activeSurveysCreated,
                'sent_people' => (int) $surveySentPeople,
                'participated' => (int) $surveyParticipated,
                'participation_rate' => $surveySentPeople > 0
                    ? round(($surveyParticipated / $surveySentPeople) * 100, 1)
                    : 0.0,
            ],
        ];
    }

    private function classifyDueDates(Collection $rows, callable $dateResolver, Carbon $today, Carbon $next30): array
    {
        $overdue = 0;
        $due30 = 0;

        foreach ($rows as $row) {
            $date = $dateResolver($row);
            if (! $date) {
                continue;
            }

            if ($date->lt($today)) {
                $overdue++;
                continue;
            }

            if ($date->betweenIncluded($today, $next30)) {
                $due30++;
            }
        }

        return [
            'overdue' => $overdue,
            'due_30' => $due30,
        ];
    }

    private function extractReincorporacionNextFollowup($payload): ?Carbon
    {
        if (is_array($payload)) {
            $value = $payload['fecha_proximo_seguimiento'] ?? null;
        } elseif (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            $value = is_array($decoded) ? ($decoded['fecha_proximo_seguimiento'] ?? null) : null;
        } else {
            $value = null;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isActiveReincorporacion(string $estado): bool
    {
        $estado = strtolower(trim($estado));
        if ($estado === '') {
            return true;
        }

        if (str_contains($estado, 'cerr') || str_contains($estado, 'final') || str_contains($estado, 'retir')) {
            return false;
        }

        return true;
    }

    private function applyFieldScope(Builder $query, string $empresaField, string $plantaField): void
    {
        if (TenantSelection::isAdminBypass()) {
            return;
        }

        $empresaIds = TenantSelection::empresaIds();
        $plantaIds = TenantSelection::plantaIds();
        $includeUnassigned = TenantSelection::selectedEmpresaIncludesUnassigned();

        if (! empty($plantaIds)) {
            $query->whereIn($plantaField, $plantaIds);
            return;
        }

        if (! empty($empresaIds)) {
            if ($includeUnassigned) {
                $query->where(function (Builder $inner) use ($empresaField, $empresaIds) {
                    $inner->whereIn($empresaField, $empresaIds)
                        ->orWhereNull($empresaField)
                        ->orWhere($empresaField, 0);
                });
                return;
            }

            $query->whereIn($empresaField, $empresaIds);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function applyEmployeeScope(Builder $query): void
    {
        $this->applyFieldScope($query, 'cliente_id', 'sucursal_id');
    }

    private function applyEmployeeJoinedScope(Builder $query, string $employeeTableAlias): void
    {
        $this->applyFieldJoinedScope($query, $employeeTableAlias, 'cliente_id', 'sucursal_id');
    }

    private function applyFieldJoinedScope(
        Builder $query,
        string $tableAlias,
        string $empresaColumn = 'cliente_id',
        string $plantaColumn = 'sucursal_id'
    ): void {
        if (TenantSelection::isAdminBypass()) {
            return;
        }

        $empresaIds = TenantSelection::empresaIds();
        $plantaIds = TenantSelection::plantaIds();
        $includeUnassigned = TenantSelection::selectedEmpresaIncludesUnassigned();

        $empresaField = $tableAlias . '.' . $empresaColumn;
        $plantaField = $tableAlias . '.' . $plantaColumn;

        if (! empty($plantaIds)) {
            $query->whereIn($plantaField, $plantaIds);
            return;
        }

        if (! empty($empresaIds)) {
            if ($includeUnassigned) {
                $query->where(function (Builder $inner) use ($empresaField, $empresaIds) {
                    $inner->whereIn($empresaField, $empresaIds)
                        ->orWhereNull($empresaField)
                        ->orWhere($empresaField, 0);
                });
                return;
            }

            $query->whereIn($empresaField, $empresaIds);
            return;
        }

        $query->whereRaw('1=0');
    }
}
