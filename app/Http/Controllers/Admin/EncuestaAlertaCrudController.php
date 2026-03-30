<?php

namespace App\Http\Controllers\Admin;

use App\Models\Empleado;
use App\Models\EncuestaAlerta;
use App\Models\ProgramaCaso;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EncuestaAlertaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(EncuestaAlerta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/encuesta-alerta');
        CRUD::setEntityNameStrings('alerta', 'alertas');
        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();

        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'function' => fn ($entry) => optional($entry->empleado)->nombre,
        ]);
        CRUD::addColumn([
            'name' => 'programa',
            'type' => 'closure',
            'label' => 'Programa',
            'function' => fn ($entry) => optional($entry->programa)->nombre,
        ]);
        CRUD::column('puntaje');
        CRUD::column('estado');
        CRUD::addColumn([
            'name' => 'encuesta',
            'type' => 'closure',
            'label' => 'Encuesta',
            'function' => fn ($entry) => optional($entry->encuesta)->titulo,
        ]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->applyListScope();

        CRUD::field('estado')->type('select_from_array')->options([
            'pendiente' => 'Pendiente',
            'atendida' => 'Atendida',
        ])->allows_null(false);

        CRUD::addField([
            'name' => 'resultado_programa',
            'type' => 'select_from_array',
            'label' => 'Clasificar como',
            'options' => [
                '' => '-',
                'Probable' => 'Probable',
                'Confirmado' => 'Confirmado',
                'Descartar' => 'Descartar',
            ],
        ]);
    }

    protected function setupShowOperation(): void
    {
        $this->applyListScope();
        $this->crud->setShowView('admin.encuestas.alerta_show');
    }

    public function update()
    {
        $response = $this->traitUpdate();

        $alerta = $this->crud->entry;
        $resultado = request()->input('resultado_programa');

        if ($alerta && in_array($resultado, ['Probable', 'Confirmado'], true)) {
            ProgramaCaso::updateOrCreate(
                [
                    'empleado_id' => $alerta->empleado_id,
                    'programa_id' => $alerta->programa_id,
                ],
                [
                    'estado' => $resultado,
                    'origen' => 'encuesta',
                    'sugerido_por' => 'encuesta',
                ]
            );
        }

        return $response;
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta'])) {
            $this->crud->denyAccess(['delete', 'create']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function applyListScope(): void
    {
        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta'])) {
            $plantaIds = backpack_user()->plantas()->pluck('sucursals.id')->all();
            $empleadoIds = Empleado::whereIn('sucursal_id', $plantaIds ?: [0])->pluck('id');
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }
}
