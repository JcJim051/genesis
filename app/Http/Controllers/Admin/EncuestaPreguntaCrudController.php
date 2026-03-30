<?php

namespace App\Http\Controllers\Admin;

use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EncuestaPreguntaCrudController extends CrudController
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

        CRUD::setModel(EncuestaPregunta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/encuesta-pregunta');
        CRUD::setEntityNameStrings('pregunta', 'preguntas');
    }

    protected function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'encuesta',
            'type' => 'closure',
            'label' => 'Encuesta',
            'function' => fn ($entry) => optional($entry->encuesta)->titulo,
        ]);
        CRUD::column('texto');
        CRUD::column('orden');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('encuesta_id')
            ->type('select')
            ->label('Encuesta')
            ->entity('encuesta')
            ->model(Encuesta::class)
            ->attribute('titulo');
        CRUD::field('texto')->type('textarea')->label('Pregunta');
        CRUD::field('orden')->type('number')->label('Orden');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
