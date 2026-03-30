<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Spatie\Permission\Models\Role;

class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        if (! backpack_user() || ! backpack_user()->hasRole('Administrador')) {
            abort(403);
        }

        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('usuario', 'usuarios');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('id');
        CRUD::column('name')->label('Nombre');
        CRUD::column('email')->label('Email');
        CRUD::addColumn([
            'name' => 'roles',
            'type' => 'closure',
            'label' => 'Roles',
            'function' => function ($entry) {
                return $entry->roles->pluck('name')->implode(', ');
            },
        ]);
        CRUD::addColumn([
            'name' => 'empresas',
            'type' => 'closure',
            'label' => 'Empresas',
            'function' => function ($entry) {
                return $entry->empresas->pluck('nombre')->implode(', ');
            },
        ]);
        CRUD::addColumn([
            'name' => 'plantas',
            'type' => 'closure',
            'label' => 'Plantas',
            'function' => function ($entry) {
                return $entry->plantas->pluck('nombre')->implode(', ');
            },
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(UserRequest::class);

        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Nombre',
            'wrapper' => ['class' => 'col-md-4'],
        ]);
        CRUD::addField([
            'name' => 'email',
            'type' => 'email',
            'label' => 'Email',
            'wrapper' => ['class' => 'col-md-4'],
        ]);
        CRUD::addField([
            'name' => 'password',
            'type' => 'password',
            'label' => 'Password',
            'wrapper' => ['class' => 'col-md-4'],
        ]);
        CRUD::addField([
            'name' => 'password_confirmation',
            'type' => 'password',
            'label' => 'Confirmar Password',
            'wrapper' => ['class' => 'col-md-4'],
        ]);
        CRUD::addField([
            'name' => 'roles',
            'type' => 'select2_multiple',
            'label' => 'Roles',
            'model' => Role::class,
            'attribute' => 'name',
            'pivot' => true,
            'wrapper' => ['class' => 'col-md-4'],
            'options' => function ($query) {
                return $query->orderBy('name')->get();
            },
        ]);
        CRUD::addField([
            'name' => 'empresas',
            'type' => 'select2_multiple',
            'label' => 'Empresas',
            'model' => Cliente::class,
            'attribute' => 'nombre',
            'pivot' => true,
            'wrapper' => ['class' => 'col-md-4'],
            'options' => function ($query) {
                return $query->orderBy('nombre')->get();
            },
        ]);
        CRUD::addField([
            'name' => 'plantas',
            'type' => 'select2_multiple',
            'label' => 'Plantas',
            'model' => Sucursal::class,
            'attribute' => 'nombre',
            'pivot' => true,
            'wrapper' => ['class' => 'col-md-4'],
            'hint' => 'Para Coordinador/Asesor general no es necesario seleccionar plantas; se asignan automáticamente según la(s) empresa(s).',
            'options' => function ($query) {
                return $query->orderBy('nombre')->get();
            },
        ]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
        CRUD::modifyField('password', [
            'hint' => 'Deja en blanco para mantener la contraseña actual.',
        ]);
        CRUD::modifyField('password_confirmation', [
            'hint' => 'Deja en blanco para mantener la contraseña actual.',
        ]);
    }

    public function store()
    {
        $response = $this->traitStore();
        $this->syncPlantasForGeneralRoles();
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->syncPlantasForGeneralRoles();
        return $response;
    }

    private function syncPlantasForGeneralRoles(): void
    {
        $entry = $this->crud->entry;
        if (! $entry) {
            return;
        }

        $request = $this->crud->getRequest();
        $roleIds = (array) $request->input('roles', []);
        $roles = Role::whereIn('id', $roleIds)->pluck('name')->all();
        $empresas = (array) $request->input('empresas', []);

        $isGeneral = in_array('Coordinador general', $roles, true)
            || in_array('Asesor externo general', $roles, true);

        if (! $isGeneral) {
            return;
        }

        if (empty($empresas)) {
            $entry->plantas()->sync([]);
            return;
        }

        $plantaIds = Sucursal::whereIn('cliente_id', $empresas)->pluck('id')->all();
        $entry->plantas()->sync($plantaIds);
    }
}
