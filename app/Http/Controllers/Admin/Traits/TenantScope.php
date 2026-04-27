<?php

namespace App\Http\Controllers\Admin\Traits;

use App\Models\Empleado;
use App\Support\TenantSelection;
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
        return TenantSelection::empresaIds();
    }

    protected function plantaIdsForUser(): array
    {
        return TenantSelection::plantaIds();
    }

    protected function applyTenantScope($queryOrCrud): void
    {
        if ($this->isAdmin() && TenantSelection::isAdminBypass()) {
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
        if (($this->isAdmin() && TenantSelection::isAdminBypass()) || ! $this->scopeModelClass) {
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
        $includeUnassigned = TenantSelection::selectedEmpresaIncludesUnassigned();

        $constraint = function ($q) use ($empresaIds, $plantaIds, $includeUnassigned) {
            if (! empty($plantaIds)) {
                $q->whereIn('sucursal_id', $plantaIds);
            } elseif (! empty($empresaIds)) {
                if ($includeUnassigned) {
                    $q->where(function ($inner) use ($empresaIds) {
                        $inner->whereIn('cliente_id', $empresaIds)
                            ->orWhereNull('cliente_id')
                            ->orWhere('cliente_id', 0);
                    });
                } else {
                    $q->whereIn('cliente_id', $empresaIds);
                }
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
        $includeUnassigned = TenantSelection::selectedEmpresaIncludesUnassigned();

        $apply = function ($q) use ($empresaIds, $plantaIds, $empresaField, $plantaField, $includeUnassigned) {
            if (! empty($plantaIds) && $plantaField) {
                $q->whereIn($plantaField, $plantaIds);
            } elseif (! empty($empresaIds) && $empresaField) {
                if ($includeUnassigned) {
                    $q->where(function ($inner) use ($empresaIds, $empresaField) {
                        $inner->whereIn($empresaField, $empresaIds)
                            ->orWhereNull($empresaField)
                            ->orWhere($empresaField, 0);
                    });
                } else {
                    $q->whereIn($empresaField, $empresaIds);
                }
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
        $includeUnassigned = TenantSelection::selectedEmpresaIncludesUnassigned();

        $sub = Empleado::query()->select('cedula');
        if (! empty($plantaIds)) {
            $sub->whereIn('sucursal_id', $plantaIds);
        } elseif (! empty($empresaIds)) {
            if ($includeUnassigned) {
                $sub->where(function ($q) use ($empresaIds) {
                    $q->whereIn('cliente_id', $empresaIds)
                        ->orWhereNull('cliente_id')
                        ->orWhere('cliente_id', 0);
                });
            } else {
                $sub->whereIn('cliente_id', $empresaIds);
            }
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
