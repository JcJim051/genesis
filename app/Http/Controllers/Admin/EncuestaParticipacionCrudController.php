<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Exports\EncuestaParticipacionesExport;
use App\Models\EncuestaRespuesta;
use App\Models\Cliente;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Maatwebsite\Excel\Facades\Excel;

class EncuestaParticipacionCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(EncuestaRespuesta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/encuesta-participacion');
        CRUD::setEntityNameStrings('participación', 'participaciones');
        $this->applyListScope();

        $this->scopeMode = 'relation';
        $this->scopeRelation = 'empleado';
        $this->scopeModelClass = EncuestaRespuesta::class;
    }

    protected function setupListOperation(): void
    {
        $this->crud->addClause('with', ['encuesta', 'empleado']);
        $this->applyRequestFilters();
        $this->crud->addButtonFromView('top', 'encuesta_participacion_export', 'encuesta_participacion_export', 'end');

        CRUD::addColumn([
            'name' => 'encuesta',
            'type' => 'closure',
            'label' => 'Encuesta',
            'function' => fn ($entry) => optional($entry->encuesta)->titulo,
        ]);
        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'escaped' => false,
            'function' => fn ($entry) => \App\Support\EmpleadoLink::render($entry->empleado),
        ]);
        CRUD::addColumn([
            'name' => 'cedula',
            'type' => 'closure',
            'label' => 'Cédula',
            'function' => fn ($entry) => optional($entry->empleado)->cedula,
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
        CRUD::addColumn([
            'name' => 'puntaje_total',
            'type' => 'number',
            'label' => 'Puntaje',
        ]);
        CRUD::addColumn([
            'name' => 'fecha_participacion',
            'type' => 'closure',
            'label' => 'Fecha participación',
            'function' => fn ($entry) => optional($entry->respondido_en ?? $entry->created_at)?->format('Y-m-d H:i'),
        ]);

        $this->crud->setListView('admin.encuestas.participaciones_list');
        $this->data['empresas'] = $this->getEmpresas();
        $this->data['plantas'] = $this->getPlantas();
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('admin.encuestas.participacion_show');
    }

    public function show($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitShow($id);
    }

    public function export()
    {
        $query = EncuestaRespuesta::with(['encuesta', 'empleado']);
        $this->applyListScope($query);
        $this->applyExportFilters($query);

        $filename = 'encuestas_participaciones_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new EncuestaParticipacionesExport($query), $filename);
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
        if (! \App\Support\TenantSelection::isAdminBypass()) {
            $empresaIds = \App\Support\TenantSelection::empresaIds();
            $query->whereIn('id', $empresaIds ?: [0]);
        }
        return $query->get();
    }

    private function getPlantas()
    {
        $query = Sucursal::orderBy('nombre');
        if (! \App\Support\TenantSelection::isAdminBypass()) {
            $empresaIds = \App\Support\TenantSelection::empresaIds();
            $query->whereIn('cliente_id', $empresaIds ?: [0]);
        }
        return $query->get();
    }

    private function applyListScope($query = null): void
    {
        $query = $query ?: $this->crud;

        if (! backpack_user()) {
            abort(403);
        }

        if (\App\Support\TenantSelection::isAdminBypass()) {
            return;
        }

        $empresaIds = \App\Support\TenantSelection::empresaIds();
        $plantaIds = \App\Support\TenantSelection::plantaIds();

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
}
