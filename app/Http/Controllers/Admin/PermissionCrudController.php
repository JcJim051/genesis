<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PermissionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Permission;

class PermissionCrudController extends CrudController
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

        CRUD::setModel(Permission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/permission');
        CRUD::setEntityNameStrings('permiso', 'permisos');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('id');
        CRUD::column('name')->label('Nombre');
        CRUD::column('guard_name')->label('Guard');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(PermissionRequest::class);

        CRUD::field('name')->type('text')->label('Nombre');
        CRUD::addField([
            'name' => 'guard_name',
            'type' => 'hidden',
            'value' => 'web',
        ]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
