<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActaIngreso;
use App\Models\Empleado;
use App\Models\Reincorporacion;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;

class ActaIngresoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(ActaIngreso::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/acta-ingreso');
        CRUD::setEntityNameStrings('acta ingreso', 'actas ingreso');
        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();

        $this->crud->addButtonFromView('line', 'pdf', 'acta_ingreso_pdf', 'end');

        CRUD::addColumn([
            'name' => 'reincorporacion',
            'type' => 'closure',
            'label' => 'Reincorporación',
            'function' => fn ($entry) => optional($entry->reincorporacion)->id,
        ]);
        CRUD::column('fecha_acta');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('created_by_user_id')
            ->type('hidden')
            ->default(backpack_user()?->id);

        CRUD::field('reincorporacion_id')
            ->type('select')
            ->label('Reincorporación')
            ->entity('reincorporacion')
            ->model(Reincorporacion::class)
            ->attribute('id');
        if (request()->filled('reincorporacion_id')) {
            CRUD::field('reincorporacion_id')->default(request()->get('reincorporacion_id'));
        }
        CRUD::field('fecha_acta')->type('date')->label('Fecha acta');
        CRUD::field('contenido')->type('textarea')->label('Contenido');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Coordinador de planta'])) {
            $this->crud->denyAccess(['delete']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function applyListScope(): void
    {
        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general'])) {
            $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
            $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
            $reincIds = Reincorporacion::whereIn('empleado_id', $empleadoIds)->pluck('id');
            $this->crud->addClause('whereIn', 'reincorporacion_id', $reincIds);
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta'])) {
            $plantaIds = backpack_user()->plantas()->pluck('sucursals.id')->all();
            $empleadoIds = Empleado::whereIn('sucursal_id', $plantaIds ?: [0])->pluck('id');
            $reincIds = Reincorporacion::whereIn('empleado_id', $empleadoIds)->pluck('id');
            $this->crud->addClause('whereIn', 'reincorporacion_id', $reincIds);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }

    public function pdf($id)
    {
        $acta = ActaIngreso::with('reincorporacion.empleado')->findOrFail($id);
        $pdf = Pdf::loadView('actas.ingreso_pdf', ['acta' => $acta]);
        return $pdf->download('acta_ingreso_' . $acta->id . '.pdf');
    }
}
