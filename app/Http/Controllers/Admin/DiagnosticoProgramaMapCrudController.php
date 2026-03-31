<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cie10;
use App\Models\DiagnosticoProgramaMap;
use App\Models\Programa;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;

class DiagnosticoProgramaMapCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        if (! backpack_user() || ! backpack_user()->hasRole('Administrador')) {
            abort(403);
        }

        CRUD::setModel(DiagnosticoProgramaMap::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/diagnostico-programa');
        CRUD::setEntityNameStrings('mapeo', 'mapeos');
    }

    protected function setupListOperation(): void
    {
        $this->crud->addButtonFromView('top', 'default_rules', 'diagnostico_programa_defaults', 'beginning');

        CRUD::column('codigo_cie10')->label('CIE10');
        CRUD::column('diagnostico_texto')->label('Diagnóstico');
        CRUD::addColumn([
            'name' => 'programa',
            'type' => 'closure',
            'label' => 'Programa',
            'function' => fn ($entry) => optional($entry->programa)->nombre,
        ]);
        CRUD::column('regla_activa')->type('boolean')->label('Activa');
        CRUD::column('prioridad');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::addField([
            'name' => 'cie10_id',
            'type' => 'cie10_select',
            'label' => 'CIE10',
            'options' => Cie10::query()
                ->orderBy('codigo')
                ->get()
                ->mapWithKeys(function ($row) {
                    return [$row->id => $row->codigo . ' - ' . $row->diagnostico];
                })
                ->toArray(),
            'lookup_url' => backpack_url('cie10/__ID__/lookup'),
            'target_codigo' => 'codigo_cie10',
            'target_diagnostico' => 'diagnostico_texto',
            'wrapper' => ['class' => 'col-md-4'],
        ]);
        CRUD::addField([
            'name' => 'cie10_diagnostico_helper',
            'type' => 'custom_html',
            'value' => '<div class="text-muted small">Selecciona un CIE10 para completar automáticamente el diagnóstico.</div>',
            'wrapper' => ['class' => 'col-md-12'],
        ]);
        CRUD::field('codigo_cie10')->type('text')->label('CIE10 (manual)')->wrapper(['class' => 'col-md-4']);
        CRUD::field('diagnostico_texto')->type('text')->label('Diagnóstico')->wrapper(['class' => 'col-md-4']);
        CRUD::field('programa_id')
            ->type('select')
            ->label('Programa')
            ->entity('programa')
            ->model(Programa::class)
            ->attribute('nombre');
        CRUD::field('regla_activa')->type('checkbox')->label('Activa');
        CRUD::field('prioridad')->type('number')->label('Prioridad');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->syncCie10ToRequest();
        return $this->traitStore();
    }

    public function update()
    {
        $this->syncCie10ToRequest();
        return $this->traitUpdate();
    }

    public function defaults()
    {
        if (! backpack_user() || ! backpack_user()->hasRole('Administrador')) {
            abort(403);
        }

        $rules = [
            'osteomuscular' => ['M%', 'S%', 'T%'],
            'visual' => ['H0%', 'H1%', 'H2%', 'H3%', 'H4%', 'H5%'],
            'auditivo' => ['H6%', 'H7%', 'H8%', 'H9%'],
            'psicosocial' => ['F%'],
            'cardiovascular' => ['I%'],
        ];

        $created = 0;
        $updated = 0;
        $missing = [];

        foreach ($rules as $slug => $cie10s) {
            $programa = $this->findProgramaBySlugOrName($slug);
            if (! $programa) {
                $missing[] = $slug;
                continue;
            }

            foreach ($cie10s as $code) {
                $map = DiagnosticoProgramaMap::updateOrCreate(
                    [
                        'programa_id' => $programa->id,
                        'codigo_cie10' => $code,
                    ],
                    [
                        'diagnostico_texto' => null,
                        'regla_activa' => true,
                        'prioridad' => 10,
                    ]
                );

                if ($map->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        }

        Alert::add('success', "Reglas creadas/actualizadas. Nuevas: {$created}. Actualizadas: {$updated}.")->flash();
        if (! empty($missing)) {
            Alert::add('warning', 'No se encontraron programas: ' . implode(', ', $missing) . '.')->flash();
        }

        return redirect(backpack_url('diagnostico-programa'));
    }

    private function findProgramaBySlugOrName(string $slug): ?Programa
    {
        $programa = Programa::where('slug', $slug)->first();
        if ($programa) {
            return $programa;
        }

        $name = str_replace('-', ' ', $slug);
        return Programa::where('nombre', 'like', '%' . $name . '%')->first();
    }

    private function syncCie10ToRequest(): void
    {
        $cie10Id = request()->input('cie10_id');
        if (! $cie10Id) {
            return;
        }

        $cie10 = Cie10::find($cie10Id);
        if (! $cie10) {
            return;
        }

        request()->merge([
            'codigo_cie10' => $cie10->codigo,
            'diagnostico_texto' => $cie10->diagnostico,
        ]);
    }
}
