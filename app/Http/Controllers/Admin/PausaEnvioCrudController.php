<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Pausa;
use App\Models\PausaEnvio;
use App\Models\PausaParticipacion;
use App\Models\Sucursal;
use App\Services\TelegramService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PausaEnvioCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(PausaEnvio::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pausa-envio');
        CRUD::setEntityNameStrings('envío', 'envíos');
    }

    protected function setupListOperation(): void
    {
        $this->crud->addClause('with', ['participaciones']);
        $this->crud->setListView('admin.pausa_envios.list');
        $this->crud->addButtonFromView('line', 'pausa_envio_send', 'pausa_envio_send', 'end');

        CRUD::addColumn([
            'name' => 'pausa',
            'type' => 'closure',
            'label' => 'Pausa',
            'function' => fn ($entry) => optional($entry->pausa)->nombre,
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
                $respuestas = $entry->participaciones ?? collect();
                $total = $respuestas->count();
                if ($total === 0) {
                    return '<span class="text-muted">Sin respuestas</span>';
                }
                $links = $respuestas->take(3)->map(function ($resp) {
                    $url = url('/pausas/' . $resp->token);
                    return '<a href="' . e($url) . '" target="_blank">Link</a>';
                })->implode(' | ');
                $extra = $total - min($total, 3);
                return $links . ($extra > 0 ? ' <span class="text-muted">(+'.$extra.' más)</span>' : '');
            },
        ]);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('pausa_id')
            ->type('select')
            ->label('Pausa activa')
            ->entity('pausa')
            ->model(Pausa::class)
            ->attribute('nombre');

        CRUD::field('cliente_id')
            ->type('select')
            ->label('Empresa')
            ->entity('cliente')
            ->model(Cliente::class)
            ->attribute('nombre');

        CRUD::field('sucursal_id')
            ->type('select')
            ->label('Planta (opcional)')
            ->entity('sucursal')
            ->model(Sucursal::class)
            ->attribute('nombre');

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
        $this->crearParticipacionesFor($this->crud->entry, [
            'force' => true,
            'mode' => 'all',
            'only_incomplete' => true,
        ]);
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->crearParticipacionesFor($this->crud->entry, [
            'force' => true,
            'mode' => 'all',
            'only_incomplete' => true,
        ]);
        return $response;
    }

    public function procesar(Request $request, $id)
    {
        $envio = PausaEnvio::findOrFail($id);
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

        $this->crearParticipacionesFor($envio, [
            'force' => true,
            'mode' => $mode,
            'only_incomplete' => $onlyIncomplete,
        ]);

        return redirect()->back();
    }

    public function procesarProgramados(): void
    {
        $envios = PausaEnvio::query()
            ->whereNotNull('programado_para')
            ->whereNull('procesado_en')
            ->where('programado_para', '<=', now())
            ->get();

        foreach ($envios as $envio) {
            $this->crearParticipacionesFor($envio, [
                'force' => true,
                'mode' => $envio->programado_modo ?: 'all',
                'only_incomplete' => $envio->programado_solo_no_completados ?? true,
            ]);
        }
    }

    private function crearParticipacionesFor(?PausaEnvio $envio, array $options = []): void
    {
        if (! $envio) {
            return;
        }

        $force = (bool) ($options['force'] ?? false);
        $mode = $options['mode'] ?? 'all';
        $onlyIncomplete = (bool) ($options['only_incomplete'] ?? true);

        if ($envio->procesado_en && ! $force) {
            return;
        }

        if ($envio->fecha_envio && $envio->fecha_envio->isFuture() && ! $force) {
            return;
        }

        $service = new TelegramService();
        $botUsername = config('services.telegram.bot_username', 'genesis_col_bot');

        if ($mode === 'pending') {
            $participaciones = PausaParticipacion::query()
                ->where('envio_id', $envio->id)
                ->where('estado', 'pendiente')
                ->get();

            foreach ($participaciones as $participacion) {
                $empleado = Empleado::find($participacion->empleado_id);
                if (! $empleado || ! $empleado->telegram_chat_id) {
                    continue;
                }

                if ($onlyIncomplete && $participacion->estado === 'completada') {
                    continue;
                }

                $link = url('/pausas/' . $participacion->token);
                $text = "Pausa activa: {$envio->pausa?->nombre}\n\nAccede aquí: {$link}";
                $service->sendMessage($empleado->telegram_chat_id, $text);
            }
        } else {
            $query = Empleado::query()
                ->whereNull('fecha_retiro')
                ->where('cliente_id', $envio->cliente_id);

            if ($envio->sucursal_id) {
                $query->where('sucursal_id', $envio->sucursal_id);
            }

            $empleados = $query->get(['id', 'telegram_chat_id']);

            foreach ($empleados as $empleado) {
                $participacion = PausaParticipacion::firstOrCreate([
                    'envio_id' => $envio->id,
                    'empleado_id' => $empleado->id,
                ], [
                    'token' => (string) Str::uuid(),
                    'estado' => $empleado->telegram_chat_id ? 'pendiente' : 'pendiente_activacion',
                ]);

                if ($empleado->telegram_chat_id && $participacion->estado === 'pendiente_activacion') {
                    $participacion->estado = 'pendiente';
                    $participacion->save();
                }

                if ($onlyIncomplete && $participacion->estado === 'completada') {
                    continue;
                }

                if ($empleado->telegram_chat_id) {
                    $link = url('/pausas/' . $participacion->token);
                    $text = "Pausa activa: {$envio->pausa?->nombre}\n\nAccede aquí: {$link}";
                    $service->sendMessage($empleado->telegram_chat_id, $text);
                }
            }
        }

        $envio->update([
            'procesado_en' => now(),
            'programado_para' => null,
            'programado_modo' => null,
            'programado_solo_no_completados' => null,
        ]);
    }
}
