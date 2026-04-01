<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Encuesta;
use App\Models\EncuestaEnvio;
use App\Models\EncuestaRespuesta;
use App\Models\Sucursal;
use App\Services\TelegramService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $this->crud->setListView('admin.encuesta_envios.list');
        $this->crud->addButtonFromView('line', 'encuesta_envio_send', 'encuesta_envio_send', 'end');

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
        $this->crearRespuestasSiCorresponde(true, ['mode' => 'all', 'only_incomplete' => true]);
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->crearRespuestasSiCorresponde(true, ['mode' => 'all', 'only_incomplete' => true]);
        return $response;
    }

    public function procesar(Request $request, $id)
    {
        $this->applyListScope();
        $envio = EncuestaEnvio::findOrFail($id);
        $mode = $request->input('send_mode', 'all');
        $onlyIncomplete = $request->boolean('only_incomplete', true);
        $scheduledAt = $request->input('scheduled_at');

        if ($scheduledAt) {
            $when = Carbon::parse($scheduledAt);
            if ($when->isFuture()) {
                $envio->update([
                    'programado_para' => $when,
                    'programado_modo' => $mode,
                    'programado_solo_no_completados' => $onlyIncomplete,
                ]);

                return redirect()->back()->with('success', 'Envío programado para ' . $when->format('Y-m-d H:i'));
            }
        }

        $this->crud->entry = $envio;
        $this->crearRespuestasSiCorresponde(true, [
            'mode' => $mode,
            'only_incomplete' => $onlyIncomplete,
        ]);

        return redirect()->back();
    }

    public function procesarProgramados(): void
    {
        $envios = EncuestaEnvio::query()
            ->whereNotNull('programado_para')
            ->whereNull('procesado_en')
            ->where('programado_para', '<=', now())
            ->get();

        foreach ($envios as $envio) {
            $this->crud->entry = $envio;
            $this->crearRespuestasSiCorresponde(true, [
                'mode' => $envio->programado_modo ?: 'all',
                'only_incomplete' => $envio->programado_solo_no_completados ?? true,
            ]);
        }
    }

    private function crearRespuestasSiCorresponde(bool $force = false, array $options = []): void
    {
        $envio = $this->crud->entry;
        if (! $envio) {
            return;
        }

        $mode = $options['mode'] ?? 'all';
        $onlyIncomplete = (bool) ($options['only_incomplete'] ?? true);

        if ($envio->procesado_en && ! $force) {
            return;
        }

        if ($envio->fecha_envio && $envio->fecha_envio->isFuture() && ! $force) {
            return;
        }

        $service = new TelegramService();

        if ($mode === 'pending') {
            $respuestas = EncuestaRespuesta::query()
                ->where('envio_id', $envio->id)
                ->get();

            foreach ($respuestas as $resp) {
                $empleado = Empleado::find($resp->empleado_id);
                if (! $empleado || ! $empleado->telegram_chat_id) {
                    continue;
                }
                if ($onlyIncomplete && $resp->estado === 'completada') {
                    continue;
                }
                $link = url('/encuestas/' . $resp->token);
                $text = "Encuesta: {$envio->encuesta?->titulo}\n\nAccede aquí: {$link}";
                $service->sendMessage($empleado->telegram_chat_id, $text);
            }
        } else {
            $query = Empleado::query()
                ->whereNull('fecha_retiro')
                ->where('cliente_id', $envio->cliente_id);

            if ($envio->sucursal_id) {
                $query->where('sucursal_id', $envio->sucursal_id);
            }

            $empleados = $query->get(['id']);

            foreach ($empleados as $empleado) {
                $resp = EncuestaRespuesta::firstOrCreate([
                    'encuesta_id' => $envio->encuesta_id,
                    'envio_id' => $envio->id,
                    'empleado_id' => $empleado->id,
                ], [
                    'token' => (string) Str::uuid(),
                    'estado' => $empleado->telegram_chat_id ? 'pendiente' : 'pendiente_activacion',
                ]);

                if ($empleado->telegram_chat_id && $resp->estado === 'pendiente_activacion') {
                    $resp->estado = 'pendiente';
                    $resp->save();
                }

                if ($onlyIncomplete && $resp->estado === 'completada') {
                    continue;
                }

                if ($empleado->telegram_chat_id) {
                    $link = url('/encuestas/' . $resp->token);
                    $text = "Encuesta: {$envio->encuesta?->titulo}\n\nAccede aquí: {$link}";
                    $service->sendMessage($empleado->telegram_chat_id, $text);
                }
            }
        }

        $envio->update([
            'procesado_en' => now(),
            'programado_para' => null,
            'programado_modo' => null,
            'programado_solo_no_completados' => $onlyIncomplete,
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
