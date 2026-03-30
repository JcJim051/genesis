<?php

namespace App\Http\Controllers\Admin;

use App\Models\Programa;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProgramaCrudController extends CrudController
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

        CRUD::setModel(Programa::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/programa');
        CRUD::setEntityNameStrings('programa', 'programas');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('id');
        CRUD::column('nombre');
        CRUD::column('slug');
        CRUD::column('tipo');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('nombre')->type('text')->label('Nombre');
        CRUD::field('slug')->type('text')->label('Slug');
        CRUD::field('tipo')->type('text')->label('Tipo');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
