<?php

namespace App\Http\Controllers\Admin;

use App\Models\ColombiaHoliday;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ColombiaHolidayCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup(): void
    {
        $this->authorizeManagers();

        CRUD::setModel(ColombiaHoliday::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/colombia-holiday');
        CRUD::setEntityNameStrings('festivo', 'festivos Colombia');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('fecha')->type('date');
        CRUD::column('nombre');
        CRUD::column('anio');
        CRUD::column('activo')->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('fecha')->type('date')->label('Fecha');
        CRUD::field('nombre')->type('text')->label('Nombre');
        CRUD::field('anio')->type('number')->label('Año');
        CRUD::field('activo')->type('checkbox')->label('Activo');

        $this->crud->setValidation([
            'fecha' => 'required|date',
            'nombre' => 'required|string|max:255',
            'anio' => 'required|integer|min:2000|max:2100',
        ]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    private function authorizeManagers(): void
    {
        if (! backpack_user() || ! backpack_user()->hasAnyRole(['Administrador', 'Coordinador general'])) {
            abort(403, 'No autorizado para administrar festivos.');
        }
    }
}
