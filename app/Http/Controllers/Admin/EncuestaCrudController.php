<?php

namespace App\Http\Controllers\Admin;

use App\Models\Encuesta;
use App\Models\Programa;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EncuestaCrudController extends CrudController
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

        CRUD::setModel(Encuesta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/encuesta');
        CRUD::setEntityNameStrings('encuesta', 'encuestas');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('titulo');
        CRUD::addColumn([
            'name' => 'programa',
            'type' => 'closure',
            'label' => 'Programa',
            'function' => fn ($entry) => optional($entry->programa)->nombre,
        ]);
        CRUD::column('umbral_puntaje');
        CRUD::column('activa')->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('titulo')->type('text')->label('Título');
        CRUD::field('programa_id')
            ->type('select')
            ->label('Programa')
            ->entity('programa')
            ->model(Programa::class)
            ->attribute('nombre');
        CRUD::field('umbral_puntaje')->type('number')->label('Umbral puntaje');
        CRUD::field('activa')->type('checkbox')->label('Activa');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
