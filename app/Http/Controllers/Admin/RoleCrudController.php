<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RoleRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleCrudController extends CrudController
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

        CRUD::setModel(Role::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/role');
        CRUD::setEntityNameStrings('rol', 'roles');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('id');
        CRUD::column('name')->label('Nombre');
        CRUD::column('guard_name')->label('Guard');
        CRUD::addColumn([
            'name' => 'permissions',
            'type' => 'closure',
            'label' => 'Permisos',
            'function' => function ($entry) {
                return $entry->permissions->pluck('name')->implode(', ');
            },
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(RoleRequest::class);

        CRUD::field('name')->type('text')->label('Nombre');
        CRUD::addField([
            'name' => 'guard_name',
            'type' => 'hidden',
            'value' => 'web',
        ]);

        CRUD::addField([
            'name' => 'permissions',
            'type' => 'select_multiple',
            'label' => 'Permisos',
            'model' => Permission::class,
            'attribute' => 'name',
            'pivot' => true,
            'options' => function ($query) {
                return $query->orderBy('name')->get();
            },
        ]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
