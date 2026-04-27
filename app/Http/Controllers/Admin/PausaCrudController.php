<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Models\Pausa;
use App\Models\PausaFormulario;
use App\Models\PausaPregunta;
use App\Models\PausaOpcion;
use App\Models\Cliente;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class PausaCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; edit as traitEdit; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(Pausa::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pausa');
        CRUD::setEntityNameStrings('pausa activa', 'pausas activas');
        $this->crud->denyAccess(['create', 'update', 'delete']);

        $this->scopeMode = 'fields';
        $this->scopeModelClass = Pausa::class;
        $this->applyTenantScope($this->crud);
    }

    protected function setupListOperation(): void
    {
        $this->crud->addButtonFromView('top', 'pausa_builder_create', 'pausa_builder_create', 'beginning');
        $this->crud->addButtonFromView('line', 'pausa_builder', 'pausa_builder', 'beginning');

        CRUD::column('nombre');
        CRUD::column('categoria');
        CRUD::column('tiempo_minimo_segundos')->label('Tiempo mínimo (s)');
        CRUD::column('activa')->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('nombre')->type('text')->label('Nombre');
        CRUD::field('descripcion')->type('textarea')->label('Descripción');
        CRUD::field('categoria')->type('select_from_array')->label('Categoría')->options([
            'virtual' => 'Virtual',
            'osteomuscular' => 'Osteomuscular',
            'psicosocial' => 'Psicosocial',
            'otros' => 'Otros',
        ])->allows_null(true);
        CRUD::field('video_url')->type('url')->label('Video URL');
        CRUD::field('tiempo_minimo_segundos')->type('number')->label('Tiempo mínimo (segundos)')->default(60);
        CRUD::field('activa')->type('checkbox')->label('Activa');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->crud->addButtonFromView('top', 'pausa_builder', 'pausa_builder', 'beginning');
    }

    public function store()
    {
        $response = $this->traitStore();
        $this->ensureFormulario();
        return $response;
    }

    public function update()
    {
        $this->enforceEntryScopeOrFail((int) $this->crud->getCurrentEntryId());
        $response = $this->traitUpdate();
        $this->ensureFormulario();
        return $response;
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

    private function ensureFormulario(): void
    {
        $pausa = $this->crud->entry;
        if (! $pausa) {
            return;
        }
        if (! $pausa->formulario) {
            $pausa->formulario()->create();
        }
    }

    public function builder(Request $request, ?int $id = null)
    {
        if ($id) {
            $this->enforceEntryScopeOrFail($id);
        }
        $pausa = $id ? Pausa::findOrFail($id) : new Pausa();
        $questions = [];

        $clientesQuery = Cliente::orderBy('nombre');
        $sucursalesQuery = Sucursal::orderBy('nombre');
        if (! \App\Support\TenantSelection::isAdminBypass()) {
            $empresaIds = \App\Support\TenantSelection::empresaIds();
            $clientesQuery->whereIn('id', $empresaIds ?: [0]);
            $sucursalesQuery->whereIn('cliente_id', $empresaIds ?: [0]);
        }
        $clientes = $clientesQuery->get();
        $sucursales = $sucursalesQuery->get();

        if ($pausa->exists) {
            $formulario = $pausa->formulario ?: $pausa->formulario()->create();
            $all = PausaPregunta::with('opciones')
                ->where('formulario_id', $formulario->id)
                ->orderBy('orden')
                ->get();

            foreach ($all as $q) {
                $qKey = 'q' . $q->id;
                $options = [];
                foreach ($q->opciones->sortBy('orden') as $opt) {
                    $options[] = [
                        'key' => 'o' . $opt->id,
                        'id' => $opt->id,
                        'texto' => $opt->texto,
                        'valor' => $opt->valor,
                        'orden' => $opt->orden,
                    ];
                }

                $questions[] = [
                    'key' => $qKey,
                    'id' => $q->id,
                    'texto' => $q->texto,
                    'tipo' => $q->tipo,
                    'orden' => $q->orden,
                    'options' => $options,
                ];
            }
        }

        return view('admin.pausas.builder', [
            'pausa' => $pausa,
            'clientes' => $clientes,
            'sucursales' => $sucursales,
            'questions' => $questions,
        ]);
    }

    public function builderSave(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string',
            'video_url' => 'nullable|string',
            'external_url' => 'nullable|url',
            'external_provider' => 'nullable|string',
            'tiempo_minimo_segundos' => 'nullable|numeric',
            'activa' => 'nullable|boolean',
            'cliente_id' => 'nullable|integer',
            'sucursal_id' => 'nullable|integer',
        ]);

        if (! empty($data['external_url']) && ! $this->isAllowedExternalUrl($data['external_url'])) {
            return back()
                ->withInput()
                ->withErrors(['external_url' => 'El dominio de la actividad externa no está permitido.']);
        }

        $pausa = $id ? Pausa::findOrFail($id) : new Pausa();
        $clienteId = $data['cliente_id'] ?? null;
        $sucursalId = $data['sucursal_id'] ?? null;
        if ($sucursalId && ! $clienteId) {
            $clienteId = Sucursal::whereKey($sucursalId)->value('cliente_id');
        }

        $pausa->fill([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'categoria' => $data['categoria'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'external_provider' => $data['external_provider'] ?? null,
            'tiempo_minimo_segundos' => $data['tiempo_minimo_segundos'] ?? 60,
            'activa' => (bool) ($data['activa'] ?? false),
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
        ]);
        $pausa->save();

        $formulario = $pausa->formulario ?: $pausa->formulario()->create();

        $questions = $request->input('questions', []);
        $keyToId = [];
        $submittedIds = [];

        foreach ($questions as $qKey => $q) {
            $texto = trim((string) ($q['texto'] ?? ''));
            if ($texto === '') {
                continue;
            }

            $question = null;
            if (! empty($q['id'])) {
                $question = PausaPregunta::find($q['id']);
            }

            if (! $question) {
                $question = new PausaPregunta();
                $question->formulario_id = $formulario->id;
            }

            $question->texto = $texto;
            $question->tipo = $q['tipo'] ?? 'abierta';
            $question->orden = (int) ($q['orden'] ?? 0);
            $question->save();

            $keyToId[$qKey] = $question->id;
            $submittedIds[] = $question->id;
        }

        // options
        foreach ($questions as $qKey => $q) {
            $questionId = $keyToId[$qKey] ?? null;
            if (! $questionId) {
                continue;
            }

            $question = PausaPregunta::find($questionId);
            if ($question && $question->tipo !== 'opcion') {
                PausaOpcion::where('pregunta_id', $questionId)->delete();
                continue;
            }

            $existing = PausaOpcion::where('pregunta_id', $questionId)->pluck('id')->all();
            $used = [];

            foreach (($q['options'] ?? []) as $oKey => $opt) {
                $texto = trim((string) ($opt['texto'] ?? ''));
                if ($texto === '') {
                    continue;
                }

                $opcion = null;
                if (! empty($opt['id'])) {
                    $opcion = PausaOpcion::find($opt['id']);
                }
                if (! $opcion) {
                    $opcion = new PausaOpcion();
                    $opcion->pregunta_id = $questionId;
                }

                $opcion->texto = $texto;
                $opcion->valor = (string) ($opt['valor'] ?? '');
                $opcion->orden = (int) ($opt['orden'] ?? 0);
                $opcion->save();

                $used[] = $opcion->id;
            }

            $toDelete = array_diff($existing, $used);
            if (! empty($toDelete)) {
                PausaOpcion::whereIn('id', $toDelete)->delete();
            }
        }

        if (! empty($submittedIds)) {
            PausaPregunta::where('formulario_id', $formulario->id)
                ->whereNotIn('id', $submittedIds)
                ->delete();
        }

        return redirect(backpack_url('pausa'))
            ->with('success', 'Pausa guardada correctamente.');
    }

    protected function isAllowedExternalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return false;
        }

        $host = strtolower($host);
        $allowedRoots = [
            'educaplay.com',
            'wordwall.net',
            'genial.ly',
        ];

        foreach ($allowedRoots as $root) {
            if ($host === $root || str_ends_with($host, '.' . $root)) {
                return true;
            }
        }

        return false;
    }
}
