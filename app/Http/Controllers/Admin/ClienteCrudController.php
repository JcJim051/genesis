<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ClienteCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Cliente::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cliente');
        CRUD::setEntityNameStrings('empresa', 'empresas');

        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();

        CRUD::column('nombre')->label('Empresa');
        CRUD::column('codigo')->label('NIT');
        CRUD::column('ciudad');
        CRUD::column('telefono');
        CRUD::column('encargado');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ClienteRequest::class);
        $this->enforceEntryScope();

        CRUD::field('nombre')->type('text')->label('Empresa')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('codigo')->type('text')->label('NIT')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('direccion')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('ciudad')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('telefono')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('encargado')->type('text')->wrapper(['class' => 'form-group col-md-6']);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->enforceEntryScope();

        CRUD::column('nombre')->label('Empresa');
        CRUD::column('codigo')->label('NIT');
        CRUD::column('direccion');
        CRUD::column('ciudad');
        CRUD::column('telefono');
        CRUD::column('encargado');
    }

    public function store()
    {
        $response = $this->traitStore();

        if (! $this->isAdmin() && $this->crud->entry) {
            backpack_user()->empresas()->syncWithoutDetaching([$this->crud->entry->id]);
        }

        return $response;
    }

    public function destroy($id)
    {
        $this->enforceEntryScope((int) $id);
        return $this->traitDestroy($id);
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if ($this->isAdmin()) {
            return;
        }

        if ($this->hasAnyRole(['Coordinador general'])) {
            return;
        }

        if ($this->hasAnyRole(['Asesor externo general'])) {
            $this->crud->denyAccess(['delete']);
            return;
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $this->crud->denyAccess(['create', 'update', 'delete']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function applyListScope(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            $this->crud->addClause('whereIn', 'id', $empresaIds ?: [0]);
            return;
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $empresaIds = $this->empresaIdsFromPlantas();
            $this->crud->addClause('whereIn', 'id', $empresaIds ?: [0]);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }

    private function enforceEntryScope(?int $entryId = null): void
    {
        $entryId = $entryId ?? $this->crud->getCurrentEntryId();
        if (! $entryId || $this->isAdmin()) {
            return;
        }

        $query = Cliente::query()->whereKey($entryId);

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            $query->whereIn('id', $empresaIds ?: [0]);
        } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $empresaIds = $this->empresaIdsFromPlantas();
            $query->whereIn('id', $empresaIds ?: [0]);
        }

        if (! $query->exists()) {
            abort(403);
        }
    }

    private function isAdmin(): bool
    {
        return \App\Support\TenantSelection::isPlatformAdmin();
    }

    private function hasAnyRole(array $roles): bool
    {
        return backpack_user()->hasAnyRole($roles);
    }

    private function empresaIdsForUser(): array
    {
        return \App\Support\TenantSelection::empresaIds();
    }

    private function empresaIdsFromPlantas(): array
    {
        return backpack_user()->plantas()->pluck('cliente_id')->unique()->values()->all();
    }
}
