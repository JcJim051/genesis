<?php

namespace App\Http\Controllers\Admin;

use App\Models\Examen;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ExamenCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Examen::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/examen');
        CRUD::setEntityNameStrings('examen', 'exámenes');
        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        CRUD::column('cedula');
        CRUD::column('fecha_examen');
        CRUD::column('tipo_examen');
        CRUD::column('resultado_apto');
        CRUD::column('restricciones');
        CRUD::column('recomendaciones');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('cedula')->type('text')->label('Cédula');
        CRUD::field('fecha_examen')->type('date')->label('Fecha examen');
        CRUD::field('tipo_examen')->type('text')->label('Tipo examen');
        CRUD::field('resultado_apto')->type('text')->label('Resultado apto');
        CRUD::field('restricciones')->type('textarea')->label('Restricciones');
        CRUD::field('recomendaciones')->type('textarea')->label('Recomendaciones');
        CRUD::field('payload')->type('textarea')->label('Payload (JSON)')->hint('Opcional');
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

        if (backpack_user()->hasAnyRole(['Coordinador general'])) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo general', 'Asesor externo planta'])) {
            $this->crud->denyAccess(['create', 'update', 'delete']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }
}
