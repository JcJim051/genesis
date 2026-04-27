<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Models\Empleado;
use App\Models\Programa;
use App\Models\ProgramaCaso;
use App\Support\TenantSelection;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class ProgramaCasoCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; edit as traitEdit; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(ProgramaCaso::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/programa-caso');
        CRUD::setEntityNameStrings('caso', 'casos');
        $this->applyAccessRules();

        $this->scopeMode = 'relation';
        $this->scopeRelation = 'empleado';
        $this->scopeModelClass = ProgramaCaso::class;
    }

    protected function setupListOperation(): void
    {
        $this->crud->setListView('admin.programa_casos.list');
        $this->applyListScope();
        $this->crud->query->with('incapacidades');

        $this->setCaseIndicators();

        $programaId = request()->get('programa_id');
        if ($programaId) {
            $programa = Programa::find($programaId);
            if ($programa) {
                $this->crud->setHeading('Casos - ' . $programa->nombre);
                $this->crud->setSubheading('Programa: ' . $programa->nombre, 'list');
            }
        }

        if (request()->get('estado')) {
            $estadoTitulo = request()->get('estado');
            $this->crud->setHeading('Casos - ' . $estadoTitulo);
            $this->crud->setSubheading('Estado: ' . $estadoTitulo, 'list');
        }

        $this->crud->addButtonFromView('line', 'programa_caso_accept', 'programa_caso_accept', 'beginning');
        $this->crud->addButtonFromView('line', 'programa_caso_probable', 'programa_caso_probable', 'beginning');
        $this->crud->addButtonFromView('line', 'programa_caso_reject', 'programa_caso_reject', 'beginning');
        $this->crud->addButtonFromView('line', 'programa_caso_ipt_initial', 'programa_caso_ipt_initial', 'beginning');
        $this->crud->addButtonFromView('line', 'programa_caso_retirar', 'programa_caso_retirar', 'end');

        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'escaped' => false,
            'function' => fn ($entry) => \App\Support\EmpleadoLink::render($entry->empleado),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('empleado', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', '%' . $searchTerm . '%')
                        ->orWhere('cedula', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);
        CRUD::addColumn([
            'name' => 'programa',
            'type' => 'closure',
            'label' => 'Programa',
            'function' => fn ($entry) => optional($entry->programa)->nombre,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('programa', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);
        CRUD::addColumn([
            'name' => 'incapacidades_origen',
            'type' => 'closure',
            'label' => 'Incapacidades origen',
            'function' => function ($entry) {
                $items = $entry->incapacidades()->orderByDesc('fecha_inicio')->take(3)->get();
                if ($items->isEmpty()) {
                    return '-';
                }
                $lines = $items->map(function ($inc) {
                    $inicio = $inc->fecha_inicio?->format('Y-m-d') ?? '';
                    $fin = $inc->fecha_fin?->format('Y-m-d') ?? '';
                    $codigo = $inc->codigo_cie10 ?? '';
                    $diag = $inc->diagnostico ?? '';
                    return "{$inicio} → {$fin} | {$codigo} {$diag}";
                })->all();
                return implode('<br>', $lines);
            },
            'escaped' => false,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('incapacidades', function ($q) use ($searchTerm) {
                    $q->where('codigo_cie10', 'like', '%' . $searchTerm . '%')
                        ->orWhere('diagnostico', 'like', '%' . $searchTerm . '%')
                        ->orWhere('cedula', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);
        CRUD::addColumn([
            'name' => 'fuente_alerta',
            'type' => 'closure',
            'label' => 'Fuente alerta',
            'priority' => 1,
            'visibleInTable' => true,
            'function' => function ($entry) {
                $origen = strtolower((string) ($entry->origen ?? ''));
                $sugerido = strtolower((string) ($entry->sugerido_por ?? ''));
                if (str_contains($origen, 'encuesta') || str_contains($sugerido, 'encuesta')) {
                    return 'Encuesta';
                }
                if (str_contains($origen, 'incapacidad') || str_contains($sugerido, 'incapacidad')) {
                    return 'Incapacidad';
                }
                if (str_contains($origen, 'examen') || str_contains($sugerido, 'examen')) {
                    return 'Examen periódico';
                }
                if (str_contains($origen, 'cie10') || str_contains($sugerido, 'cie10')) {
                    return 'CIE10';
                }
                if ($origen !== '') {
                    return ucfirst($origen);
                }
                return 'Manual';
            },
        ]);
        if (request()->get('estado') !== 'No evaluado') {
            CRUD::addColumn([
                'name' => 'estado_badge',
                'type' => 'closure',
                'label' => 'Estado',
                'escaped' => false,
                'visibleInTable' => true,
                'priority' => 1,
                'function' => function ($entry) {
                    $estado = (string) ($entry->estado ?? '');
                    $class = 'secondary';
                    $text = $estado ?: '-';
                    if ($estado === 'Confirmado') {
                        $class = 'success';
                    } elseif ($estado === 'Probable') {
                        $class = 'warning';
                    } elseif ($estado === 'No caso') {
                        $class = 'danger';
                    } elseif ($estado === 'No evaluado') {
                        $class = 'info';
                    }
                    return '<span class="badge bg-' . $class . '">' . e($text) . '</span>';
                },
            ]);
        }
        CRUD::column('origen');
        CRUD::column('sugerido_por');
    }

    private function setCaseIndicators(): void
    {
        $programaId = request()->get('programa_id');

        $baseQuery = ProgramaCaso::query();
        if ($programaId) {
            $baseQuery->where('programa_id', $programaId);
        }

        $empleadosQuery = Empleado::query()->whereNull('fecha_retiro');

        if (! \App\Support\TenantSelection::isAdminBypass()) {
            if (TenantSelection::isPlatformAdmin()) {
                $empleadoIds = $this->scopedEmpleadoIds();
                $baseQuery->whereIn('empleado_id', $empleadoIds->isEmpty() ? [0] : $empleadoIds);
                $empleadosQuery->whereIn('id', $empleadoIds->isEmpty() ? [0] : $empleadoIds);
            } elseif (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
                $empresaIds = TenantSelection::empresaIds();
                $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
                $baseQuery->whereIn('empleado_id', $empleadoIds);
                $empleadosQuery->whereIn('cliente_id', $empresaIds ?: [0]);
            } elseif (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
                $plantaIds = TenantSelection::plantaIds();
                $empleadoIds = Empleado::whereIn('sucursal_id', $plantaIds ?: [0])->pluck('id');
                $baseQuery->whereIn('empleado_id', $empleadoIds);
                $empleadosQuery->whereIn('sucursal_id', $plantaIds ?: [0]);
            } else {
                $baseQuery->whereRaw('1 = 0');
                $empleadosQuery->whereRaw('1 = 0');
            }
        }

        $confirmados = (clone $baseQuery)->where('estado', 'Confirmado')->count();
        $probables = (clone $baseQuery)->where('estado', 'Probable')->count();
        $activos = $empleadosQuery->count();

        $this->crud->setOperationSetting('case_indicators', [
            'confirmados' => $confirmados,
            'probables' => $probables,
            'activos' => $activos,
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('empleado_id')
            ->type('select')
            ->label('Persona')
            ->entity('empleado')
            ->model(Empleado::class)
            ->attribute('nombre');

        CRUD::field('programa_id')
            ->type('select')
            ->label('Programa')
            ->entity('programa')
            ->model(Programa::class)
            ->attribute('nombre');

        CRUD::field('estado')->type('select_from_array')->options([
            'No caso' => 'No caso',
            'Probable' => 'Probable',
            'Confirmado' => 'Confirmado',
        ])->allows_null(false);

        CRUD::field('origen')->type('text');
        CRUD::field('sugerido_por')->type('text')->label('Sugerido por');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $entryId = $this->crud->getCurrentEntryId();
        $entry = $entryId ? ProgramaCaso::find($entryId) : null;
        if ($entry) {
            $this->authorizeDecision($entry);
        }
        if ($entry && $entry->estado !== 'No evaluado') {
            $this->crud->setShowView('admin.programa_casos.show_programa');
        } else {
            $this->crud->setShowView('admin.programa_casos.show');
        }
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

    public function update()
    {
        $this->enforceEntryScopeOrFail((int) $this->crud->getCurrentEntryId());
        return $this->traitUpdate();
    }

    public function destroy($id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        return $this->traitDestroy($id);
    }

    public function accept(Request $request, $id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        $data = $request->validate([
            'observacion' => 'required|string|min:5|max:1000',
            'return_url' => 'nullable|string|max:2000',
        ]);
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'Confirmado', $data['observacion']);
        return $this->redirectAfterDecision($request, backpack_url('programa-caso') . '?estado=No%20evaluado');
    }

    public function probable(Request $request, $id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        $data = $request->validate([
            'observacion' => 'required|string|min:5|max:1000',
            'return_url' => 'nullable|string|max:2000',
        ]);
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'Probable', $data['observacion']);
        return $this->redirectAfterDecision($request, backpack_url('programa-caso') . '?estado=No%20evaluado');
    }

    public function reject(Request $request, $id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        $data = $request->validate([
            'observacion' => 'required|string|min:5|max:1000',
            'return_url' => 'nullable|string|max:2000',
        ]);
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'No caso', $data['observacion']);
        return $this->redirectAfterDecision($request, backpack_url('programa-caso') . '?estado=No%20evaluado');
    }

    public function retirar(Request $request, $id)
    {
        $this->enforceEntryScopeOrFail((int) $id);
        $data = $request->validate([
            'observacion' => 'required|string|min:5|max:1000',
            'return_url' => 'nullable|string|max:2000',
        ]);
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'No caso', $data['observacion']);
        return $this->redirectAfterDecision($request, backpack_url('programa-caso'));
    }

    private function updateEstado(ProgramaCaso $entry, string $nuevoEstado, ?string $observacion = null): void
    {
        $estadoAnterior = $entry->estado;
        if ($estadoAnterior === $nuevoEstado) {
            return;
        }

        // Fechas de permanencia
        if (in_array($nuevoEstado, ['Probable', 'Confirmado'], true)) {
            if (! $entry->fecha_inicio) {
                $entry->fecha_inicio = now()->format('Y-m-d');
            }
            $entry->fecha_fin = null;
        }

        if ($nuevoEstado === 'No caso' && in_array($estadoAnterior, ['Probable', 'Confirmado'], true)) {
            $entry->fecha_fin = now()->format('Y-m-d');
        }

        $entry->estado = $nuevoEstado;
        $entry->save();

        \App\Models\ProgramaCasoHistorial::create([
            'programa_caso_id' => $entry->id,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $nuevoEstado,
            'observacion' => $observacion ? trim($observacion) : null,
            'user_id' => backpack_user()?->id,
        ]);
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (\App\Support\TenantSelection::isPlatformAdmin()) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $this->crud->denyAccess(['delete']);
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $this->crud->denyAccess(['delete']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function authorizeDecision(ProgramaCaso $entry): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (\App\Support\TenantSelection::isAdminBypass()) {
            return;
        }

        if (TenantSelection::isPlatformAdmin()) {
            $empleadoIds = $this->scopedEmpleadoIds();
            if (! $empleadoIds->contains($entry->empleado_id)) {
                abort(403);
            }
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = TenantSelection::empresaIds();
            $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
            if (! $empleadoIds->contains($entry->empleado_id)) {
                abort(403);
            }
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = \App\Support\TenantSelection::plantaIds();
            $empleadoIds = Empleado::whereIn('sucursal_id', $plantaIds ?: [0])->pluck('id');
            if (! $empleadoIds->contains($entry->empleado_id)) {
                abort(403);
            }
            return;
        }

        abort(403);
    }

    private function applyListScope(): void
    {
        $programaId = request()->get('programa_id');
        if ($programaId) {
            $this->crud->addClause('where', 'programa_id', $programaId);
        }

        $estado = request()->get('estado');
        if ($estado) {
            $this->crud->addClause('where', 'estado', $estado);
        } else {
            if (request()->get('programa_id')) {
                $this->crud->addClause('whereIn', 'estado', ['Probable', 'Confirmado']);
            } else {
                $this->crud->addClause('whereIn', 'estado', ['No evaluado', 'Probable', 'Confirmado']);
            }
        }

        if (\App\Support\TenantSelection::isAdminBypass()) {
            return;
        }

        if (TenantSelection::isPlatformAdmin()) {
            $empleadoIds = $this->scopedEmpleadoIds();
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds->isEmpty() ? [0] : $empleadoIds);
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = TenantSelection::empresaIds();
            $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds);
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = TenantSelection::plantaIds();
            $empleadoIds = Empleado::whereIn('sucursal_id', $plantaIds ?: [0])->pluck('id');
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }

    private function scopedEmpleadoIds()
    {
        $plantaIds = TenantSelection::plantaIds();
        if (! empty($plantaIds)) {
            return Empleado::whereIn('sucursal_id', $plantaIds)->pluck('id');
        }

        $empresaIds = TenantSelection::empresaIds();
        if (empty($empresaIds)) {
            return collect();
        }

        if (TenantSelection::selectedEmpresaIncludesUnassigned()) {
            return Empleado::where(function ($q) use ($empresaIds) {
                $q->whereIn('cliente_id', $empresaIds)
                    ->orWhereNull('cliente_id')
                    ->orWhere('cliente_id', 0);
            })->pluck('id');
        }

        return Empleado::whereIn('cliente_id', $empresaIds)->pluck('id');
    }

    private function redirectAfterDecision(Request $request, string $fallbackUrl)
    {
        $returnUrl = trim((string) $request->input('return_url', ''));
        if ($returnUrl === '') {
            $returnUrl = (string) url()->previous();
        }

        $isRelative = str_starts_with($returnUrl, '/');
        $isLocalAbsolute = str_starts_with($returnUrl, rtrim(url('/'), '/'));

        if ($isRelative || $isLocalAbsolute) {
            return redirect()->to($returnUrl);
        }

        return redirect()->to($fallbackUrl);
    }
}
