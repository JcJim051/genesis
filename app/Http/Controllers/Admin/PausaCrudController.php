<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pausa;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PausaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Pausa::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pausa');
        CRUD::setEntityNameStrings('pausa activa', 'pausas activas');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('nombre');
        CRUD::column('categoria');
        CRUD::column('tiempo_minimo_segundos')->label('Tiempo mínimo (s)');
        CRUD::column('activa')->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('nombre')->type('text')->label('Nombre');
        CRUD::field('descripcion')->type('textarea')->label('Descripción');
        CRUD::field('categoria')->type('select_from_array')->label('Categoría')->options([
            'virtual' => 'Virtual',
            'osteomuscular' => 'Osteomuscular',
            'psicosocial' => 'Psicosocial',
            'otros' => 'Otros',
        ])->allows_null(true);
        CRUD::field('video_url')->type('url')->label('Video URL');
        CRUD::field('tiempo_minimo_segundos')->type('number')->label('Tiempo mínimo (segundos)')->default(60);
        CRUD::field('activa')->type('checkbox')->label('Activa');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $response = $this->traitStore();
        $this->ensureFormulario();
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->ensureFormulario();
        return $response;
    }

    private function ensureFormulario(): void
    {
        $pausa = $this->crud->entry;
        if (! $pausa) {
            return;
        }
        if (! $pausa->formulario) {
            $pausa->formulario()->create();
        }
    }
}
