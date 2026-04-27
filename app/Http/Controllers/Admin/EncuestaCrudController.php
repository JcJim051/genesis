<?php

namespace App\Http\Controllers\Admin;

use App\Models\Encuesta;
use App\Models\EncuestaPregunta;
use App\Models\EncuestaOpcion;
use App\Models\Cliente;
use App\Models\Programa;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class EncuestaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        if (! backpack_user() || ! backpack_user()->hasRole('Administrador')) {
            abort(403);
        }

        CRUD::setModel(Encuesta::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/encuesta');
        CRUD::setEntityNameStrings('encuesta', 'encuestas');
        $this->crud->denyAccess(['create', 'update', 'delete']);
    }

    protected function setupListOperation(): void
    {
        $this->crud->addButtonFromView('top', 'encuesta_builder_create', 'encuesta_builder_create', 'beginning');
        $this->crud->addButtonFromView('line', 'encuesta_builder', 'encuesta_builder', 'beginning');

        CRUD::column('titulo');
        CRUD::addColumn([
            'name' => 'programa',
            'type' => 'closure',
            'label' => 'Programa',
            'function' => fn ($entry) => optional($entry->programa)->nombre,
        ]);
        CRUD::column('umbral_puntaje');
        CRUD::column('activa')->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('titulo')->type('text')->label('Título');
        CRUD::field('programa_id')
            ->type('select')
            ->label('Programa')
            ->entity('programa')
            ->model(Programa::class)
            ->attribute('nombre');
        CRUD::field('umbral_puntaje')->type('number')->label('Umbral puntaje');
        CRUD::field('activa')->type('checkbox')->label('Activa');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->crud->addButtonFromView('top', 'encuesta_builder', 'encuesta_builder', 'beginning');
    }

    public function builder(Request $request, ?int $id = null)
    {
        if (! backpack_user() || ! backpack_user()->hasRole('Administrador')) {
            abort(403);
        }

        $encuesta = $id ? Encuesta::findOrFail($id) : new Encuesta();
        $programas = Programa::orderBy('nombre')->get();

        $clientesQuery = Cliente::orderBy('nombre');
        $sucursalesQuery = Sucursal::orderBy('nombre');
        if (! backpack_user()->hasRole('Administrador')) {
            $empresaIds = \App\Support\TenantSelection::empresaIds();
            $clientesQuery->whereIn('id', $empresaIds ?: [0]);
            $sucursalesQuery->whereIn('cliente_id', $empresaIds ?: [0]);
        }
        $clientes = $clientesQuery->get();
        $sucursales = $sucursalesQuery->get();

        $questions = [];
        if ($encuesta->exists) {
            $all = EncuestaPregunta::with('opciones')
                ->where('encuesta_id', $encuesta->id)
                ->orderBy('orden')
                ->get();

            $childIds = collect();
            $rulesByParent = [];
            foreach ($all as $q) {
                $rules = $q->conditional_rules ?? [];
                $options = $rules['options'] ?? [];
                foreach ($options as $optionId => $childList) {
                    foreach ($childList as $childId) {
                        $childIds->push($childId);
                        $rulesByParent[$q->id][$optionId] = $childList;
                    }
                }
            }

            $childIds = $childIds->unique()->all();
            $byId = $all->keyBy('id');

            $buildQuestion = function ($q) use (&$buildQuestion, $rulesByParent, $byId) {
                $qKey = 'q' . $q->id;
                $options = [];
                foreach ($q->opciones->sortBy('orden') as $opt) {
                    $optKey = 'o' . $opt->id;
                    $children = [];
                    $childIds = $rulesByParent[$q->id][$opt->id] ?? [];
                    foreach ($childIds as $childId) {
                        if (! $byId->has($childId)) {
                            continue;
                        }
                        $childQ = $byId->get($childId);
                        $children[] = $buildQuestion($childQ);
                    }

                    $options[] = [
                        'key' => $optKey,
                        'id' => $opt->id,
                        'texto' => $opt->texto,
                        'puntaje' => $opt->puntaje,
                        'orden' => $opt->orden,
                        'children' => $children,
                    ];
                }

                return [
                    'key' => $qKey,
                    'id' => $q->id,
                    'texto' => $q->texto,
                    'orden' => $q->orden,
                    'options' => $options,
                ];
            };

            foreach ($all->whereNotIn('id', $childIds) as $q) {
                $questions[] = $buildQuestion($q);
            }
        }

        return view('admin.encuestas.builder', [
            'encuesta' => $encuesta,
            'programas' => $programas,
            'clientes' => $clientes,
            'sucursales' => $sucursales,
            'questions' => $questions,
        ]);
    }

    public function builderSave(Request $request, ?int $id = null)
    {
        if (! backpack_user() || ! backpack_user()->hasRole('Administrador')) {
            abort(403);
        }

        $data = $request->validate([
            'titulo' => 'required|string',
            'programa_id' => 'required|integer',
            'umbral_puntaje' => 'nullable|numeric',
            'activa' => 'nullable|boolean',
            'cliente_id' => 'nullable|integer',
            'sucursal_id' => 'nullable|integer',
        ]);

        $encuesta = $id ? Encuesta::findOrFail($id) : new Encuesta();
        $clienteId = $data['cliente_id'] ?? null;
        $sucursalId = $data['sucursal_id'] ?? null;
        if ($sucursalId && ! $clienteId) {
            $clienteId = Sucursal::whereKey($sucursalId)->value('cliente_id');
        }
        $encuesta->fill([
            'titulo' => $data['titulo'],
            'programa_id' => $data['programa_id'],
            'umbral_puntaje' => $data['umbral_puntaje'] ?? 0,
            'activa' => (bool) ($data['activa'] ?? false),
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
        ]);
        $encuesta->save();

        $questions = $request->input('questions', []);
        $keyToId = [];
        $submittedIds = [];
        $meta = [];

        foreach ($questions as $qKey => $q) {
            $texto = trim((string) ($q['texto'] ?? ''));
            if ($texto === '') {
                continue;
            }

            $question = null;
            if (! empty($q['id'])) {
                $question = EncuestaPregunta::find($q['id']);
            }

            if (! $question) {
                $question = new EncuestaPregunta();
                $question->encuesta_id = $encuesta->id;
            }

            $question->texto = $texto;
            $question->orden = (int) ($q['orden'] ?? 0);
            $question->conditional_rules = null;
            $question->save();

            $keyToId[$qKey] = $question->id;
            $submittedIds[] = $question->id;
            $meta[$qKey] = [
                'parent_key' => $q['parent_key'] ?? null,
                'parent_option_key' => $q['parent_option_key'] ?? null,
                'options' => $q['options'] ?? [],
            ];
        }

        // sync options and map option keys
        $optionKeyToId = [];
        foreach ($meta as $qKey => $info) {
            $questionId = $keyToId[$qKey] ?? null;
            if (! $questionId) {
                continue;
            }

            $existing = EncuestaOpcion::where('pregunta_id', $questionId)->pluck('id')->all();
            $used = [];
            $optionKeyToId[$qKey] = [];

            foreach ($info['options'] as $oKey => $opt) {
                $texto = trim((string) ($opt['texto'] ?? ''));
                if ($texto === '') {
                    continue;
                }

                $opcion = null;
                if (! empty($opt['id'])) {
                    $opcion = EncuestaOpcion::find($opt['id']);
                }
                if (! $opcion) {
                    $opcion = new EncuestaOpcion();
                    $opcion->pregunta_id = $questionId;
                }

                $opcion->texto = $texto;
                $opcion->puntaje = (int) ($opt['puntaje'] ?? 0);
                $opcion->orden = (int) ($opt['orden'] ?? 0);
                $opcion->save();

                $used[] = $opcion->id;
                $optionKeyToId[$qKey][$oKey] = $opcion->id;
            }

            $toDelete = array_diff($existing, $used);
            if (! empty($toDelete)) {
                EncuestaOpcion::whereIn('id', $toDelete)->delete();
            }
        }

        // apply conditional rules to parent questions
        $rulesByParent = [];
        foreach ($meta as $qKey => $info) {
            $parentKey = $info['parent_key'] ?? null;
            $parentOptKey = $info['parent_option_key'] ?? null;
            if (! $parentKey || ! $parentOptKey) {
                continue;
            }

            $parentId = $keyToId[$parentKey] ?? null;
            $childId = $keyToId[$qKey] ?? null;
            $optionId = $optionKeyToId[$parentKey][$parentOptKey] ?? null;
            if (! $parentId || ! $childId || ! $optionId) {
                continue;
            }

            $rulesByParent[$parentId][$optionId][] = $childId;
        }

        foreach ($rulesByParent as $parentId => $rules) {
            EncuestaPregunta::where('id', $parentId)->update([
                'conditional_rules' => ['options' => $rules],
            ]);
        }

        // clear rules for parents without children
        EncuestaPregunta::where('encuesta_id', $encuesta->id)
            ->whereNotIn('id', array_keys($rulesByParent))
            ->update(['conditional_rules' => null]);

        // delete removed questions
        if (! empty($submittedIds)) {
            EncuestaPregunta::where('encuesta_id', $encuesta->id)
                ->whereNotIn('id', $submittedIds)
                ->delete();
        }

        return redirect(backpack_url('encuesta/' . $encuesta->id . '/builder'))
            ->with('success', 'Encuesta guardada correctamente.');
    }
}
