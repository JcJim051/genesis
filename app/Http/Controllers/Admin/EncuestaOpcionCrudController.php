<?php

namespace App\Http\Controllers\Admin;

use App\Models\EncuestaOpcion;
use App\Models\EncuestaPregunta;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EncuestaOpcionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        if (! backpack_user() || ! backpack_user()->hasRole('Administrador')) {
            abort(403);
        }

        CRUD::setModel(EncuestaOpcion::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/encuesta-opcion');
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
        CRUD::column('puntaje');
        CRUD::column('orden');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('pregunta_id')
            ->type('select')
            ->label('Pregunta')
            ->entity('pregunta')
            ->model(EncuestaPregunta::class)
            ->attribute('texto');
        CRUD::field('texto')->type('text')->label('Texto');
        CRUD::field('puntaje')->type('number')->label('Puntaje');
        CRUD::field('orden')->type('number')->label('Orden');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
