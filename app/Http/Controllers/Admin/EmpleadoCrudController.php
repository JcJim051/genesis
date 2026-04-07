<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\EmpleadoRequest;
use App\Mail\TelegramActivationMail;
use App\Models\Empleado;
use App\Models\EmpleadoArea;
use App\Models\EmpleadoCargo;
use App\Models\Cliente;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Prologue\Alerts\Facades\Alert;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmpleadoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Empleado::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/empleado');
        CRUD::setEntityNameStrings('persona', 'personas');

        $this->applyAccessRules();
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();
        $this->crud->addButtonFromView('top', 'telegram_pendientes', 'empleado_telegram_pendientes', 'beginning');
        $this->crud->addButtonFromView('top', 'telegram_pendientes_csv', 'empleado_telegram_pendientes_csv', 'beginning');
        $this->crud->addButtonFromView('top', 'import', 'empleado_import', 'beginning');

        CRUD::addColumn([
            'name' => 'cliente_nombre',
            'type' => 'model_function',
            'label' => 'Empresa',
            'function_name' => 'getClienteNombre',
        ]);
        CRUD::addColumn([
            'name' => 'sucursal_nombre',
            'type' => 'model_function',
            'label' => 'Planta',
            'function_name' => 'getSucursalNombre',
        ]);
        CRUD::column('nombre')->label('Nombre');
        CRUD::column('cedula')->label('Cédula');
        CRUD::addColumn([
            'name' => 'cargo_actual',
            'type' => 'model_function',
            'label' => 'Cargo Actual',
            'function_name' => 'getCargoActual',
        ]);
        CRUD::addColumn([
            'name' => 'antiguedad_cargo_actual',
            'type' => 'model_function',
            'label' => 'Antigüedad Cargo',
            'function_name' => 'getAntiguedadCargoActual',
        ]);
        CRUD::addColumn([
            'name' => 'area_actual',
            'type' => 'model_function',
            'label' => 'Área Actual',
            'function_name' => 'getAreaActual',
        ]);
        CRUD::column('telefono')->label('Teléfono');
        CRUD::column('correo_electronico')->label('Correo');
        CRUD::column('tipo_contrato')->label('Contrato');
        CRUD::column('genero')->label('Género');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(EmpleadoRequest::class);
        $this->enforceEntryScope();

        CRUD::field('cliente_id')
            ->type('select')
            ->label('Empresa')
            ->entity('cliente')
            ->model(\App\Models\Cliente::class)
            ->attribute('nombre')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->options(function ($query) {
                if ($this->isAdmin()) {
                    return $query->orderBy('nombre')->get();
                }

                $allowed = $this->allowedEmpresaIdsForCreate();
                return $query->whereIn('id', $allowed ?: [0])->orderBy('nombre')->get();
            });

        CRUD::field('sucursal_id')
            ->type('select')
            ->label('Planta')
            ->entity('sucursal')
            ->model(\App\Models\Sucursal::class)
            ->attribute('nombre')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->options(function ($query) {
                $allowed = $this->allowedPlantaIdsForCreate();
                return $query->whereIn('id', $allowed ?: [0])->orderBy('nombre')->get();
            });

        CRUD::field('nombre')->type('text')->label('Nombre')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('cedula')->type('text')->label('Cédula')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('eps')->type('text')->label('EPS')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('arl')->type('text')->label('ARL')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fondo_pensiones')->type('text')->label('Fondo de pensiones')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('cargo')->type('text')->label('Cargo')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('telefono')->type('text')->label('Teléfono')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('direccion')->type('text')->label('Dirección')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('correo_electronico')->type('email')->label('Correo')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('tipo_contrato')->type('text')->label('Tipo de contrato')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('lateralidad')->type('select_from_array')->label('Lateralidad')->options([
            'Derecha' => 'Derecha',
            'Izquierda' => 'Izquierda',
            'Ambidiestro' => 'Ambidiestro',
        ])->allows_null(true)->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('genero')->type('select_from_array')->label('Género')->options([
            'Masculino' => 'Masculino',
            'Femenino' => 'Femenino',
            'Otro' => 'Otro',
        ])->allows_null(true)->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fecha_nacimiento')->type('date')->label('Fecha de nacimiento')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fecha_ingreso')->type('date')->label('Fecha de ingreso')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('fecha_retiro')->type('date')->label('Fecha de retiro')->wrapper(['class' => 'form-group col-md-4']);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->enforceEntryScope();
        $this->crud->setShowView('admin.empleados.show');

        CRUD::addColumn([
            'name' => 'cargos_historial',
            'type' => 'model_function',
            'label' => 'Historial de cargos',
            'function_name' => 'getCargosHistorial',
            'escaped' => false,
        ]);
        CRUD::addColumn([
            'name' => 'areas_historial',
            'type' => 'model_function',
            'label' => 'Historial de áreas',
            'function_name' => 'getAreasHistorial',
            'escaped' => false,
        ]);
    }

    public function lookup()
    {
        if (! backpack_user()) {
            abort(403);
        }

        $id = request()->get('id');
        $cedula = request()->get('cedula');
        $nombre = request()->get('nombre');

        if (! $id && ! $cedula && ! $nombre) {
            return response()->json(['message' => 'Debe enviar id, cédula o nombre.'], 422);
        }

        $query = Empleado::query();

        if (! $this->isAdmin()) {
            if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
                $empresaIds = $this->empresaIdsForUser();
                $query->whereIn('cliente_id', $empresaIds ?: [0]);
            } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
                $plantaIds = $this->plantaIdsForUser();
                $query->whereIn('sucursal_id', $plantaIds ?: [0]);
            } else {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        }

        if ($id) {
            $query->whereKey($id);
        } elseif ($cedula) {
            $query->where('cedula', $cedula);
        } else {
            $query->where('nombre', 'like', '%' . $nombre . '%')->orderBy('nombre');
        }

        $empleado = $query->first();
        if (! $empleado) {
            return response()->json(['message' => 'Persona no encontrada.'], 404);
        }

        return response()->json([
            'id' => $empleado->id,
            'nombre' => $empleado->nombre,
            'cedula' => $empleado->cedula,
            'fecha_nacimiento' => optional($empleado->fecha_nacimiento)->format('Y-m-d'),
            'edad' => $empleado->edad,
            'genero' => $empleado->genero,
            'lateralidad' => $empleado->lateralidad,
            'eps' => $empleado->eps,
            'arl' => $empleado->arl,
            'fondo_pensiones' => $empleado->fondo_pensiones,
            'telefono' => $empleado->telefono,
            'correo_electronico' => $empleado->correo_electronico,
            'direccion' => $empleado->direccion,
            'fecha_ingreso' => optional($empleado->fecha_ingreso)->format('Y-m-d'),
            'cargo_actual' => $empleado->getCargoActual(),
            'antiguedad_cargo' => $empleado->getAntiguedadCargoActual(),
            'area_actual' => $empleado->getAreaActual(),
        ]);
    }

    public function telegramPendientes()
    {
        $this->applyAccessRules();
        $this->applyListScope();

        $query = Empleado::query()
            ->whereNull('telegram_chat_id')
            ->whereNull('fecha_retiro');

        if (! $this->isAdmin()) {
            if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
                $empresaIds = $this->empresaIdsForUser();
                $query->whereIn('cliente_id', $empresaIds ?: [0]);
            } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
                $plantaIds = $this->plantaIdsForUser();
                $query->whereIn('sucursal_id', $plantaIds ?: [0]);
            } else {
                abort(403);
            }
        }

        $filename = 'activacion_telegram_pendientes_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['NOMBRE', 'CEDULA', 'EMPRESA', 'PLANTA', 'LINK']);

            $query->with(['cliente', 'sucursal'])->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $empleado) {
                    $link = $empleado->getTelegramActivationUrl();
                    fputcsv($handle, [
                        $empleado->nombre,
                        $empleado->cedula,
                        $empleado->cliente?->nombre,
                        $empleado->sucursal?->nombre,
                        $link,
                    ]);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function telegramPendientesView()
    {
        $this->applyAccessRules();
        $this->applyListScope();

        $query = Empleado::query()
            ->whereNull('telegram_chat_id')
            ->whereNull('fecha_retiro');

        if (! $this->isAdmin()) {
            if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
                $empresaIds = $this->empresaIdsForUser();
                $query->whereIn('cliente_id', $empresaIds ?: [0]);
            } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
                $plantaIds = $this->plantaIdsForUser();
                $query->whereIn('sucursal_id', $plantaIds ?: [0]);
            } else {
                abort(403);
            }
        }

        $total = (clone $query)->count();
        $conCorreo = (clone $query)->whereNotNull('correo_electronico')->count();
        $sinCorreo = $total - $conCorreo;

        $empleados = $query->with(['cliente', 'sucursal'])->orderBy('nombre')->paginate(50);

        return view('admin.empleados.telegram_pendientes', [
            'empleados' => $empleados,
            'total' => $total,
            'conCorreo' => $conCorreo,
            'sinCorreo' => $sinCorreo,
        ]);
    }

    public function enviarTelegramEmail($id)
    {
        $this->enforceEntryScope((int) $id);
        $empleado = Empleado::findOrFail($id);

        if (! $empleado->correo_electronico) {
            return back()->withErrors('La persona no tiene correo registrado.');
        }

        $link = $empleado->getTelegramActivationUrl();
        Mail::to($empleado->correo_electronico)->send(new TelegramActivationMail($empleado, $link));

        return back()->with('success', 'Correo de activación enviado.');
    }

    public function enviarTelegramEmailsPendientes()
    {
        $this->applyAccessRules();
        $this->applyListScope();

        $query = Empleado::query()
            ->whereNull('telegram_chat_id')
            ->whereNull('fecha_retiro')
            ->whereNotNull('correo_electronico');

        if (! $this->isAdmin()) {
            if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
                $empresaIds = $this->empresaIdsForUser();
                $query->whereIn('cliente_id', $empresaIds ?: [0]);
            } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
                $plantaIds = $this->plantaIdsForUser();
                $query->whereIn('sucursal_id', $plantaIds ?: [0]);
            } else {
                abort(403);
            }
        }

        $sent = 0;
        $query->chunk(200, function ($rows) use (&$sent) {
            foreach ($rows as $empleado) {
                $link = $empleado->getTelegramActivationUrl();
                Mail::to($empleado->correo_electronico)->send(new TelegramActivationMail($empleado, $link));
                $sent++;
            }
        });

        return back()->with('success', 'Correos de activación enviados: ' . $sent);
    }

    public function desvincularTelegram($id)
    {
        $this->enforceEntryScope((int) $id);

        $empleado = Empleado::findOrFail($id);
        $empleado->telegram_chat_id = null;
        $empleado->telegram_username = null;
        $empleado->save();

        Alert::add('success', 'Telegram desvinculado correctamente.')->flash();
        return redirect()->back();
    }

    public function store()
    {
        $this->ensureEmpresaPlantaWithinScope();
        $response = $this->traitStore();
        $this->syncEdad($this->crud->entry);
        $this->syncRetiroToHistories($this->crud->entry?->id, $this->crud->entry?->fecha_retiro);
        return $response;
    }

    public function update()
    {
        $this->ensureEmpresaPlantaWithinScope();
        $response = $this->traitUpdate();
        $this->syncEdad($this->crud->entry);
        $this->syncRetiroToHistories($this->crud->entry?->id, $this->crud->entry?->fecha_retiro);
        return $response;
    }

    public function importForm()
    {
        $this->ensureCanImport();
        return view('admin.empleados.import');
    }

    public function import(Request $request)
    {
        $this->ensureCanImport();
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $file = $request->file('archivo');
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if ($ext === 'xlsx') {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false) ?? [];
        } else {
            $rows = $this->parseCsv($file->getRealPath());
        }

        if (count($rows) < 2) {
            return back()->withErrors(['archivo' => 'El archivo no contiene filas válidas.']);
        }

        $headerRow = array_values($rows[0]);
        $header = array_map(function ($h) {
            $h = strtolower(trim((string) $h));
            $h = str_replace(['  ', "\n", "\r"], ' ', $h);
            return $h;
        }, $headerRow);

        $idx = $this->resolveHeaderIndexes($header);
        if ($idx['cedula'] === null) {
            return back()->withErrors(['archivo' => 'No se encontró la columna CEDULA.']);
        }
        if ($idx['empresa'] === null && $idx['planta'] === null) {
            return back()->withErrors(['archivo' => 'Debe incluir la columna EMPRESA o PLANTA para asignar empresa.']);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNumber = $i + 2;
            $row = array_values($row);

            try {
                $cedula = trim((string) ($row[$idx['cedula']] ?? ''));
                if ($cedula === '') {
                    $errors[] = "Fila {$rowNumber}: CÉDULA vacía.";
                    continue;
                }

                $nombre = trim((string) ($row[$idx['nombre']] ?? ''));
                $empleado = Empleado::where('cedula', $cedula)->first();
                $empresaNombre = trim((string) ($row[$idx['empresa']] ?? ''));
                $plantaNombre = trim((string) ($row[$idx['planta']] ?? ''));
                $hasEmpresaInput = ($empresaNombre !== '' || $plantaNombre !== '');

                [$clienteId, $sucursalId] = $hasEmpresaInput
                    ? $this->resolveEmpresaPlanta([
                        'empresa' => $empresaNombre,
                        'planta' => $plantaNombre,
                    ])
                    : [($empleado?->cliente_id), ($empleado?->sucursal_id)];

                if (! $this->canImportToScope($clienteId, $sucursalId)) {
                    $skipped++;
                    continue;
                }

                $data = [
                    'cliente_id' => $clienteId,
                    'sucursal_id' => $sucursalId,
                    'nombre' => $nombre !== '' ? $nombre : ($empleado?->nombre ?? ('SIN NOMBRE ' . $cedula)),
                ];

                $this->setIfNotNull($data, 'eps', $this->valueAt($row, $idx['eps']));
                $this->setIfNotNull($data, 'arl', $this->valueAt($row, $idx['arl']));
                $this->setIfNotNull($data, 'fondo_pensiones', $this->valueAt($row, $idx['fondo_pensiones']));
                $this->setIfNotNull($data, 'cargo', $this->valueAt($row, $idx['cargo']));
                $this->setIfNotNull($data, 'telefono', $this->valueAt($row, $idx['telefono']));
                $this->setIfNotNull($data, 'direccion', $this->valueAt($row, $idx['direccion']));
                $this->setIfNotNull($data, 'correo_electronico', $this->valueAt($row, $idx['correo']));
                $this->setIfNotNull($data, 'tipo_contrato', $this->valueAt($row, $idx['tipo_contrato']));
                $this->setIfNotNull($data, 'lateralidad', $this->valueAt($row, $idx['lateralidad']));
                $this->setIfNotNull($data, 'genero', $this->valueAt($row, $idx['genero']));

                $fechaNacimiento = $this->parseExcelDate($row[$idx['fecha_nacimiento']] ?? null);
                $fechaIngreso = $this->parseExcelDate($row[$idx['fecha_ingreso']] ?? null);
                $fechaRetiro = $this->parseExcelDate($row[$idx['fecha_retiro']] ?? null);

                $this->setIfNotNull($data, 'fecha_nacimiento', $fechaNacimiento);
                $this->setIfNotNull($data, 'fecha_ingreso', $fechaIngreso);
                $this->setIfNotNull($data, 'fecha_retiro', $fechaRetiro);

                if ($fechaNacimiento) {
                    $data['edad'] = $this->computeEdadFromFecha($fechaNacimiento);
                }

                if ($empleado) {
                    $empleado->fill($data);
                    $empleado->save();
                    $updated++;
                } else {
                    $empleado = Empleado::create(array_merge(['cedula' => $cedula], $data));
                    $created++;
                }

                if ($empleado?->fecha_retiro) {
                    $this->syncRetiroToHistories($empleado->id, $empleado->fecha_retiro);
                }
            } catch (\Throwable $e) {
                $errors[] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }

        $total = $created + $updated;
        $summary = "Importadas: {$total}. Nuevas: {$created}. Actualizadas: {$updated}. Omitidas: {$skipped}.";

        Alert::add('success', $summary)->flash();

        if (! empty($errors)) {
            $preview = array_slice($errors, 0, 20);
            $more = count($errors) > 20 ? ('<br>... y ' . (count($errors) - 20) . ' más.') : '';
            Alert::add('warning', "Errores de importación:<br>" . implode('<br>', $preview) . $more)->flash();
        }

        return redirect(backpack_url('empleado'));
    }

    public function template()
    {
        $this->ensureCanImport();

        $headers = [
            'EMPRESA',
            'PLANTA',
            'NOMBRE',
            'CEDULA',
            'EPS',
            'ARL',
            'FONDO_PENSIONES',
            'CARGO',
            'TELEFONO',
            'DIRECCION',
            'CORREO',
            'TIPO_CONTRATO',
            'LATERALIDAD',
            'GENERO',
            'FECHA_NACIMIENTO',
            'FECHA_INGRESO',
            'FECHA_RETIRO',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $writer = new Xlsx($spreadsheet);

        $tmp = tempnam(sys_get_temp_dir(), 'empleados_template_');
        $writer->save($tmp);

        return response()->download($tmp, 'plantilla_empleados.xlsx')->deleteFileAfterSend(true);
    }

    public function destroy($id)
    {
        $this->enforceEntryScope((int) $id);
        return $this->traitDestroy($id);
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if ($this->isAdmin()) {
            return;
        }

        if ($this->hasAnyRole(['Coordinador general'])) {
            return;
        }

        if ($this->hasAnyRole(['Asesor externo general', 'Coordinador de planta', 'Asesor externo planta'])) {
            $this->crud->denyAccess(['delete']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function applyListScope(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            $this->crud->addClause('whereIn', 'cliente_id', $empresaIds ?: [0]);
            return;
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = $this->plantaIdsForUser();
            $this->crud->addClause('whereIn', 'sucursal_id', $plantaIds ?: [0]);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }

    private function enforceEntryScope(?int $entryId = null): void
    {
        $entryId = $entryId ?? $this->crud->getCurrentEntryId();
        if (! $entryId || $this->isAdmin()) {
            return;
        }

        $query = Empleado::query()->whereKey($entryId);

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            $query->whereIn('cliente_id', $empresaIds ?: [0]);
        } elseif ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = $this->plantaIdsForUser();
            $query->whereIn('sucursal_id', $plantaIds ?: [0]);
        }

        if (! $query->exists()) {
            abort(403);
        }
    }

    private function isAdmin(): bool
    {
        return backpack_user()->hasRole('Administrador');
    }

    private function hasAnyRole(array $roles): bool
    {
        return backpack_user()->hasAnyRole($roles);
    }

    private function empresaIdsForUser(): array
    {
        return backpack_user()->empresas()->pluck('clientes.id')->all();
    }

    private function plantaIdsForUser(): array
    {
        return backpack_user()->plantas()->pluck('sucursals.id')->all();
    }

    private function allowedEmpresaIdsForCreate(): array
    {
        if ($this->isAdmin()) {
            return \App\Models\Cliente::pluck('id')->all();
        }

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            return $this->empresaIdsForUser();
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            return backpack_user()->plantas()->pluck('cliente_id')->unique()->values()->all();
        }

        return [];
    }

    private function allowedPlantaIdsForCreate(): array
    {
        if ($this->isAdmin()) {
            return \App\Models\Sucursal::pluck('id')->all();
        }

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = $this->empresaIdsForUser();
            return \App\Models\Sucursal::whereIn('cliente_id', $empresaIds ?: [0])->pluck('id')->all();
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            return $this->plantaIdsForUser();
        }

        return [];
    }

    private function ensureEmpresaPlantaWithinScope(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $empresaId = (int) request()->input('cliente_id');
        $plantaId = (int) request()->input('sucursal_id');

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $allowedEmpresas = $this->empresaIdsForUser();
            if (! in_array($empresaId, $allowedEmpresas, true)) {
                abort(403);
            }

            if (! \App\Models\Sucursal::where('id', $plantaId)->where('cliente_id', $empresaId)->exists()) {
                abort(403);
            }

            return;
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $allowedPlantas = $this->plantaIdsForUser();
            if (! in_array($plantaId, $allowedPlantas, true)) {
                abort(403);
            }

            return;
        }

        abort(403);
    }

    private function syncRetiroToHistories(?int $empleadoId, $fechaRetiro): void
    {
        if (! $empleadoId || ! $fechaRetiro) {
            return;
        }

        $retiro = Carbon::parse($fechaRetiro)->toDateString();

        EmpleadoCargo::query()
            ->where('empleado_id', $empleadoId)
            ->where('fecha_inicio', '<=', $retiro)
            ->where(function ($q) use ($retiro) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>', $retiro);
            })
            ->update(['fecha_fin' => $retiro]);

        EmpleadoArea::query()
            ->where('empleado_id', $empleadoId)
            ->where('fecha_inicio', '<=', $retiro)
            ->where(function ($q) use ($retiro) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>', $retiro);
            })
            ->update(['fecha_fin' => $retiro]);
    }

    private function ensureCanImport(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (! $this->crud->hasAccess('create')) {
            abort(403);
        }
    }

    private function canImportToScope(?int $clienteId, ?int $sucursalId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            return in_array($clienteId, $this->empresaIdsForUser(), true);
        }

        if ($this->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            return in_array($sucursalId, $this->plantaIdsForUser(), true);
        }

        return false;
    }

    private function resolveEmpresaPlanta(array $payload): array
    {
        $empresaNombre = trim((string) ($payload['empresa'] ?? ''));
        $plantaNombre = trim((string) ($payload['planta'] ?? ''));

        if ($empresaNombre !== '') {
            $cliente = Cliente::where('nombre', $empresaNombre)->first();
            if (! $cliente) {
                $cliente = $this->matchClienteByNormalizedName($empresaNombre);
            }

            if ($cliente) {
                if ($plantaNombre !== '') {
                    $sucursal = Sucursal::where('cliente_id', $cliente->id)
                        ->where('nombre', $plantaNombre)
                        ->first();
                } else {
                    $sucursal = null;
                }

                if (! $sucursal) {
                    $sucursal = Sucursal::firstOrCreate(
                        ['nombre' => $plantaNombre !== '' ? $plantaNombre : 'SIN PLANTA', 'cliente_id' => $cliente->id],
                        ['direccion' => '']
                    );
                }

                return [$cliente->id, $sucursal->id];
            }
        }

        if ($plantaNombre !== '') {
            $sucursal = Sucursal::where('nombre', $plantaNombre)->first();
            if ($sucursal) {
                return [$sucursal->cliente_id, $sucursal->id];
            }
        }

        $cliente = Cliente::firstOrCreate(
            ['nombre' => 'SIN EMPRESA'],
            ['nit' => '0', 'codigo' => 'SIN-EMPRESA']
        );

        $sucursal = Sucursal::firstOrCreate(
            ['nombre' => $plantaNombre !== '' ? $plantaNombre : 'SIN PLANTA', 'cliente_id' => $cliente->id],
            ['direccion' => '']
        );

        return [$cliente->id, $sucursal->id];
    }

    private function matchClienteByNormalizedName(string $empresaNombre): ?Cliente
    {
        $target = $this->normalizeEmpresaName($empresaNombre);
        if ($target === '') {
            return null;
        }

        $clientes = Cliente::all();

        $exact = $clientes->first(function ($cliente) use ($target) {
            return $this->normalizeEmpresaName((string) $cliente->nombre) === $target;
        });

        if ($exact) {
            return $exact;
        }

        return $clientes->first(function ($cliente) use ($target) {
            $name = $this->normalizeEmpresaName((string) $cliente->nombre);
            if ($name === '') {
                return false;
            }

            return str_contains($name, $target) || str_contains($target, $name);
        });
    }

    private function normalizeEmpresaName(string $name): string
    {
        $name = mb_strtolower($name);
        $name = str_replace(['.', ',', '  '], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        $suffixes = [
            ' s a s',
            ' sas',
            ' s a',
            ' sa',
            ' ltda',
            ' s en c',
            ' s de rl',
            ' s de r l',
            ' s a de c v',
            ' s a de cv',
            ' company',
            ' cia',
            ' compañía',
        ];

        foreach ($suffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                $name = trim(substr($name, 0, -strlen($suffix)));
                break;
            }
        }

        $name = preg_replace('/[^a-z0-9 ]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private function resolveHeaderIndexes(array $header): array
    {
        $map = array_flip($header);

        return [
            'empresa' => $map['empresa'] ?? $map['cliente'] ?? null,
            'planta' => $map['planta'] ?? $map['sucursal'] ?? null,
            'nombre' => $map['nombre'] ?? $map['empleado'] ?? null,
            'cedula' => $map['cedula'] ?? $map['cédula'] ?? null,
            'eps' => $map['eps'] ?? null,
            'arl' => $map['arl'] ?? null,
            'fondo_pensiones' => $map['fondo_pensiones'] ?? $map['fondo de pensiones'] ?? null,
            'cargo' => $map['cargo'] ?? null,
            'telefono' => $map['telefono'] ?? $map['teléfono'] ?? null,
            'direccion' => $map['direccion'] ?? $map['dirección'] ?? null,
            'correo' => $map['correo'] ?? $map['correo_electronico'] ?? $map['correo electronico'] ?? null,
            'tipo_contrato' => $map['tipo_contrato'] ?? $map['tipo de contrato'] ?? null,
            'lateralidad' => $map['lateralidad'] ?? null,
            'genero' => $map['genero'] ?? $map['género'] ?? null,
            'fecha_nacimiento' => $map['fecha_nacimiento'] ?? $map['fecha de nacimiento'] ?? null,
            'fecha_ingreso' => $map['fecha_ingreso'] ?? $map['fecha de ingreso'] ?? null,
            'fecha_retiro' => $map['fecha_retiro'] ?? $map['fecha de retiro'] ?? null,
        ];
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function computeEdadFromFecha(?string $fechaNacimiento): ?int
    {
        if (! $fechaNacimiento) {
            return null;
        }

        try {
            return Carbon::parse($fechaNacimiento)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function syncEdad(?Empleado $empleado): void
    {
        if (! $empleado) {
            return;
        }

        $fecha = null;
        if ($empleado->fecha_nacimiento) {
            try {
                $fecha = Carbon::parse($empleado->fecha_nacimiento)->format('Y-m-d');
            } catch (\Throwable $e) {
                $fecha = null;
            }
        }

        $edad = $this->computeEdadFromFecha($fecha);

        $empleado->edad = $edad;
        $empleado->save();
    }

    private function parseNumber($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $clean = preg_replace('/[^0-9]/', '', (string) $value);
        return $clean !== '' ? (int) $clean : null;
    }

    private function valueAt(array $row, ?int $index): ?string
    {
        if ($index === null) {
            return null;
        }

        $val = $row[$index] ?? null;
        if ($val === null) {
            return null;
        }

        $val = trim((string) $val);
        return $val === '' ? null : $val;
    }

    private function setIfNotNull(array &$data, string $key, $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $data[$key] = $value;
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (! file_exists($path)) {
            return $rows;
        }

        if (($handle = fopen($path, 'r')) === false) {
            return $rows;
        }

        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }

        fclose($handle);
        return $rows;
    }
}
