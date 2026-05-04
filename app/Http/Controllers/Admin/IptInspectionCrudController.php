<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Models\IptInspection;
use App\Models\IptInspectionAnswer;
use App\Models\IptInspectionRequirement;
use App\Models\IptTemplate;
use App\Models\Empleado;
use App\Models\Programa;
use App\Models\ProgramaCaso;
use App\Services\Ipt\BusinessDayService;
use App\Services\Ipt\IptScoringService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IptInspectionCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(IptInspection::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/ipt-inspection');
        CRUD::setEntityNameStrings('inspección IPT', 'inspecciones IPT');

        $this->crud->denyAccess(['create', 'update', 'delete']);

        $this->scopeMode = 'fields';
        $this->scopeModelClass = IptInspection::class;
        $this->scopeEmpresaField = 'cliente_id';
        $this->scopePlantaField = 'sucursal_id';
        $this->applyTenantScope($this->crud);
    }

    protected function setupListOperation(): void
    {
        $this->crud->setListView('admin.ipt_inspections.list');

        $today = Carbon::today();
        $plus7 = $today->copy()->addDays(7);
        $plus30 = $today->copy()->addDays(30);

        $base = $this->baseScopedQueryForList();

        $indicators = [
            'total' => (clone $base)->count(),
            'abiertas' => (clone $base)->where('estado', 'abierto')->count(),
            'vencidas' => (clone $base)
                ->whereNotNull('fecha_proximo_seguimiento_sugerida')
                ->whereDate('fecha_proximo_seguimiento_sugerida', '<', $today->toDateString())
                ->count(),
            'proximas_7' => (clone $base)
                ->whereNotNull('fecha_proximo_seguimiento_sugerida')
                ->whereDate('fecha_proximo_seguimiento_sugerida', '>=', $today->toDateString())
                ->whereDate('fecha_proximo_seguimiento_sugerida', '<=', $plus7->toDateString())
                ->count(),
            'proximas_30' => (clone $base)
                ->whereNotNull('fecha_proximo_seguimiento_sugerida')
                ->whereDate('fecha_proximo_seguimiento_sugerida', '>=', $today->toDateString())
                ->whereDate('fecha_proximo_seguimiento_sugerida', '<=', $plus30->toDateString())
                ->count(),
        ];

        $alertas = $this->baseScopedQueryForList()
            ->with(['empleado'])
            ->whereNotNull('fecha_proximo_seguimiento_sugerida')
            ->whereDate('fecha_proximo_seguimiento_sugerida', '<=', $plus30->toDateString())
            ->orderBy('fecha_proximo_seguimiento_sugerida')
            ->limit(12)
            ->get();

        $scopeLabel = 'Global';
        if (! $this->isAdmin()) {
            if (! empty($this->plantaIdsForUser())) {
                $scopeLabel = 'Planta';
            } elseif (! empty($this->empresaIdsForUser())) {
                $scopeLabel = 'Empresa';
            } else {
                $scopeLabel = 'Sin alcance';
            }
        }

        $this->crud->setOperationSetting('ipt_alerts_summary', [
            'today' => $today,
            'indicators' => $indicators,
            'alerts' => $alertas,
            'scope_label' => $scopeLabel,
        ]);

        CRUD::addColumn([
            'name' => 'fecha_inspeccion',
            'label' => 'Fecha',
            'type' => 'date',
        ]);

        CRUD::addColumn([
            'name' => 'empleado',
            'label' => 'Persona',
            'type' => 'closure',
            'escaped' => false,
            'function' => fn ($entry) => \App\Support\EmpleadoLink::render(
                $entry->empleado,
                trim(($entry->empleado?->nombre ?? '') . ' · ' . ($entry->empleado?->cedula ?? ''))
            ),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('empleado', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', '%' . $searchTerm . '%')
                        ->orWhere('cedula', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'template',
            'label' => 'Plantilla',
            'type' => 'closure',
            'function' => fn ($entry) => $entry->template?->nombre_publico ?? '-',
        ]);

        CRUD::addColumn([
            'name' => 'tipo',
            'label' => 'Tipo',
            'type' => 'closure',
            'function' => fn ($entry) => $entry->tipo === 'followup' ? 'Seguimiento' : 'Inicial',
        ]);

        CRUD::addColumn([
            'name' => 'puntaje_riesgo',
            'label' => 'Puntaje / Riesgo',
            'type' => 'closure',
            'function' => function ($entry) {
                $puntaje = (int) ($entry->puntaje_total ?? 0);
                $riesgo = strtoupper((string) ($entry->nivel_riesgo ?? ''));
                return $riesgo !== '' ? "{$puntaje} · {$riesgo}" : (string) $puntaje;
            },
            'priority' => 1,
            'visibleInTable' => true,
        ]);

        CRUD::addColumn([
            'name' => 'fecha_seguimiento',
            'label' => 'Fecha seguimiento',
            'type' => 'closure',
            'function' => fn ($entry) => $entry->fecha_proximo_seguimiento_sugerida?->format('Y-m-d') ?? '—',
            'priority' => 1,
            'visibleInTable' => true,
        ]);

        CRUD::addColumn([
            'name' => 'estado',
            'label' => 'Estado',
            'type' => 'text',
        ]);

        $this->crud->addButtonFromView('line', 'ipt_inspection_edit', 'ipt_inspection_edit', 'beginning');
        $this->crud->addButtonFromView('line', 'ipt_inspection_followup', 'ipt_inspection_followup', 'end');
        $this->crud->addButtonFromView('top', 'ipt_inspection_create_manual', 'ipt_inspection_create_manual', 'beginning');
    }

    private function baseScopedQueryForList(): Builder
    {
        $query = IptInspection::query();
        $this->applyTenantScope($query);

        return $query;
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('admin.ipt_inspections.show');
        $this->crud->addButtonFromView('top', 'ipt_inspection_edit', 'ipt_inspection_edit', 'beginning');
        $this->crud->addButtonFromView('top', 'ipt_inspection_followup', 'ipt_inspection_followup', 'end');
    }

    public function show($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitShow($id);
    }

    public function createInitialForCase(Request $request, int $programaCasoId)
    {
        $programaCaso = $this->loadCaseOrFail($programaCasoId);
        $templates = $this->templatesForCase($programaCaso);
        if ($templates->isEmpty()) {
            $empresaId = (int) ($programaCaso->empleado->cliente_id ?? 0);
            $empresaNombre = $programaCaso->empleado->cliente?->nombre ?? 'la empresa de la persona seleccionada';
            \Alert::error("No hay plantillas IPT activas para {$empresaNombre}. Selecciona una plantilla de esa empresa o crea una nueva.")->flash();
            return redirect(backpack_url('ipt-template') . '?cliente_id=' . $empresaId);
        }
        $selectedTemplateId = (int) $request->query('template_id', 0);
        $template = $selectedTemplateId > 0
            ? ($templates->firstWhere('id', $selectedTemplateId) ?: $templates->first())
            : $templates->first();

        return $this->renderForm('initial', null, $programaCaso, $template, null, [], $templates);
    }

    public function createManual()
    {
        $empleados = $this->scopedEmployeesQuery()
            ->with(['cliente', 'sucursal'])
            ->orderBy('nombre')
            ->limit(500)
            ->get();

        $templatePoolQuery = IptTemplate::query()
            ->where('activo', true);

        if (! ($this->isAdmin() && \App\Support\TenantSelection::isAdminBypass())) {
            $this->applyScopeByFields($templatePoolQuery, 'cliente_id', null);
        }

        $templatePool = $templatePoolQuery
            ->orderBy('nombre_publico')
            ->get(['id', 'cliente_id', 'nombre_publico', 'codigo', 'segmento']);

        return view('admin.ipt_inspections.create_manual', [
            'empleados' => $empleados,
            'templatePool' => $templatePool,
        ]);
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'empleado_id' => 'required|integer',
            'template_id' => 'required|integer|exists:ipt_templates,id',
        ]);

        $empleado = $this->scopedEmployeesQuery()->findOrFail((int) $data['empleado_id']);

        $programa = Programa::query()
            ->where('slug', 'osteomuscular')
            ->first();

        if (! $programa) {
            return redirect(backpack_url('ipt-inspection'))
                ->with('error', 'No se encontró el programa Osteomuscular.');
        }

        $caso = ProgramaCaso::firstOrCreate(
            [
                'empleado_id' => $empleado->id,
                'programa_id' => $programa->id,
            ],
            [
                'estado' => 'No evaluado',
                'origen' => 'IPT manual',
                'sugerido_por' => backpack_user()?->name ?? 'Sistema',
                'fecha_inicio' => now()->toDateString(),
            ]
        );

        $redirect = backpack_url('programa-caso/' . $caso->id . '/ipt/create-initial');
        $template = IptTemplate::query()
            ->whereKey((int) $data['template_id'])
            ->where('activo', true)
            ->first();

        if (! $template || (int) $template->cliente_id !== (int) $empleado->cliente_id) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['template_id' => 'La plantilla seleccionada no corresponde a la empresa de la persona.']);
        }
        $redirect .= '?template_id=' . (int) $template->id;

        return redirect($redirect);
    }

    public function storeInitialForCase(Request $request, int $programaCasoId)
    {
        $programaCaso = $this->loadCaseOrFail($programaCasoId);
        if ($this->templatesForCase($programaCaso)->isEmpty()) {
            $empresaId = (int) ($programaCaso->empleado->cliente_id ?? 0);
            $empresaNombre = $programaCaso->empleado->cliente?->nombre ?? 'la empresa de la persona seleccionada';
            \Alert::error("No hay plantillas IPT activas para {$empresaNombre}. Selecciona una plantilla de esa empresa o crea una nueva.")->flash();
            return redirect(backpack_url('ipt-template') . '?cliente_id=' . $empresaId);
        }
        $template = $this->resolveTemplateForCase($programaCaso, (int) $request->input('template_id'));

        return $this->persistInspection($request, 'initial', $programaCaso, null, null, $template);
    }

    public function createFollowup(int $inspectionId)
    {
        $initial = $this->resolveInitialInspection($inspectionId);
        $this->enforceEntryScopeOrFail($initial->id);

        $programaCaso = $initial->programaCaso;
        $template = $initial->template;

        return $this->renderForm('followup', null, $programaCaso, $template, $initial, [], collect([$template]));
    }

    public function storeFollowup(Request $request, int $inspectionId)
    {
        $initial = $this->resolveInitialInspection($inspectionId);
        $this->enforceEntryScopeOrFail($initial->id);

        return $this->persistInspection($request, 'followup', $initial->programaCaso, $initial);
    }

    public function editForm(int $id)
    {
        $inspection = IptInspection::with(['programaCaso.programa', 'programaCaso.empleado', 'template.sections.questions', 'template.riskRules', 'template.requirements', 'answers', 'requirements'])
            ->findOrFail($id);
        $this->enforceEntryScopeOrFail($inspection->id);

        $initial = $inspection->tipo === 'followup'
            ? $inspection->initialInspection
            : null;

        $oldAnswers = $inspection->answers->pluck('respuesta', 'question_id')->all();
        $oldRequirements = $inspection->requirements->pluck('aplica', 'requirement_id')->map(fn ($v) => (bool) $v)->all();

        return $this->renderForm($inspection->tipo, $inspection, $inspection->programaCaso, $inspection->template, $initial, [
            'answers' => $oldAnswers,
            'requirements' => $oldRequirements,
        ], collect([$inspection->template]));
    }

    public function updateForm(Request $request, int $id)
    {
        $inspection = IptInspection::with(['programaCaso.programa', 'template.sections.questions', 'template.riskRules'])
            ->findOrFail($id);
        $this->enforceEntryScopeOrFail($inspection->id);

        $initial = $inspection->tipo === 'followup'
            ? $this->resolveInitialInspection((int) ($inspection->initial_inspection_id ?? $inspection->id))
            : null;

        return $this->persistInspection(
            $request,
            $inspection->tipo,
            $inspection->programaCaso,
            $initial,
            $inspection
        );
    }

    private function renderForm(
        string $tipo,
        ?IptInspection $inspection,
        ProgramaCaso $programaCaso,
        IptTemplate $template,
        ?IptInspection $initial,
        array $oldState,
        ?Collection $templates = null
    )
    {
        $template->loadMissing(['sections.questions', 'riskRules', 'requirements']);

        $answers = old('answers', $oldState['answers'] ?? []);
        $requirements = old('requirements', $oldState['requirements'] ?? []);

        return view('admin.ipt_inspections.form', [
            'tipo' => $tipo,
            'inspection' => $inspection,
            'programaCaso' => $programaCaso,
            'template' => $template,
            'initialInspection' => $initial,
            'answers' => $answers,
            'requirements' => $requirements,
            'templates' => $templates ?? collect([$template]),
        ]);
    }

    private function persistInspection(
        Request $request,
        string $tipo,
        ProgramaCaso $programaCaso,
        ?IptInspection $initial = null,
        ?IptInspection $editing = null
    ) {
        $template = $editing?->template ?: $this->resolveTemplateForCase($programaCaso);

        $fotoAntesRequired = ($editing?->foto_antes ? 'nullable' : 'required') . '|image|max:5120';
        $fotoDespuesRequired = ($editing?->foto_despues ? 'nullable' : 'required') . '|image|max:5120';

        $validation = $request->validate([
            'fecha_inspeccion' => 'required|date',
            'template_id' => 'nullable|integer|exists:ipt_templates,id',
            'foto_antes' => $fotoAntesRequired,
            'foto_despues' => $fotoDespuesRequired,
            'hallazgos' => 'nullable|string',
            'recomendaciones' => 'nullable|string',
            'accion' => 'nullable|string',
            'responsable' => 'nullable|string|max:255',
            'estado' => 'nullable|in:abierto,cerrado',
            'seguimiento_exitoso' => 'nullable|in:0,1',
            'answers' => 'required|array',
            'answers.*' => 'nullable|in:si,no,na',
            'requirements' => 'nullable|array',
        ]);

        $template->loadMissing(['sections.questions', 'riskRules', 'requirements']);

        if ($tipo === 'followup' && ! $initial) {
            abort(422, 'Los seguimientos requieren una inspección inicial.');
        }

        if ($tipo === 'initial' && $initial) {
            abort(422, 'La inspección inicial no debe tener inspección inicial relacionada.');
        }

        $scoring = app(IptScoringService::class)->evaluate($template, $validation['answers']);
        $risk = $scoring['risk'];

        $fechaInspeccion = Carbon::parse($validation['fecha_inspeccion']);
        $followupDate = null;
        if ($risk) {
            $followupDate = app(BusinessDayService::class)
                ->addMonthsAndAdjust($fechaInspeccion, (int) $risk['followup_months'])
                ->toDateString();
        }

        DB::transaction(function () use ($editing, $programaCaso, $template, $tipo, $initial, $validation, $scoring, $risk, $followupDate, $fechaInspeccion) {
            $inspection = $editing ?: new IptInspection();

            $fotoAntesPath = $inspection->foto_antes;
            $fotoDespuesPath = $inspection->foto_despues;

            if (request()->hasFile('foto_antes')) {
                if ($fotoAntesPath) {
                    Storage::disk('public')->delete($fotoAntesPath);
                }
                $fotoAntesPath = request()->file('foto_antes')->store('ipt-evidencias', 'public');
            }

            if (request()->hasFile('foto_despues')) {
                if ($fotoDespuesPath) {
                    Storage::disk('public')->delete($fotoDespuesPath);
                }
                $fotoDespuesPath = request()->file('foto_despues')->store('ipt-evidencias', 'public');
            }

            $inspection->fill([
                'programa_caso_id' => $programaCaso->id,
                'empleado_id' => $programaCaso->empleado_id,
                'cliente_id' => $programaCaso->empleado->cliente_id,
                'sucursal_id' => $programaCaso->empleado->sucursal_id,
                'template_id' => $template->id,
                'tipo' => $tipo,
                'initial_inspection_id' => $tipo === 'followup' ? $initial?->id : null,
                'fecha_inspeccion' => $fechaInspeccion->toDateString(),
                'puntaje_total' => (int) $scoring['total'],
                'nivel_riesgo' => $risk['nivel'] ?? null,
                'fecha_proximo_seguimiento_sugerida' => $followupDate,
                'foto_antes' => $fotoAntesPath,
                'foto_despues' => $fotoDespuesPath,
                'hallazgos' => $validation['hallazgos'] ?? null,
                'recomendaciones' => $validation['recomendaciones'] ?? null,
                'accion' => $validation['accion'] ?? null,
                'responsable' => $validation['responsable'] ?? null,
                'estado' => $validation['estado'] ?? 'abierto',
                'seguimiento_exitoso' => array_key_exists('seguimiento_exitoso', $validation)
                    ? (int) $validation['seguimiento_exitoso'] === 1
                    : null,
                'created_by' => $inspection->exists ? $inspection->created_by : backpack_user()?->id,
            ]);
            $inspection->save();

            IptInspectionAnswer::where('inspection_id', $inspection->id)->delete();
            foreach ($scoring['answers'] as $answer) {
                IptInspectionAnswer::create([
                    'inspection_id' => $inspection->id,
                    'question_id' => $answer['question_id'],
                    'respuesta' => $answer['respuesta'],
                    'score' => $answer['score'],
                ]);
            }

            IptInspectionRequirement::where('inspection_id', $inspection->id)->delete();
            $requirementsInput = $validation['requirements'] ?? [];
            foreach ($template->requirements->where('activo', true) as $requirement) {
                IptInspectionRequirement::create([
                    'inspection_id' => $inspection->id,
                    'requirement_id' => $requirement->id,
                    'aplica' => (bool) ($requirementsInput[$requirement->id] ?? false),
                ]);
            }
        });

        return redirect(backpack_url('ipt-inspection'))
            ->with('success', 'Inspección IPT guardada correctamente.');
    }

    private function loadCaseOrFail(int $programaCasoId): ProgramaCaso
    {
        $programaCaso = ProgramaCaso::with(['programa', 'empleado'])->findOrFail($programaCasoId);
        $this->enforceCaseScopeOrFail($programaCaso);

        if (($programaCaso->programa?->slug ?? null) !== 'osteomuscular') {
            abort(422, 'La IPT solo aplica para el programa Osteomuscular.');
        }

        return $programaCaso;
    }

    private function templatesForCase(ProgramaCaso $programaCaso): Collection
    {
        return IptTemplate::query()
            ->where('cliente_id', $programaCaso->empleado->cliente_id)
            ->where('activo', true)
            ->orderByDesc('id')
            ->get();
    }

    private function resolveTemplateForCase(ProgramaCaso $programaCaso, ?int $templateId = null): IptTemplate
    {
        $templates = $this->templatesForCase($programaCaso);
        if ($templates->isEmpty()) {
            abort(422, 'La empresa no tiene plantilla IPT activa. Configura una plantilla primero.');
        }

        if ($templateId) {
            $selected = $templates->firstWhere('id', $templateId);
            if (! $selected) {
                abort(422, 'La plantilla seleccionada no pertenece a la empresa del caso o está inactiva.');
            }

            return $selected;
        }

        $template = $templates->first();

        return $template;
    }

    private function resolveInitialInspection(int $inspectionId): IptInspection
    {
        $inspection = IptInspection::with(['programaCaso.programa', 'template'])
            ->findOrFail($inspectionId);

        if ($inspection->tipo === 'initial') {
            return $inspection;
        }

        $initial = $inspection->initialInspection;
        if (! $initial) {
            abort(422, 'Este seguimiento no tiene inspección inicial asociada.');
        }

        return $initial;
    }

    private function scopedEmployeesQuery(): Builder
    {
        $query = Empleado::query();

        if (! ($this->isAdmin() && \App\Support\TenantSelection::isAdminBypass())) {
            $this->applyScopeByFields($query, 'cliente_id', 'sucursal_id');
        }

        return $query;
    }

    private function enforceCaseScopeOrFail(ProgramaCaso $programaCaso): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $empresaIds = $this->empresaIdsForUser();
        $plantaIds = $this->plantaIdsForUser();

        if (! empty($plantaIds)) {
            if (! in_array((int) $programaCaso->empleado->sucursal_id, array_map('intval', $plantaIds), true)) {
                abort(403, 'No autorizado.');
            }

            return;
        }

        if (! empty($empresaIds)) {
            if (! in_array((int) $programaCaso->empleado->cliente_id, array_map('intval', $empresaIds), true)) {
                abort(403, 'No autorizado.');
            }

            return;
        }

        abort(403, 'No autorizado.');
    }
}
