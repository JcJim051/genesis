<?php

namespace App\Http\Controllers\Admin;

use App\Models\PausaOpcion;
use App\Models\PausaPregunta;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PausaOpcionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(PausaOpcion::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pausa-opcion');
        CRUD::setEntityNameStrings('opción', 'opciones');
    }

    protected function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'pregunta',
            'type' => 'closure',
            'label' => 'Pregunta',
            'function' => fn ($entry) => optional($entry->pregunta)->texto,
        ]);
        CRUD::column('texto');
        CRUD::column('valor');
        CRUD::column('orden');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('pregunta_id')
            ->type('select')
            ->label('Pregunta')
            ->entity('pregunta')
            ->model(PausaPregunta::class)
            ->attribute('texto');

        CRUD::field('texto')->type('text')->label('Texto');
        CRUD::field('valor')->type('text')->label('Valor');
        CRUD::field('orden')->type('number')->label('Orden')->default(0);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
