<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SucursalRequest;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SucursalCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Sucursal::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sucursal');
        CRUD::setEntityNameStrings('planta', 'plantas');

        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();

        CRUD::addColumn([
            'name' => 'cliente_nombre',
            'type' => 'model_function',
            'label' => 'Empresa',
            'function_name' => 'getClienteNombre',
        ]);

        CRUD::addColumn([
            'name' => 'nombre',
            'type' => 'text',
            'label' => 'Planta',
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(SucursalRequest::class);
        $this->enforceEntryScope();

        CRUD::field('cliente_id')
            ->type('select')
            ->label('Empresa')
            ->entity('cliente')
            ->model(\App\Models\Cliente::class)
            ->attribute('nombre')
            ->options(function ($query) {
                if ($this->isAdmin()) {
                    return $query->orderBy('nombre')->get();
                }

                $allowed = $this->allowedEmpresaIdsForCreate();
                return $query->whereIn('id', $allowed ?: [0])->orderBy('nombre')->get();
            });

        CRUD::field('nombre')->type('text')->label('Planta');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->enforceEntryScope();
    }

    public function store()
    {
        $this->ensureEmpresaWithinScope();
        $response = $this->traitStore();

        if (! $this->isAdmin() && $this->crud->entry) {
            backpack_user()->plantas()->syncWithoutDetaching([$this->crud->entry->id]);
        }

        return $response;
    }

    public function update()
    {
        $this->ensureEmpresaWithinScope();
        return $this->traitUpdate();
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

        if ($this->hasAnyRole(['Asesor externo general', 'Coordinador de planta', 'Asesor externo planta'])) {
            $this->crud->denyAccess(['delete']);
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
            $this->crud->addClause('whereIn', 'cliente_id', $empresaIds ?: [0]);
            return;
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = $this->plantaIdsForUser();
            $this->crud->addClause('whereIn', 'id', $plantaIds ?: [0]);
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

        $query = Sucursal::query()->whereKey($entryId);

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            $query->whereIn('cliente_id', $empresaIds ?: [0]);
        } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = $this->plantaIdsForUser();
            $query->whereIn('id', $plantaIds ?: [0]);
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

    private function plantaIdsForUser(): array
    {
        return \App\Support\TenantSelection::plantaIds();
    }

    private function allowedEmpresaIdsForCreate(): array
    {
        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            return $this->empresaIdsForUser();
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            return backpack_user()->plantas()->pluck('cliente_id')->unique()->values()->all();
        }

        return [];
    }

    private function ensureEmpresaWithinScope(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $empresaId = (int) request()->input('cliente_id');
        $allowed = $this->allowedEmpresaIdsForCreate();

        if (! in_array($empresaId, $allowed, true)) {
            abort(403);
        }
    }
}
