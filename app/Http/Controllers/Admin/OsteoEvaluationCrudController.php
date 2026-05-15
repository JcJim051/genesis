<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Models\Empleado;
use App\Models\OsteoEvaluation;
use App\Models\OsteoEvaluationAnswer;
use App\Models\OsteoTemplate;
use App\Models\Programa;
use App\Models\ProgramaCaso;
use App\Services\Google\GoogleSheetsMatrixService;
use App\Support\IntegrationSettings;
use App\Support\TenantSelection;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OsteoEvaluationCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(OsteoEvaluation::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/osteo-evaluation');
        CRUD::setEntityNameStrings('valoración osteomuscular', 'valoraciones osteomusculares');

        $this->crud->denyAccess(['create', 'update', 'delete']);

        $this->scopeMode = 'fields';
        $this->scopeModelClass = OsteoEvaluation::class;
        $this->scopeEmpresaField = 'cliente_id';
        $this->scopePlantaField = 'sucursal_id';
        $this->applyTenantScope($this->crud);
    }

    protected function setupListOperation(): void
    {
        $this->crud->setOperationSetting('lineButtonsAsDropdown', false);
        $this->crud->setOperationSetting('lineButtonsAsDropdownMinimum', 999);
        $this->crud->setOperationSetting('responsiveTable', false);
        $this->crud->setOperationSetting('persistentTable', false);
        $this->crud->setOperationSetting('showTableColumnPicker', false);

        CRUD::addColumn(['name' => 'fecha_valoracion', 'label' => 'Fecha', 'type' => 'date']);
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
            'visibleInTable' => false,
        ]);
        CRUD::addColumn(['name' => 'estado', 'label' => 'Estado', 'type' => 'text']);
        CRUD::addColumn(['name' => 'evaluador', 'label' => 'Evaluador', 'type' => 'text', 'visibleInTable' => false]);

        $this->crud->addButtonFromView('line', 'osteo_evaluation_edit', 'osteo_evaluation_edit', 'beginning');
        $this->crud->addButtonFromView('line', 'osteo_evaluation_pdf', 'osteo_evaluation_pdf', 'end');
        $this->crud->addButtonFromView('top', 'osteo_evaluation_open_drive', 'osteo_evaluation_open_drive', 'beginning');
        $this->crud->addButtonFromView('top', 'osteo_evaluation_sync_drive', 'osteo_evaluation_sync_drive', 'beginning');
        $this->crud->addButtonFromView('top', 'osteo_evaluation_create_manual', 'osteo_evaluation_create_manual', 'beginning');
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('admin.osteo_evaluations.show');
        $this->crud->addButtonFromView('top', 'osteo_evaluation_edit', 'osteo_evaluation_edit', 'beginning');
        $this->crud->addButtonFromView('top', 'osteo_evaluation_pdf', 'osteo_evaluation_pdf', 'end');
    }

    public function show($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitShow($id);
    }

    public function createForCase(Request $request, int $programaCasoId)
    {
        $programaCaso = $this->loadCaseOrFail($programaCasoId);
        $templates = $this->templatesForEmpleado($programaCaso->empleado_id);
        if ($templates->isEmpty()) {
            \Alert::error('La empresa no tiene plantilla osteomuscular activa. Configúrala primero.')->flash();
            return redirect(backpack_url('osteo-template'));
        }
        $selectedTemplate = $templates->firstWhere('id', (int) $request->query('template_id')) ?: $templates->first();

        return $this->renderForm($programaCaso, $selectedTemplate, null, $templates);
    }

    public function createManual()
    {
        $empleados = $this->scopedEmployeesQuery()
            ->with(['cliente', 'sucursal'])
            ->orderBy('nombre')
            ->limit(500)
            ->get();

        return view('admin.osteo_evaluations.create_manual', [
            'empleados' => $empleados,
        ]);
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
        ]);
        $empleado = Empleado::query()->findOrFail((int) $data['empleado_id']);
        if (! $this->canUseEmpleado($empleado)) {
            abort(403, 'No autorizado para usar esta persona.');
        }

        $programa = Programa::query()->where('slug', 'osteomuscular')->first();
        if (! $programa) {
            \Alert::error('No existe el programa Osteomuscular configurado.')->flash();
            return back();
        }

        $programaCaso = ProgramaCaso::query()->firstOrCreate(
            ['empleado_id' => $empleado->id, 'programa_id' => $programa->id],
            ['estado' => 'No evaluado', 'origen' => 'manual']
        );

        return redirect(backpack_url('programa-caso/' . $programaCaso->id . '/osteo-evaluation/create'));
    }

    public function storeForCase(Request $request, int $programaCasoId)
    {
        $programaCaso = $this->loadCaseOrFail($programaCasoId);
        $template = $this->resolveTemplateOrFail($programaCaso->empleado_id, (int) $request->input('template_id'));
        $this->saveEvaluation($request, $programaCaso, $template, null);

        \Alert::success('Valoración osteomuscular creada correctamente.')->flash();
        return redirect(backpack_url('osteo-evaluation'));
    }

    public function editForm(int $id)
    {
        $this->enforceEntryScopeOrFail($id);
        $evaluation = OsteoEvaluation::with(['programaCaso.empleado.cliente', 'programaCaso.empleado.sucursal', 'template.sections.fields', 'answers'])
            ->findOrFail($id);
        $templates = $this->templatesForEmpleado((int) $evaluation->empleado_id);
        $template = $templates->firstWhere('id', $evaluation->template_id) ?: $evaluation->template;

        return $this->renderForm($evaluation->programaCaso, $template, $evaluation, $templates);
    }

    public function updateForm(Request $request, int $id)
    {
        $this->enforceEntryScopeOrFail($id);
        $evaluation = OsteoEvaluation::with(['programaCaso.empleado'])->findOrFail($id);
        $template = $this->resolveTemplateOrFail((int) $evaluation->empleado_id, (int) $request->input('template_id', $evaluation->template_id));
        $this->saveEvaluation($request, $evaluation->programaCaso, $template, $evaluation);

        \Alert::success('Valoración osteomuscular actualizada correctamente.')->flash();
        return redirect(backpack_url('osteo-evaluation'));
    }

    public function pdf(int $id)
    {
        $this->enforceEntryScopeOrFail($id);
        $evaluation = OsteoEvaluation::with([
            'programaCaso.empleado.cliente',
            'programaCaso.empleado.sucursal',
            'template.sections.fields',
            'answers.field',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('admin.osteo_evaluations.pdf', [
            'evaluation' => $evaluation,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('valoracion_osteomuscular_' . $evaluation->id . '.pdf');
    }

    public function syncMatrixToDrive(GoogleSheetsMatrixService $service)
    {
        $scopeLabel = TenantSelection::humanLabel();

        $evaluations = $this->baseScopedQuery()
            ->with(['empleado.cliente', 'empleado.sucursal', 'template'])
            ->orderBy('fecha_valoracion', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        if ($evaluations->isEmpty()) {
            \Alert::warning('No hay valoraciones osteomusculares en el alcance seleccionado.')->flash();
            return back();
        }

        $grouped = $evaluations->groupBy(fn ($e) => (int) ($e->cliente_id ?? 0));
        $ok = 0;
        $errors = [];
        foreach ($grouped as $clienteId => $rows) {
            if ($clienteId <= 0) {
                continue;
            }
            $empresaNombre = trim((string) optional($rows->first()->empleado?->cliente)->nombre);
            if ($empresaNombre === '') {
                $empresaNombre = 'Empresa_' . $clienteId;
            }

            $matrixRows = $rows->map(function ($evaluation) {
                return [
                    optional($evaluation->fecha_valoracion)->format('Y-m-d') ?? '',
                    $evaluation->empleado?->cliente?->nombre ?? '',
                    $evaluation->empleado?->sucursal?->nombre ?? '',
                    $evaluation->empleado?->nombre ?? '',
                    $evaluation->empleado?->cedula ?? '',
                    $evaluation->template?->nombre_publico ?? '',
                    ucfirst((string) $evaluation->estado),
                    (string) ($evaluation->evaluador ?? ''),
                    (string) ($evaluation->cargo_profesional ?? ''),
                    (string) ($evaluation->licencia ?? ''),
                    (string) ($evaluation->observaciones ?? ''),
                    backpack_url('osteo-evaluation/' . $evaluation->id . '/show'),
                ];
            })->values()->all();

            try {
                $service->syncOsteoCompanyMatrix($clienteId, $empresaNombre, $matrixRows, $scopeLabel);
                $ok++;
            } catch (Throwable $e) {
                $errors[] = $empresaNombre . ' → ' . $e->getMessage();
            }
        }

        if ($ok > 0) {
            \Alert::success("Sincronización completada. Matrices actualizadas: {$ok}.")->flash();
        }
        if (! empty($errors)) {
            \Alert::error('Errores en sincronización:<br>' . implode('<br>', array_map(fn ($x) => e($x), $errors)))->flash();
        }

        return back();
    }

    public function openDriveMatrices()
    {
        $evaluations = $this->baseScopedQuery()->get(['cliente_id']);
        if ($evaluations->isEmpty()) {
            \Alert::warning('No hay valoraciones en el alcance seleccionado.')->flash();
            return back();
        }
        $clienteIds = $evaluations->pluck('cliente_id')->filter(fn ($id) => (int) $id > 0)->unique()->values()->all();

        $items = [];
        foreach ($clienteIds as $clienteId) {
            $cfg = json_decode((string) IntegrationSettings::get('google_drive.company_sheet_osteo.' . $clienteId, ''), true);
            $url = (string) ($cfg['spreadsheet_url'] ?? '');
            if ($url === '') {
                continue;
            }
            $empresa = \App\Models\Cliente::query()->find($clienteId);
            $items[] = [
                'empresa' => $empresa?->nombre ?? ('Empresa #' . $clienteId),
                'url' => $url,
            ];
        }

        if (empty($items)) {
            \Alert::warning('Aún no hay matrices sincronizadas en Drive para este alcance.')->flash();
            return back();
        }
        if (count($items) === 1) {
            return redirect()->away($items[0]['url']);
        }

        return view('admin.osteo_evaluations.drive_links', [
            'items' => $items,
            'scopeLabel' => TenantSelection::humanLabel(),
        ]);
    }

    private function baseScopedQuery(): Builder
    {
        $query = OsteoEvaluation::query();
        $this->applyTenantScope($query);
        return $query;
    }

    private function renderForm(ProgramaCaso $programaCaso, OsteoTemplate $template, ?OsteoEvaluation $evaluation = null, $templates = null)
    {
        $answers = [];
        if ($evaluation) {
            foreach ($evaluation->answers as $ans) {
                $key = (string) $ans->field_id;
                if ($ans->lado !== null && $ans->lado !== '') {
                    $key .= ':' . $ans->lado;
                }
                $answers[$key] = [
                    'valor' => $ans->valor,
                    'observacion' => $ans->observacion,
                ];
            }
        }

        return view('admin.osteo_evaluations.form', [
            'programaCaso' => $programaCaso,
            'template' => $template->loadMissing(['sections.fields']),
            'evaluation' => $evaluation,
            'answers' => $answers,
            'templates' => $templates ?: collect([$template]),
        ]);
    }

    private function saveEvaluation(Request $request, ProgramaCaso $programaCaso, OsteoTemplate $template, ?OsteoEvaluation $evaluation = null): OsteoEvaluation
    {
        $data = $request->validate([
            'template_id' => 'required|integer|exists:osteo_templates,id',
            'fecha_valoracion' => 'required|date',
            'estado' => 'required|in:borrador,final',
            'evaluador' => 'nullable|string|max:255',
            'licencia' => 'nullable|string|max:255',
            'cargo_profesional' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $evaluation = $evaluation ?: new OsteoEvaluation();
        $evaluation->fill([
            'programa_caso_id' => $programaCaso->id,
            'empleado_id' => $programaCaso->empleado_id,
            'cliente_id' => $programaCaso->empleado?->cliente_id,
            'sucursal_id' => $programaCaso->empleado?->sucursal_id,
            'template_id' => $template->id,
            'fecha_valoracion' => $data['fecha_valoracion'],
            'estado' => $data['estado'],
            'evaluador' => $data['evaluador'] ?? null,
            'licencia' => $data['licencia'] ?? null,
            'cargo_profesional' => $data['cargo_profesional'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'created_by' => $evaluation->exists ? $evaluation->created_by : backpack_user()?->id,
        ]);
        $evaluation->save();

        $fields = $template->sections->flatMap(fn ($section) => $section->fields)->keyBy('id');
        $payloadAnswers = (array) $request->input('answers', []);
        $payloadObs = (array) $request->input('answer_obs', []);

        DB::transaction(function () use ($evaluation, $fields, $payloadAnswers, $payloadObs) {
            OsteoEvaluationAnswer::where('evaluation_id', $evaluation->id)->delete();

            foreach ($fields as $fieldId => $field) {
                $fieldId = (string) $fieldId;
                $tipo = (string) $field->tipo;
                if (in_array($tipo, ['laterality_pair', 'plus_minus_pair'], true)) {
                    foreach (['D', 'I'] as $side) {
                        $value = trim((string) ($payloadAnswers[$fieldId][$side] ?? ''));
                        $obs = trim((string) ($payloadObs[$fieldId][$side] ?? ''));
                        if ($value === '' && $obs === '') {
                            continue;
                        }
                        OsteoEvaluationAnswer::create([
                            'evaluation_id' => $evaluation->id,
                            'field_id' => (int) $fieldId,
                            'lado' => $side,
                            'valor' => $value,
                            'observacion' => $obs !== '' ? $obs : null,
                        ]);
                    }
                    continue;
                }

                $value = trim((string) ($payloadAnswers[$fieldId] ?? ''));
                $obs = trim((string) ($payloadObs[$fieldId] ?? ''));
                if ($value === '' && $obs === '') {
                    continue;
                }
                OsteoEvaluationAnswer::create([
                    'evaluation_id' => $evaluation->id,
                    'field_id' => (int) $fieldId,
                    'lado' => null,
                    'valor' => $value,
                    'observacion' => $obs !== '' ? $obs : null,
                ]);
            }
        });

        return $evaluation;
    }

    private function loadCaseOrFail(int $id): ProgramaCaso
    {
        $case = ProgramaCaso::query()->with(['empleado.cliente', 'empleado.sucursal', 'programa'])->findOrFail($id);
        if (($case->programa?->slug ?? null) !== 'osteomuscular') {
            abort(422, 'Este caso no pertenece al programa Osteomuscular.');
        }
        if (! $this->canUseEmpleado($case->empleado)) {
            abort(403, 'No autorizado para usar esta persona.');
        }
        return $case;
    }

    private function templatesForEmpleado(int $empleadoId)
    {
        $empleado = Empleado::query()->findOrFail($empleadoId);
        $query = OsteoTemplate::query()
            ->where('activo', true)
            ->where('cliente_id', (int) $empleado->cliente_id)
            ->with(['sections.fields'])
            ->orderByDesc('id');
        return $query->get();
    }

    private function resolveTemplateOrFail(int $empleadoId, int $templateId): OsteoTemplate
    {
        $templates = $this->templatesForEmpleado($empleadoId);
        $template = $templates->firstWhere('id', $templateId);
        if (! $template) {
            abort(422, 'La plantilla seleccionada no pertenece a la empresa de la persona.');
        }
        return $template;
    }

    private function scopedEmployeesQuery(): Builder
    {
        $query = Empleado::query();
        if (! ($this->isAdmin() && TenantSelection::isAdminBypass())) {
            $this->applyScopeByFields($query, 'cliente_id', 'sucursal_id');
        }
        return $query;
    }

    private function canUseEmpleado(?Empleado $empleado): bool
    {
        if (! $empleado) {
            return false;
        }
        if ($this->isAdmin() && TenantSelection::isAdminBypass()) {
            return true;
        }
        $ids = $this->scopedEmployeesQuery()->whereKey($empleado->id)->pluck('id')->all();
        return in_array($empleado->id, $ids, true);
    }
}

