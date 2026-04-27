<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Models\Cliente;
use App\Models\IptTemplate;
use App\Models\IptTemplateQuestion;
use App\Models\IptTemplateRequirement;
use App\Models\IptTemplateRiskRule;
use App\Models\IptTemplateSection;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class IptTemplateCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }

    public function setup(): void
    {
        $this->authorizeTemplateManagers();

        CRUD::setModel(IptTemplate::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/ipt-template');
        CRUD::setEntityNameStrings('plantilla IPT', 'plantillas IPT');

        $this->crud->denyAccess(['create', 'update']);

        $this->scopeMode = 'fields';
        $this->scopeModelClass = IptTemplate::class;
        $this->scopeEmpresaField = 'cliente_id';
        $this->scopePlantaField = null;
        $this->applyTenantScope($this->crud);
    }

    protected function setupListOperation(): void
    {
        $this->crud->addButtonFromView('top', 'ipt_template_builder_create', 'ipt_template_builder_create', 'beginning');
        $this->crud->addButtonFromView('top', 'ipt_template_seed_vdt', 'ipt_template_seed_vdt', 'beginning');
        $this->crud->addButtonFromView('line', 'ipt_template_builder', 'ipt_template_builder', 'beginning');

        CRUD::addColumn([
            'name' => 'nombre_publico',
            'label' => 'Nombre público',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'codigo',
            'label' => 'Código',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'segmento',
            'label' => 'Segmento',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'cliente',
            'label' => 'Empresa',
            'type' => 'closure',
            'function' => fn ($entry) => $entry->cliente?->nombre ?? '-',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('cliente', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'questions_count',
            'label' => 'Preguntas',
            'type' => 'closure',
            'function' => function ($entry) {
                $entry->loadMissing('sections.questions');
                return (string) $entry->sections->sum(fn ($section) => $section->questions->count());
            },
        ]);

        CRUD::addColumn([
            'name' => 'activo',
            'label' => 'Activa',
            'type' => 'boolean',
        ]);
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('admin.ipt_templates.show');
    }

    public function show($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitShow($id);
    }

    public function destroy($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitDestroy($id);
    }

    public function builder(?int $id = null)
    {
        if ($id) {
            $this->enforceEntryScopeOrFail($id);
        }

        $template = $id
            ? IptTemplate::with(['sections.questions', 'riskRules', 'requirements'])->findOrFail($id)
            : new IptTemplate();

        $clientes = $this->clientesPermitidos();

        $sections = [];
        if ($template->exists) {
            foreach ($template->sections->sortBy('orden')->values() as $section) {
                $questions = [];
                foreach ($section->questions->sortBy('orden')->values() as $question) {
                    $questions[] = [
                        'key' => 'q' . $question->id,
                        'id' => $question->id,
                        'texto' => $question->texto,
                        'orden' => $question->orden,
                        'scorable' => (bool) $question->scorable,
                        'si_score' => (int) $question->si_score,
                        'score_on_answer' => (string) ($question->score_on_answer ?? 'si'),
                    ];
                }

                $sections[] = [
                    'key' => 's' . $section->id,
                    'id' => $section->id,
                    'titulo' => $section->titulo,
                    'orden' => $section->orden,
                    'questions' => $questions,
                ];
            }
        }

        $riskRules = $template->exists
            ? $template->riskRules->sortBy('orden')->values()->map(function ($rule) {
                return [
                    'key' => 'r' . $rule->id,
                    'id' => $rule->id,
                    'nivel' => $rule->nivel,
                    'min_score' => (int) $rule->min_score,
                    'max_score' => (int) $rule->max_score,
                    'followup_months' => (int) $rule->followup_months,
                    'orden' => (int) $rule->orden,
                ];
            })->all()
            : [];

        $requirements = $template->exists
            ? $template->requirements->sortBy('orden')->values()->map(function ($item) {
                return [
                    'key' => 'm' . $item->id,
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                    'orden' => (int) $item->orden,
                    'activo' => (bool) $item->activo,
                ];
            })->all()
            : [];

        return view('admin.ipt_templates.builder', [
            'template' => $template,
            'clientes' => $clientes,
            'selectedClienteIds' => [$template->cliente_id],
            'sections' => $sections,
            'riskRules' => $riskRules,
            'requirements' => $requirements,
        ]);
    }

    public function builderSave(Request $request, ?int $id = null)
    {
        if ($id) {
            $this->enforceEntryScopeOrFail($id);
        }

        $data = $request->validate([
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'integer|exists:clientes,id',
            'nombre_publico' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:80',
            'segmento' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $targetClienteIds = collect($data['cliente_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        foreach ($targetClienteIds as $clienteId) {
            $this->validateClientePermitido($clienteId);
        }

        DB::transaction(function () use ($request, $data, $id, $targetClienteIds) {
            $baseTemplate = $id ? IptTemplate::findOrFail($id) : null;

            foreach ($targetClienteIds as $index => $clienteId) {
                if ($index === 0 && $baseTemplate) {
                    $template = $baseTemplate;
                } else {
                    $template = IptTemplate::query()->firstOrNew([
                        'cliente_id' => $clienteId,
                        'nombre_publico' => $data['nombre_publico'],
                        'codigo' => $data['codigo'] ?? null,
                        'segmento' => $data['segmento'] ?? null,
                    ]);
                }

                $template->fill([
                    'cliente_id' => $clienteId,
                    'nombre_publico' => $data['nombre_publico'],
                    'codigo' => $data['codigo'] ?? null,
                    'segmento' => $data['segmento'] ?? null,
                    'activo' => (bool) ($data['activo'] ?? false),
                ]);
                $template->save();

                // Para empresas adicionales, no reutilizamos ids previos del formulario.
                $this->syncTemplateStructure($template, $request, $index > 0);
            }
        });

        $count = count($targetClienteIds);
        $message = $count > 1
            ? 'Plantilla IPT guardada y aplicada en ' . $count . ' empresas.'
            : 'Plantilla IPT guardada correctamente.';

        return redirect(backpack_url('ipt-template'))->with('success', $message);
    }

    public function seedVdt()
    {
        $clientes = $this->clientesPermitidos();
        if ($clientes->isEmpty()) {
            return redirect(backpack_url('ipt-template'))->with('error', 'No hay empresas disponibles para precargar VDT.');
        }

        foreach ($clientes as $cliente) {
            Artisan::call('ipt:seed-vdt', ['cliente_id' => $cliente->id]);
        }

        return redirect(backpack_url('ipt-template'))->with('success', 'Plantilla VDT precargada para las empresas permitidas.');
    }

    private function authorizeTemplateManagers(): void
    {
        if (! backpack_user() || ! backpack_user()->hasAnyRole(['Administrador', 'Coordinador general'])) {
            abort(403, 'No autorizado para administrar plantillas IPT.');
        }
    }

    private function clientesPermitidos()
    {
        $query = Cliente::query()->orderBy('nombre');
        if (! \App\Support\TenantSelection::isAdminBypass()) {
            $ids = \App\Support\TenantSelection::empresaIds();
            $query->whereIn('id', $ids ?: [0]);
        }

        return $query->get();
    }

    private function validateClientePermitido(int $clienteId): void
    {
        if (\App\Support\TenantSelection::isAdminBypass()) {
            return;
        }

        $allowed = backpack_user()->empresas()->where('clientes.id', $clienteId)->exists();
        if (! $allowed) {
            abort(403, 'No autorizado para usar esa empresa.');
        }
    }

    private function syncTemplateStructure(IptTemplate $template, Request $request, bool $forceCreate = false): void
    {
        $sections = $request->input('sections', []);
        $sectionMap = [];
        $sectionIds = [];

        foreach ($sections as $sKey => $sectionPayload) {
            $titulo = trim((string) ($sectionPayload['titulo'] ?? ''));
            if ($titulo === '') {
                continue;
            }

            $section = (! $forceCreate && ! empty($sectionPayload['id']))
                ? IptTemplateSection::find($sectionPayload['id'])
                : null;
            if (! $section) {
                $section = new IptTemplateSection();
            }

            $section->template_id = $template->id;
            $section->titulo = $titulo;
            $section->orden = (int) ($sectionPayload['orden'] ?? 0);
            $section->save();

            $sectionMap[$sKey] = $section->id;
            $sectionIds[] = $section->id;
        }

        if (! empty($sectionIds)) {
            IptTemplateSection::where('template_id', $template->id)
                ->whereNotIn('id', $sectionIds)
                ->delete();
        } else {
            IptTemplateSection::where('template_id', $template->id)->delete();
        }

        foreach ($sections as $sKey => $sectionPayload) {
            $sectionId = $sectionMap[$sKey] ?? null;
            if (! $sectionId) {
                continue;
            }

            $questionIds = [];
            foreach (($sectionPayload['questions'] ?? []) as $questionPayload) {
                $texto = trim((string) ($questionPayload['texto'] ?? ''));
                if ($texto === '') {
                    continue;
                }

                $question = (! $forceCreate && ! empty($questionPayload['id']))
                    ? IptTemplateQuestion::find($questionPayload['id'])
                    : null;
                if (! $question) {
                    $question = new IptTemplateQuestion();
                }

                $question->section_id = $sectionId;
                $question->texto = $texto;
                $question->tipo = 'si_no_na';
                $question->orden = (int) ($questionPayload['orden'] ?? 0);
                $question->scorable = (bool) ($questionPayload['scorable'] ?? false);
                $question->si_score = (int) ($questionPayload['si_score'] ?? 1);
                $scoreOnAnswer = strtolower((string) ($questionPayload['score_on_answer'] ?? 'si'));
                $question->score_on_answer = in_array($scoreOnAnswer, ['si', 'no'], true) ? $scoreOnAnswer : 'si';
                $question->save();

                $questionIds[] = $question->id;
            }

            if (! empty($questionIds)) {
                IptTemplateQuestion::where('section_id', $sectionId)
                    ->whereNotIn('id', $questionIds)
                    ->delete();
            } else {
                IptTemplateQuestion::where('section_id', $sectionId)->delete();
            }
        }

        $riskRulesPayload = $request->input('risk_rules', []);
        $riskRuleIds = [];
        foreach ($riskRulesPayload as $riskPayload) {
            $nivel = strtolower(trim((string) ($riskPayload['nivel'] ?? '')));
            if (! in_array($nivel, ['bajo', 'medio', 'alto'], true)) {
                continue;
            }

            $riskRule = (! $forceCreate && ! empty($riskPayload['id']))
                ? IptTemplateRiskRule::find($riskPayload['id'])
                : null;
            if (! $riskRule) {
                $riskRule = new IptTemplateRiskRule();
            }

            $riskRule->template_id = $template->id;
            $riskRule->nivel = $nivel;
            $riskRule->min_score = (int) ($riskPayload['min_score'] ?? 0);
            $riskRule->max_score = (int) ($riskPayload['max_score'] ?? 0);
            $riskRule->followup_months = max(1, (int) ($riskPayload['followup_months'] ?? 1));
            $riskRule->orden = (int) ($riskPayload['orden'] ?? 0);
            $riskRule->save();

            $riskRuleIds[] = $riskRule->id;
        }

        if (! empty($riskRuleIds)) {
            IptTemplateRiskRule::where('template_id', $template->id)
                ->whereNotIn('id', $riskRuleIds)
                ->delete();
        } else {
            IptTemplateRiskRule::where('template_id', $template->id)->delete();
        }

        $requirementsPayload = $request->input('requirements', []);
        $requirementIds = [];
        foreach ($requirementsPayload as $itemPayload) {
            $nombre = trim((string) ($itemPayload['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            $item = (! $forceCreate && ! empty($itemPayload['id']))
                ? IptTemplateRequirement::find($itemPayload['id'])
                : null;
            if (! $item) {
                $item = new IptTemplateRequirement();
            }

            $item->template_id = $template->id;
            $item->nombre = $nombre;
            $item->orden = (int) ($itemPayload['orden'] ?? 0);
            $item->activo = (bool) ($itemPayload['activo'] ?? true);
            $item->save();

            $requirementIds[] = $item->id;
        }

        if (! empty($requirementIds)) {
            IptTemplateRequirement::where('template_id', $template->id)
                ->whereNotIn('id', $requirementIds)
                ->delete();
        } else {
            IptTemplateRequirement::where('template_id', $template->id)->delete();
        }
    }
}
