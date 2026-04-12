<?php

namespace App\Http\Controllers\Admin\Traits;

use App\Models\Empleado;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;

trait TenantScope
{
    protected string $scopeMode = 'none';
    protected ?string $scopeRelation = null;
    protected ?string $scopeEmpresaField = 'cliente_id';
    protected ?string $scopePlantaField = 'sucursal_id';
    protected ?string $scopeModelClass = null;

    protected function isAdmin(): bool
    {
        return backpack_user() && backpack_user()->hasRole('Administrador');
    }

    protected function empresaIdsForUser(): array
    {
        return backpack_user()?->empresas()->pluck('clientes.id')->all() ?? [];
    }

    protected function plantaIdsForUser(): array
    {
        return backpack_user()?->plantas()->pluck('sucursals.id')->all() ?? [];
    }

    protected function applyTenantScope($queryOrCrud): void
    {
        if ($this->isAdmin()) {
            return;
        }

        switch ($this->scopeMode) {
            case 'empleado':
                $this->applyScopeByEmpleado($queryOrCrud, $this->scopeRelation ?: 'empleado');
                break;
            case 'relation':
                $this->applyScopeByEmpleado($queryOrCrud, $this->scopeRelation ?: 'empleado');
                break;
            case 'fields':
                $this->applyScopeByFields($queryOrCrud, $this->scopeEmpresaField, $this->scopePlantaField);
                break;
            case 'cedula':
                $this->applyScopeByCedula($queryOrCrud);
                break;
            default:
                break;
        }
    }

    protected function enforceEntryScopeOrFail(int $id): void
    {
        if ($this->isAdmin() || ! $this->scopeModelClass) {
            return;
        }

        $model = $this->scopeModelClass;
        $query = $model::query()->whereKey($id);

        switch ($this->scopeMode) {
            case 'empleado':
            case 'relation':
                $relation = $this->scopeRelation ?: 'empleado';
                $this->applyScopeByEmpleado($query, $relation);
                break;
            case 'fields':
                $this->applyScopeByFields($query, $this->scopeEmpresaField, $this->scopePlantaField);
                break;
            case 'cedula':
                $this->applyScopeByCedula($query);
                break;
            default:
                break;
        }

        if (! $query->exists()) {
            abort(403, 'No autorizado.');
        }
    }

    protected function applyScopeByEmpleado($queryOrCrud, string $relation = 'empleado'): void
    {
        $empresaIds = $this->empresaIdsForUser();
        $plantaIds = $this->plantaIdsForUser();

        $constraint = function ($q) use ($empresaIds, $plantaIds) {
            if (! empty($plantaIds)) {
                $q->whereIn('sucursal_id', $plantaIds);
            } elseif (! empty($empresaIds)) {
                $q->whereIn('cliente_id', $empresaIds);
            } else {
                $q->whereRaw('1=0');
            }
        };

        if ($queryOrCrud instanceof CrudPanel) {
            $queryOrCrud->addClause('whereHas', $relation, $constraint);
        } else {
            $queryOrCrud->whereHas($relation, $constraint);
        }
    }

    protected function applyScopeByFields($queryOrCrud, ?string $empresaField, ?string $plantaField): void
    {
        $empresaIds = $this->empresaIdsForUser();
        $plantaIds = $this->plantaIdsForUser();

        $apply = function ($q) use ($empresaIds, $plantaIds, $empresaField, $plantaField) {
            if (! empty($plantaIds) && $plantaField) {
                $q->whereIn($plantaField, $plantaIds);
            } elseif (! empty($empresaIds) && $empresaField) {
                $q->whereIn($empresaField, $empresaIds);
            } else {
                $q->whereRaw('1=0');
            }
        };

        if ($queryOrCrud instanceof CrudPanel) {
            $queryOrCrud->addClause(function ($q) use ($apply) {
                $apply($q);
            });
        } else {
            $apply($queryOrCrud);
        }
    }

    protected function applyScopeByCedula($queryOrCrud): void
    {
        $empresaIds = $this->empresaIdsForUser();
        $plantaIds = $this->plantaIdsForUser();

        $sub = Empleado::query()->select('cedula');
        if (! empty($plantaIds)) {
            $sub->whereIn('sucursal_id', $plantaIds);
        } elseif (! empty($empresaIds)) {
            $sub->whereIn('cliente_id', $empresaIds);
        } else {
            $sub->whereRaw('1=0');
        }

        if ($queryOrCrud instanceof CrudPanel) {
            $queryOrCrud->addClause('whereIn', 'cedula', $sub);
        } else {
            $queryOrCrud->whereIn('cedula', $sub);
        }
    }

    // Nota: los controladores deben envolver show/edit/update/destroy
    // para aplicar enforceEntryScopeOrFail según las operaciones usadas.
}
