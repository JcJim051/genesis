<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Http\Requests\EmpleadoAreaRequest;
use App\Models\EmpleadoArea;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EmpleadoAreaCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; edit as traitEdit; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(EmpleadoArea::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/empleado-area');
        CRUD::setEntityNameStrings('area', 'areas');

        $this->scopeMode = 'empleado';
        $this->scopeRelation = 'empleado';
        $this->scopeModelClass = EmpleadoArea::class;
        $this->applyTenantScope($this->crud);
    }

    protected function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'function' => function ($entry) {
                return optional($entry->empleado)->nombre;
            },
        ]);
        CRUD::column('area');
        CRUD::column('fecha_inicio');
        CRUD::column('fecha_fin');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(EmpleadoAreaRequest::class);

        CRUD::field('empleado_id')
            ->type('select')
            ->label('Persona')
            ->entity('empleado')
            ->model(\App\Models\Empleado::class)
            ->attribute('nombre');

        CRUD::field('area')->type('text')->label('Área');
        CRUD::field('fecha_inicio')->type('date')->label('Fecha inicio');
        CRUD::field('fecha_fin')->type('date')->label('Fecha fin');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $request = $this->crud->getRequest();
        $empleadoId = $request->input('empleado_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        if ($empleadoId && $fechaInicio) {
            $nuevaFechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFinAnterior = $nuevaFechaInicio->copy()->subDay()->toDateString();

            $prev = EmpleadoArea::query()
                ->where('empleado_id', $empleadoId)
                ->where('fecha_inicio', '<', $nuevaFechaInicio->toDateString())
                ->where(function ($q) use ($nuevaFechaInicio) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', $nuevaFechaInicio->toDateString());
                })
                ->orderByDesc('fecha_inicio')
                ->first();

            $ignoreId = null;
            if ($prev) {
                $prev->update(['fecha_fin' => $fechaFinAnterior]);
                $ignoreId = $prev->id;
            }

            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $ignoreId);
        } else {
            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, null);
        }

        return $this->traitStore();
    }

    public function update()
    {
        $this->enforceEntryScopeOrFail((int) $this->crud->getCurrentEntryId());
        $request = $this->crud->getRequest();
        $empleadoId = $request->input('empleado_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $entryId = $this->crud->getCurrentEntryId();

        if ($empleadoId && $fechaInicio) {
            $nuevaFechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFinAnterior = $nuevaFechaInicio->copy()->subDay()->toDateString();

            $prev = EmpleadoArea::query()
                ->where('empleado_id', $empleadoId)
                ->where('id', '!=', $entryId)
                ->where('fecha_inicio', '<', $nuevaFechaInicio->toDateString())
                ->where(function ($q) use ($nuevaFechaInicio) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', $nuevaFechaInicio->toDateString());
                })
                ->orderByDesc('fecha_inicio')
                ->first();

            $ignoreId = null;
            if ($prev) {
                $prev->update(['fecha_fin' => $fechaFinAnterior]);
                $ignoreId = $prev->id;
            }

            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $ignoreId ? [$entryId, $ignoreId] : $entryId);
        } else {
            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $entryId);
        }

        return $this->traitUpdate();
    }

    public function show($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitShow($id);
    }

    public function edit($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitEdit($id);
    }

    public function destroy($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitDestroy($id);
    }

    private function validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $ignoreId): void
    {
        if (! $empleadoId || ! $fechaInicio) {
            return;
        }

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        if ($fin && $fin->lt($inicio)) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha fin no puede ser anterior a la fecha inicio.',
            ]);
        }

        $endDate = $fin ? $fin->toDateString() : '9999-12-31';

        $overlapQuery = EmpleadoArea::query()
            ->where('empleado_id', $empleadoId)
            ->when($ignoreId, function ($q) use ($ignoreId) {
                if (is_array($ignoreId)) {
                    $q->whereNotIn('id', $ignoreId);
                } else {
                    $q->where('id', '!=', $ignoreId);
                }
            })
            ->where('fecha_inicio', '<=', $endDate)
            ->where(function ($q) use ($inicio) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $inicio->toDateString());
            });

        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'El rango de fechas se cruza con otra área existente para esta persona.',
            ]);
        }
    }
}
