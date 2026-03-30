<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Encuesta;
use App\Models\EncuestaEnvio;
use App\Models\EncuestaRespuesta;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Str;

class EncuestaEnvioCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(EncuestaEnvio::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/encuesta-envio');
        CRUD::setEntityNameStrings('envío', 'envíos');
        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();

        $this->crud->addClause('with', ['respuestas']);
        $this->crud->addButtonFromView('line', 'encuesta_envio_procesar', 'encuesta_envio_procesar', 'end');

        CRUD::addColumn([
            'name' => 'encuesta',
            'type' => 'closure',
            'label' => 'Encuesta',
            'function' => fn ($entry) => optional($entry->encuesta)->titulo,
        ]);
        CRUD::addColumn([
            'name' => 'cliente',
            'type' => 'closure',
            'label' => 'Empresa',
            'function' => fn ($entry) => optional($entry->cliente)->nombre,
        ]);
        CRUD::addColumn([
            'name' => 'sucursal',
            'type' => 'closure',
            'label' => 'Planta',
            'function' => fn ($entry) => optional($entry->sucursal)->nombre,
        ]);
        CRUD::column('fecha_envio');
        CRUD::column('fecha_expiracion');
        CRUD::addColumn([
            'name' => 'links',
            'type' => 'closure',
            'label' => 'Links',
            'escaped' => false,
            'function' => function ($entry) {
                $respuestas = $entry->respuestas ?? collect();
                $total = $respuestas->count();
                if ($total === 0) {
                    return '<span class="text-muted">Sin respuestas</span>';
                }
                $links = $respuestas->take(3)->map(function ($resp) {
                    $url = url('/encuestas/' . $resp->token);
                    return '<a href="' . e($url) . '" target="_blank">Link</a>';
                })->implode(' | ');
                $extra = $total - min($total, 3);
                return $links . ($extra > 0 ? ' <span class="text-muted">(+'.$extra.' más)</span>' : '');
            },
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('encuesta_id')
            ->type('select')
            ->label('Encuesta')
            ->entity('encuesta')
            ->model(Encuesta::class)
            ->attribute('titulo');

        CRUD::field('cliente_id')
            ->type('select')
            ->label('Empresa')
            ->entity('cliente')
            ->model(Cliente::class)
            ->attribute('nombre')
            ->options(function ($query) {
                if (backpack_user()->hasRole('Administrador')) {
                    return $query->orderBy('nombre')->get();
                }

                $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
                return $query->whereIn('id', $empresaIds ?: [0])->orderBy('nombre')->get();
            });

        CRUD::field('sucursal_id')
            ->type('select')
            ->label('Planta (opcional)')
            ->entity('sucursal')
            ->model(Sucursal::class)
            ->attribute('nombre')
            ->options(function ($query) {
                if (backpack_user()->hasRole('Administrador')) {
                    return $query->orderBy('nombre')->get();
                }

                $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
                return $query->whereIn('cliente_id', $empresaIds ?: [0])->orderBy('nombre')->get();
            });

        CRUD::field('fecha_envio')->type('date')->label('Fecha envío');
        CRUD::field('fecha_expiracion')->type('date')->label('Fecha expiración');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $response = $this->traitStore();
        $this->crearRespuestasSiCorresponde();
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->crearRespuestasSiCorresponde();
        return $response;
    }

    public function procesar($id)
    {
        $this->applyListScope();
        $envio = EncuestaEnvio::findOrFail($id);
        $this->crud->entry = $envio;
        $this->crearRespuestasSiCorresponde(true);
        return redirect()->back();
    }

    private function crearRespuestasSiCorresponde(bool $force = false): void
    {
        $envio = $this->crud->entry;
        if (! $envio) {
            return;
        }

        if ($envio->procesado_en && ! $force) {
            return;
        }

        if ($envio->fecha_envio && $envio->fecha_envio->isFuture() && ! $force) {
            return;
        }

        $query = Empleado::query()
            ->whereNull('fecha_retiro')
            ->where('cliente_id', $envio->cliente_id);

        if ($envio->sucursal_id) {
            $query->where('sucursal_id', $envio->sucursal_id);
        }

        $empleados = $query->get(['id']);

        foreach ($empleados as $empleado) {
            EncuestaRespuesta::firstOrCreate([
                'encuesta_id' => $envio->encuesta_id,
                'envio_id' => $envio->id,
                'empleado_id' => $empleado->id,
            ], [
                'token' => (string) Str::uuid(),
                'estado' => 'pendiente',
            ]);
        }

        $envio->update(['procesado_en' => now()]);
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general'])) {
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function applyListScope(): void
    {
        if (backpack_user()->hasRole('Administrador')) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general'])) {
            $empresaIds = backpack_user()->empresas()->pluck('clientes.id')->all();
            $this->crud->addClause('whereIn', 'cliente_id', $empresaIds ?: [0]);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }
}
