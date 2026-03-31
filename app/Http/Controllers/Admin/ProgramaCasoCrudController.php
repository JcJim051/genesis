<?php

namespace App\Http\Controllers\Admin;

use App\Models\Empleado;
use App\Models\Programa;
use App\Models\ProgramaCaso;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProgramaCasoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(ProgramaCaso::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/programa-caso');
        CRUD::setEntityNameStrings('caso', 'casos');
        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
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
        $this->crud->addButtonFromView('line', 'programa_caso_retirar', 'programa_caso_retirar', 'end');

        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'function' => fn ($entry) => optional($entry->empleado)->nombre,
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

        if (! backpack_user()->hasRole('Administrador')) {
            if (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
                $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
                $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
                $baseQuery->whereIn('empleado_id', $empleadoIds);
                $empleadosQuery->whereIn('cliente_id', $empresaIds ?: [0]);
            } elseif (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
                $plantaIds = backpack_user()->plantas()->pluck('sucursals.id')->all();
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

    public function accept($id)
    {
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'Confirmado');
        return redirect()->to(backpack_url('programa-caso') . '?estado=No%20evaluado');
    }

    public function probable($id)
    {
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'Probable');
        return redirect()->to(backpack_url('programa-caso') . '?estado=No%20evaluado');
    }

    public function reject($id)
    {
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'No caso');
        return redirect()->to(backpack_url('programa-caso') . '?estado=No%20evaluado');
    }

    public function retirar($id)
    {
        $entry = ProgramaCaso::findOrFail($id);
        $this->authorizeDecision($entry);
        $this->updateEstado($entry, 'No caso');
        return redirect()->back();
    }

    private function updateEstado(ProgramaCaso $entry, string $nuevoEstado): void
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
            'user_id' => backpack_user()?->id,
        ]);
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (backpack_user()->hasRole('Administrador')) {
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

        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
            $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
            if (! $empleadoIds->contains($entry->empleado_id)) {
                abort(403);
            }
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = backpack_user()->plantas()->pluck('sucursals.id')->all();
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

        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
            $empleadoIds = Empleado::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id');
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds);
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = backpack_user()->plantas()->pluck('sucursals.id')->all();
            $empleadoIds = Empleado::whereIn('sucursal_id', $plantaIds ?: [0])->pluck('id');
            $this->crud->addClause('whereIn', 'empleado_id', $empleadoIds);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }
}
