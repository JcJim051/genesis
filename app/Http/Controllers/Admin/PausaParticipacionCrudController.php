<?php

namespace App\Http\Controllers\Admin;

use App\Models\PausaParticipacion;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PausaParticipacionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(PausaParticipacion::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pausa-participacion');
        CRUD::setEntityNameStrings('participación', 'participaciones');
    }

    protected function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'pausa',
            'type' => 'closure',
            'label' => 'Pausa',
            'function' => fn ($entry) => optional($entry->envio?->pausa)->nombre,
        ]);
        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'function' => fn ($entry) => optional($entry->empleado)->nombre,
        ]);
        CRUD::addColumn([
            'name' => 'estado',
            'type' => 'closure',
            'label' => 'Estado',
            'escaped' => false,
            'function' => function ($entry) {
                $estado = (string) ($entry->estado ?? '');
                $class = 'secondary';
                if ($estado === 'completada') {
                    $class = 'success';
                } elseif ($estado === 'pendiente_activacion') {
                    $class = 'warning';
                } elseif ($estado === 'pendiente') {
                    $class = 'info';
                }
                return '<span class="badge bg-' . $class . '">' . e($estado) . '</span>';
            },
        ]);
        CRUD::column('tiempo_activo_total')->label('Tiempo activo (s)');
        CRUD::column('tab_switch_count')->label('Cambios pestaña');
        CRUD::column('respondido_en');
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('admin.pausas.participacion_show');
    }
}
