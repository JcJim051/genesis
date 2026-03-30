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
        $this->crud->addButtonFromView('line', 'pausa_envio_procesar', 'pausa_envio_procesar', 'end');

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
        $this->crearParticipaciones(true);
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->crearParticipaciones(true);
        return $response;
    }

    public function procesar($id)
    {
        $envio = PausaEnvio::findOrFail($id);
        $this->crud->entry = $envio;
        $this->crearParticipaciones(true);
        return redirect()->back();
    }

    private function crearParticipaciones(bool $force = false): void
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

        $empleados = $query->get(['id', 'telegram_chat_id']);
        $service = new TelegramService();
        $botUsername = config('services.telegram.bot_username', 'genesis_col_bot');

        foreach ($empleados as $empleado) {
            $participacion = PausaParticipacion::firstOrCreate([
                'envio_id' => $envio->id,
                'empleado_id' => $empleado->id,
            ], [
                'token' => (string) Str::uuid(),
                'estado' => $empleado->telegram_chat_id ? 'pendiente' : 'pendiente_activacion',
            ]);

            if ($empleado->telegram_chat_id) {
                $link = url('/pausas/' . $participacion->token);
                $text = "Pausa activa: {$envio->pausa?->nombre}\n\nAccede aquí: {$link}";
                $service->sendMessage($empleado->telegram_chat_id, $text);
            }
        }

        $envio->update(['procesado_en' => now()]);
    }
}
