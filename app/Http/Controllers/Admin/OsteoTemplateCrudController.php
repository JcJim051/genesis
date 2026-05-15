<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Models\Cliente;
use App\Models\OsteoTemplate;
use App\Models\OsteoTemplateField;
use App\Models\OsteoTemplateSection;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OsteoTemplateCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }

    public function setup(): void
    {
        $this->authorizeTemplateManagers();
        CRUD::setModel(OsteoTemplate::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/osteo-template');
        CRUD::setEntityNameStrings('plantilla osteomuscular', 'plantillas osteomusculares');
        $this->crud->denyAccess(['create', 'update']);

        $this->scopeMode = 'fields';
        $this->scopeModelClass = OsteoTemplate::class;
        $this->scopeEmpresaField = 'cliente_id';
        $this->scopePlantaField = null;
        $this->applyTenantScope($this->crud);
    }

    protected function setupListOperation(): void
    {
        $this->crud->addButtonFromView('top', 'osteo_template_builder_create', 'osteo_template_builder_create', 'beginning');
        $this->crud->addButtonFromView('line', 'osteo_template_builder', 'osteo_template_builder', 'beginning');
        $this->crud->addColumn(['name' => 'nombre_publico', 'label' => 'Nombre', 'type' => 'text']);
        $this->crud->addColumn(['name' => 'codigo', 'label' => 'Código', 'type' => 'text']);
        $this->crud->addColumn(['name' => 'segmento', 'label' => 'Segmento', 'type' => 'text']);
        $this->crud->addColumn([
            'name' => 'cliente',
            'label' => 'Empresa',
            'type' => 'closure',
            'function' => fn ($entry) => $entry->cliente?->nombre ?? '-',
        ]);
        $this->crud->addColumn(['name' => 'activo', 'label' => 'Activa', 'type' => 'boolean']);
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('admin.osteo_templates.show');
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
        $template = $id ? OsteoTemplate::with(['sections.fields'])->findOrFail($id) : new OsteoTemplate();
        return view('admin.osteo_templates.builder', [
            'template' => $template,
            'clientes' => $this->clientesPermitidos(),
            'selectedClienteIds' => [$template->cliente_id],
            'sections' => $template->exists ? $template->sections->map(function ($s) {
                return [
                    'id' => $s->id,
                    'titulo' => $s->titulo,
                    'orden' => $s->orden,
                    'fields' => $s->fields->map(fn ($f) => [
                        'id' => $f->id,
                        'label' => $f->label,
                        'key_name' => $f->key_name,
                        'tipo' => $f->tipo,
                        'options_json' => implode("\n", $f->options_json ?? []),
                        'meta_json' => json_encode($f->meta_json ?? []),
                        'required' => (bool) $f->required,
                        'orden' => $f->orden,
                    ])->values()->all(),
                ];
            })->values()->all() : [],
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

        $targetClienteIds = collect($data['cliente_ids'])->map(fn ($x) => (int) $x)->unique()->values()->all();
        foreach ($targetClienteIds as $cid) {
            $this->validateClientePermitido($cid);
        }

        DB::transaction(function () use ($id, $targetClienteIds, $data, $request) {
            $base = $id ? OsteoTemplate::findOrFail($id) : null;
            foreach ($targetClienteIds as $index => $clienteId) {
                $template = ($index === 0 && $base) ? $base : new OsteoTemplate();
                $template->fill([
                    'cliente_id' => $clienteId,
                    'nombre_publico' => $data['nombre_publico'],
                    'codigo' => $data['codigo'] ?? null,
                    'segmento' => $data['segmento'] ?? null,
                    'activo' => (bool) ($data['activo'] ?? false),
                ]);
                $template->save();
                $this->syncStructure($template, $request, $index > 0);
            }
        });

        return redirect(backpack_url('osteo-template'))->with('success', 'Plantilla osteomuscular guardada.');
    }

    public function seedBase()
    {
        $clientes = $this->clientesPermitidos();
        if ($clientes->isEmpty()) {
            return back()->with('error', 'No hay empresas permitidas para crear plantilla base.');
        }

        foreach ($clientes as $cliente) {
            $tpl = OsteoTemplate::firstOrCreate([
                'cliente_id' => $cliente->id,
                'nombre_publico' => 'VALORACIÓN OSTEOMUSCULAR BASE',
            ], [
                'codigo' => 'OSTEO-BASE',
                'segmento' => 'General',
                'activo' => true,
            ]);

            if ($tpl->sections()->exists()) {
                continue;
            }
            $sec1 = $tpl->sections()->create(['titulo' => 'Datos generales', 'orden' => 10]);
            $sec2 = $tpl->sections()->create(['titulo' => 'Sintomatología referida', 'orden' => 20]);
            $sec3 = $tpl->sections()->create(['titulo' => 'Fuerza y movilidad', 'orden' => 30]);
            $sec4 = $tpl->sections()->create(['titulo' => 'Pruebas funcionales', 'orden' => 40]);
            $sec5 = $tpl->sections()->create(['titulo' => 'Recomendaciones', 'orden' => 50]);

            $sec1->fields()->createMany([
                ['label' => 'Ciudad', 'key_name' => 'ciudad', 'tipo' => 'text', 'required' => true, 'orden' => 10],
                ['label' => 'Área funcional', 'key_name' => 'area_funcional', 'tipo' => 'text', 'required' => true, 'orden' => 20],
                ['label' => 'Sexo', 'key_name' => 'sexo', 'tipo' => 'select', 'options_json' => ['Femenino', 'Masculino'], 'orden' => 30],
                ['label' => 'Antigüedad en la entidad', 'key_name' => 'antiguedad', 'tipo' => 'text', 'orden' => 40],
                ['label' => 'EPS', 'key_name' => 'eps', 'tipo' => 'text', 'orden' => 50],
            ]);
            $sec2->fields()->createMany([
                ['label' => 'Zona dolor', 'key_name' => 'zona_dolor', 'tipo' => 'text', 'orden' => 10],
                ['label' => 'Intensidad dolor (1-10)', 'key_name' => 'intensidad', 'tipo' => 'pain_scale_1_10', 'orden' => 20],
                ['label' => 'Frecuencia', 'key_name' => 'frecuencia', 'tipo' => 'select', 'options_json' => ['Ocasional', 'Frecuente', 'Constante'], 'orden' => 30],
                ['label' => 'Tipo dolor', 'key_name' => 'tipo_dolor', 'tipo' => 'select', 'options_json' => ['Quemante', 'Punzante'], 'orden' => 40],
            ]);
            $sec3->fields()->createMany([
                ['label' => 'Hombro flexión', 'key_name' => 'hombro_flexion', 'tipo' => 'laterality_pair', 'meta_json' => ['choices' => ['Normal', 'Disminuida', 'Ausente']], 'orden' => 10],
                ['label' => 'Codo extensión', 'key_name' => 'codo_extension', 'tipo' => 'laterality_pair', 'meta_json' => ['choices' => ['Normal', 'Disminuida', 'Ausente']], 'orden' => 20],
                ['label' => 'Rodilla flexión', 'key_name' => 'rodilla_flexion', 'tipo' => 'laterality_pair', 'meta_json' => ['choices' => ['Normal', 'Disminuida', 'Ausente']], 'orden' => 30],
            ]);
            $sec4->fields()->createMany([
                ['label' => 'Lasegue', 'key_name' => 'prueba_lasegue', 'tipo' => 'plus_minus_pair', 'orden' => 10],
                ['label' => 'Trendelenburg', 'key_name' => 'prueba_trendelenburg', 'tipo' => 'plus_minus_pair', 'orden' => 20],
                ['label' => 'Phalen', 'key_name' => 'prueba_phalen', 'tipo' => 'plus_minus_pair', 'orden' => 30],
            ]);
            $sec5->fields()->createMany([
                ['label' => 'Recomendaciones físicas', 'key_name' => 'rec_fisicas', 'tipo' => 'textarea', 'orden' => 10],
                ['label' => 'Recomendaciones al puesto', 'key_name' => 'rec_puesto', 'tipo' => 'textarea', 'orden' => 20],
                ['label' => 'Valoración EPS', 'key_name' => 'val_eps', 'tipo' => 'textarea', 'orden' => 30],
                ['label' => 'Otro', 'key_name' => 'otro', 'tipo' => 'textarea', 'orden' => 40],
            ]);
        }

        return redirect(backpack_url('osteo-template'))->with('success', 'Plantilla base osteomuscular creada.');
    }

    private function syncStructure(OsteoTemplate $template, Request $request, bool $forceCreate = false): void
    {
        $sectionIds = [];
        foreach ((array) $request->input('sections', []) as $sPayload) {
            $titulo = trim((string) ($sPayload['titulo'] ?? ''));
            if ($titulo === '') {
                continue;
            }
            $section = (! $forceCreate && ! empty($sPayload['id'])) ? OsteoTemplateSection::find($sPayload['id']) : new OsteoTemplateSection();
            if (! $section) {
                $section = new OsteoTemplateSection();
            }
            $section->template_id = $template->id;
            $section->titulo = $titulo;
            $section->orden = (int) ($sPayload['orden'] ?? 0);
            $section->save();
            $sectionIds[] = $section->id;

            $fieldIds = [];
            foreach ((array) ($sPayload['fields'] ?? []) as $fPayload) {
                $label = trim((string) ($fPayload['label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $field = (! $forceCreate && ! empty($fPayload['id'])) ? OsteoTemplateField::find($fPayload['id']) : new OsteoTemplateField();
                if (! $field) {
                    $field = new OsteoTemplateField();
                }
                $field->section_id = $section->id;
                $field->label = $label;
                $field->key_name = trim((string) ($fPayload['key_name'] ?? ''));
                $field->tipo = (string) ($fPayload['tipo'] ?? 'text');
                $opts = collect(explode("\n", (string) ($fPayload['options_json'] ?? '')))->map(fn ($x) => trim($x))->filter()->values()->all();
                $field->options_json = ! empty($opts) ? $opts : null;
                $metaRaw = trim((string) ($fPayload['meta_json'] ?? ''));
                $field->meta_json = $metaRaw !== '' ? (json_decode($metaRaw, true) ?: null) : null;
                $field->required = (bool) ($fPayload['required'] ?? false);
                $field->orden = (int) ($fPayload['orden'] ?? 0);
                $field->save();
                $fieldIds[] = $field->id;
            }
            OsteoTemplateField::where('section_id', $section->id)->whereNotIn('id', $fieldIds ?: [0])->delete();
        }

        OsteoTemplateSection::where('template_id', $template->id)->whereNotIn('id', $sectionIds ?: [0])->delete();
    }

    private function authorizeTemplateManagers(): void
    {
        if (! backpack_user() || ! backpack_user()->hasAnyRole(['Administrador', 'Coordinador general'])) {
            abort(403, 'No autorizado para administrar plantillas osteomusculares.');
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
        $scopeEmpresaIds = collect(\App\Support\TenantSelection::empresaIds())->map(fn ($id) => (int) $id)->all();
        if (! in_array($clienteId, $scopeEmpresaIds, true) && ! backpack_user()->empresas()->where('clientes.id', $clienteId)->exists()) {
            abort(403, 'No autorizado para usar esa empresa.');
        }
    }
}

