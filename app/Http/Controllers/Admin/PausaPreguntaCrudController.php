<?php

namespace App\Http\Controllers\Admin;

use App\Models\PausaFormulario;
use App\Models\PausaPregunta;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PausaPreguntaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(PausaPregunta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pausa-pregunta');
        CRUD::setEntityNameStrings('pregunta', 'preguntas');
    }

    protected function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'formulario',
            'type' => 'closure',
            'label' => 'Pausa',
            'function' => fn ($entry) => optional($entry->formulario?->pausa)->nombre,
        ]);
        CRUD::column('texto');
        CRUD::column('tipo');
        CRUD::column('orden');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('formulario_id')
            ->type('select')
            ->label('Pausa')
            ->entity('formulario')
            ->model(PausaFormulario::class)
            ->attribute('nombre');

        CRUD::field('texto')->type('text')->label('Pregunta');
        CRUD::field('tipo')->type('select_from_array')->label('Tipo')->options([
            'abierta' => 'Abierta',
            'opcion' => 'Opción múltiple',
        ])->allows_null(false);
        CRUD::field('orden')->type('number')->label('Orden')->default(0);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
