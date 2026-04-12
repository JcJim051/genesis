<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Exports\PausaParticipacionesExport;
use App\Models\Cliente;
use App\Models\PausaParticipacion;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Maatwebsite\Excel\Facades\Excel;

class PausaParticipacionCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(PausaParticipacion::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pausa-participacion');
        CRUD::setEntityNameStrings('participación', 'participaciones');
        $this->applyListScope();

        $this->scopeMode = 'relation';
        $this->scopeRelation = 'empleado';
        $this->scopeModelClass = PausaParticipacion::class;
    }

    protected function setupListOperation(): void
    {
        $this->crud->addClause('with', ['envio.pausa', 'empleado']);
        $this->applyRequestFilters();
        $this->crud->addButtonFromView('top', 'pausa_participacion_export', 'pausa_participacion_export', 'end');

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
            'name' => 'correo',
            'type' => 'closure',
            'label' => 'Correo',
            'function' => fn ($entry) => optional($entry->empleado)->correo_electronico,
        ]);
        CRUD::addColumn([
            'name' => 'telegram',
            'type' => 'closure',
            'label' => 'Telegram',
            'function' => fn ($entry) => optional($entry->empleado)->telegram_chat_id,
        ]);
        CRUD::addColumn([
            'name' => 'cedula',
            'type' => 'closure',
            'label' => 'Cédula',
            'function' => fn ($entry) => optional($entry->empleado)->cedula,
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
        CRUD::addColumn([
            'name' => 'fecha_participacion',
            'type' => 'closure',
            'label' => 'Fecha participación',
            'function' => fn ($entry) => optional($entry->respondido_en ?? $entry->created_at)?->format('Y-m-d H:i'),
        ]);

        $this->crud->setListView('admin.pausas.participaciones_list');
        $this->data['empresas'] = $this->getEmpresas();
        $this->data['plantas'] = $this->getPlantas();
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('admin.pausas.participacion_show');
    }

    public function show($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitShow($id);
    }

    public function export()
    {
        $query = PausaParticipacion::with(['envio.pausa', 'empleado']);
        $this->applyListScope($query);
        $this->applyExportFilters($query);

        $filename = 'pausas_participaciones_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new PausaParticipacionesExport($query), $filename);
    }

    private function applyListScope($query = null): void
    {
        $query = $query ?: $this->crud;

        if (! backpack_user()) {
            abort(403);
        }

        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
        $plantaIds = backpack_user()->plantas()->pluck('sucursals.id')->all();

        $constraint = function ($q) use ($empresaIds, $plantaIds) {
            if (! empty($plantaIds)) {
                $q->whereIn('sucursal_id', $plantaIds);
            } elseif (! empty($empresaIds)) {
                $q->whereIn('cliente_id', $empresaIds);
            } else {
                $q->whereRaw('1=0');
            }
        };

        if ($query instanceof \Backpack\CRUD\app\Library\CrudPanel\CrudPanel) {
            $query->addClause('whereHas', 'empleado', $constraint);
        } else {
            $query->whereHas('empleado', $constraint);
        }
    }

    private function applyRequestFilters(): void
    {
        $clienteId = request('cliente_id');
        $sucursalId = request('sucursal_id');

        if ($clienteId) {
            $this->crud->addClause('whereHas', 'empleado', function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId);
            });
        }
        if ($sucursalId) {
            $this->crud->addClause('whereHas', 'empleado', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            });
        }
    }

    private function applyExportFilters($query): void
    {
        $clienteId = request('cliente_id');
        $sucursalId = request('sucursal_id');

        if ($clienteId) {
            $query->whereHas('empleado', function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId);
            });
        }
        if ($sucursalId) {
            $query->whereHas('empleado', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            });
        }
    }

    private function getEmpresas()
    {
        $query = Cliente::orderBy('nombre');
        if (! backpack_user()->hasRole('Administrador')) {
            $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
            $query->whereIn('id', $empresaIds ?: [0]);
        }
        return $query->get();
    }

    private function getPlantas()
    {
        $query = Sucursal::orderBy('nombre');
        if (! backpack_user()->hasRole('Administrador')) {
            $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
            $query->whereIn('cliente_id', $empresaIds ?: [0]);
        }
        return $query->get();
    }
}
