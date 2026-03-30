<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\EmpleadoRequest;
use App\Models\Empleado;
use App\Models\EmpleadoArea;
use App\Models\EmpleadoCargo;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;

class EmpleadoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Empleado::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/empleado');
        CRUD::setEntityNameStrings('persona', 'personas');

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
            'name' => 'sucursal_nombre',
            'type' => 'model_function',
            'label' => 'Planta',
            'function_name' => 'getSucursalNombre',
        ]);
        CRUD::column('nombre')->label('Nombre');
        CRUD::column('cedula')->label('Cédula');
        CRUD::addColumn([
            'name' => 'cargo_actual',
            'type' => 'model_function',
            'label' => 'Cargo Actual',
            'function_name' => 'getCargoActual',
        ]);
        CRUD::addColumn([
            'name' => 'antiguedad_cargo_actual',
            'type' => 'model_function',
            'label' => 'Antigüedad Cargo',
            'function_name' => 'getAntiguedadCargoActual',
        ]);
        CRUD::addColumn([
            'name' => 'area_actual',
            'type' => 'model_function',
            'label' => 'Área Actual',
            'function_name' => 'getAreaActual',
        ]);
        CRUD::column('telefono')->label('Teléfono');
        CRUD::column('correo_electronico')->label('Correo');
        CRUD::column('tipo_contrato')->label('Contrato');
        CRUD::column('genero')->label('Género');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(EmpleadoRequest::class);
        $this->enforceEntryScope();

        CRUD::field('cliente_id')
            ->type('select')
            ->label('Empresa')
            ->entity('cliente')
            ->model(\App\Models\Cliente::class)
            ->attribute('nombre')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->options(function ($query) {
                if ($this->isAdmin()) {
                    return $query->orderBy('nombre')->get();
                }

                $allowed = $this->allowedEmpresaIdsForCreate();
                return $query->whereIn('id', $allowed ?: [0])->orderBy('nombre')->get();
            });

        CRUD::field('sucursal_id')
            ->type('select')
            ->label('Planta')
            ->entity('sucursal')
            ->model(\App\Models\Sucursal::class)
            ->attribute('nombre')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->options(function ($query) {
                $allowed = $this->allowedPlantaIdsForCreate();
                return $query->whereIn('id', $allowed ?: [0])->orderBy('nombre')->get();
            });

        CRUD::field('nombre')->type('text')->label('Nombre')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('cedula')->type('text')->label('Cédula')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('eps')->type('text')->label('EPS')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('arl')->type('text')->label('ARL')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fondo_pensiones')->type('text')->label('Fondo de pensiones')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('cargo')->type('text')->label('Cargo')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('telefono')->type('text')->label('Teléfono')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('direccion')->type('text')->label('Dirección')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('correo_electronico')->type('email')->label('Correo')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('tipo_contrato')->type('text')->label('Tipo de contrato')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('edad')->type('number')->label('Edad')->attributes(['min' => 0])->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('lateralidad')->type('select_from_array')->label('Lateralidad')->options([
            'Derecha' => 'Derecha',
            'Izquierda' => 'Izquierda',
            'Ambidiestro' => 'Ambidiestro',
        ])->allows_null(true)->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('genero')->type('select_from_array')->label('Género')->options([
            'Masculino' => 'Masculino',
            'Femenino' => 'Femenino',
            'Otro' => 'Otro',
        ])->allows_null(true)->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fecha_nacimiento')->type('date')->label('Fecha de nacimiento')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fecha_ingreso')->type('date')->label('Fecha de ingreso')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fecha_retiro')->type('date')->label('Fecha de retiro')->wrapper(['class' => 'form-group col-md-4']);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->enforceEntryScope();
        $this->crud->setShowView('admin.empleados.show');

        CRUD::addColumn([
            'name' => 'cargos_historial',
            'type' => 'model_function',
            'label' => 'Historial de cargos',
            'function_name' => 'getCargosHistorial',
            'escaped' => false,
        ]);
        CRUD::addColumn([
            'name' => 'areas_historial',
            'type' => 'model_function',
            'label' => 'Historial de áreas',
            'function_name' => 'getAreasHistorial',
            'escaped' => false,
        ]);
    }

    public function lookup()
    {
        if (! backpack_user()) {
            abort(403);
        }

        $id = request()->get('id');
        $cedula = request()->get('cedula');
        $nombre = request()->get('nombre');

        if (! $id && ! $cedula && ! $nombre) {
            return response()->json(['message' => 'Debe enviar id, cédula o nombre.'], 422);
        }

        $query = Empleado::query();

        if (! $this->isAdmin()) {
            if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
                $empresaIds = $this->empresaIdsForUser();
                $query->whereIn('cliente_id', $empresaIds ?: [0]);
            } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
                $plantaIds = $this->plantaIdsForUser();
                $query->whereIn('sucursal_id', $plantaIds ?: [0]);
            } else {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        }

        if ($id) {
            $query->whereKey($id);
        } elseif ($cedula) {
            $query->where('cedula', $cedula);
        } else {
            $query->where('nombre', 'like', '%' . $nombre . '%')->orderBy('nombre');
        }

        $empleado = $query->first();
        if (! $empleado) {
            return response()->json(['message' => 'Persona no encontrada.'], 404);
        }

        return response()->json([
            'id' => $empleado->id,
            'nombre' => $empleado->nombre,
            'cedula' => $empleado->cedula,
            'fecha_nacimiento' => optional($empleado->fecha_nacimiento)->format('Y-m-d'),
            'edad' => $empleado->edad,
            'genero' => $empleado->genero,
            'lateralidad' => $empleado->lateralidad,
            'eps' => $empleado->eps,
            'arl' => $empleado->arl,
            'fondo_pensiones' => $empleado->fondo_pensiones,
            'telefono' => $empleado->telefono,
            'correo_electronico' => $empleado->correo_electronico,
            'direccion' => $empleado->direccion,
            'fecha_ingreso' => optional($empleado->fecha_ingreso)->format('Y-m-d'),
            'cargo_actual' => $empleado->getCargoActual(),
            'antiguedad_cargo' => $empleado->getAntiguedadCargoActual(),
            'area_actual' => $empleado->getAreaActual(),
        ]);
    }

    public function store()
    {
        $this->ensureEmpresaPlantaWithinScope();
        $response = $this->traitStore();
        $this->syncRetiroToHistories($this->crud->entry?->id, $this->crud->entry?->fecha_retiro);
        return $response;
    }

    public function update()
    {
        $this->ensureEmpresaPlantaWithinScope();
        $response = $this->traitUpdate();
        $this->syncRetiroToHistories($this->crud->entry?->id, $this->crud->entry?->fecha_retiro);
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
            $this->crud->addClause('whereIn', 'sucursal_id', $plantaIds ?: [0]);
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

        $query = Empleado::query()->whereKey($entryId);

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            $query->whereIn('cliente_id', $empresaIds ?: [0]);
        } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = $this->plantaIdsForUser();
            $query->whereIn('sucursal_id', $plantaIds ?: [0]);
        }

        if (! $query->exists()) {
            abort(403);
        }
    }

    private function isAdmin(): bool
    {
        return backpack_user()->hasRole('Administrador');
    }

    private function hasAnyRole(array $roles): bool
    {
        return backpack_user()->hasAnyRole($roles);
    }

    private function empresaIdsForUser(): array
    {
        return backpack_user()->empresas()->pluck('clientes.id')->all();
    }

    private function plantaIdsForUser(): array
    {
        return backpack_user()->plantas()->pluck('sucursals.id')->all();
    }

    private function allowedEmpresaIdsForCreate(): array
    {
        if ($this->isAdmin()) {
            return \App\Models\Cliente::pluck('id')->all();
        }

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            return $this->empresaIdsForUser();
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            return backpack_user()->plantas()->pluck('cliente_id')->unique()->values()->all();
        }

        return [];
    }

    private function allowedPlantaIdsForCreate(): array
    {
        if ($this->isAdmin()) {
            return \App\Models\Sucursal::pluck('id')->all();
        }

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            return \App\Models\Sucursal::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id')->all();
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            return $this->plantaIdsForUser();
        }

        return [];
    }

    private function ensureEmpresaPlantaWithinScope(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $empresaId = (int) request()->input('cliente_id');
        $plantaId = (int) request()->input('sucursal_id');

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $allowedEmpresas = $this->empresaIdsForUser();
            if (! in_array($empresaId, $allowedEmpresas, true)) {
                abort(403);
            }

            if (! \App\Models\Sucursal::where('id', $plantaId)->where('cliente_id', $empresaId)->exists()) {
                abort(403);
            }

            return;
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $allowedPlantas = $this->plantaIdsForUser();
            if (! in_array($plantaId, $allowedPlantas, true)) {
                abort(403);
            }

            return;
        }

        abort(403);
    }

    private function syncRetiroToHistories(?int $empleadoId, $fechaRetiro): void
    {
        if (! $empleadoId || ! $fechaRetiro) {
            return;
        }

        $retiro = Carbon::parse($fechaRetiro)->toDateString();

        EmpleadoCargo::query()
            ->where('empleado_id', $empleadoId)
            ->where('fecha_inicio', '<=', $retiro)
            ->where(function ($q) use ($retiro) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>', $retiro);
            })
            ->update(['fecha_fin' => $retiro]);

        EmpleadoArea::query()
            ->where('empleado_id', $empleadoId)
            ->where('fecha_inicio', '<=', $retiro)
            ->where(function ($q) use ($retiro) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>', $retiro);
            })
            ->update(['fecha_fin' => $retiro]);
    }
}
