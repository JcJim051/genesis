<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Models\DiagnosticoProgramaMap;
use App\Models\Empleado;
use App\Models\Incapacidad;
use App\Models\Programa;
use App\Models\ProgramaCaso;
use App\Models\Cliente;
use App\Models\Sucursal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Prologue\Alerts\Facades\Alert;

class IncapacidadCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; edit as traitEdit; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(Incapacidad::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/incapacidad');
        CRUD::setEntityNameStrings('incapacidad', 'incapacidades');
        $this->applyAccessRules();

        $this->scopeMode = 'fields';
        $this->scopeEmpresaField = 'cliente_id';
        $this->scopePlantaField = 'sucursal_id';
        $this->scopeModelClass = Incapacidad::class;
    }

    protected function setupListOperation(): void
    {
        $this->applyListScope();

        $this->crud->addButtonFromView('top', 'reprocess', 'incapacidad_reprocess', 'beginning');
        $this->crud->addButtonFromView('top', 'import', 'incapacidad_import', 'beginning');

        CRUD::addColumn([
            'name' => 'empresa_nombre',
            'type' => 'closure',
            'label' => 'Empresa',
            'function' => fn ($entry) => optional($entry->cliente)->nombre,
        ]);
        CRUD::addColumn([
            'name' => 'planta_nombre',
            'type' => 'closure',
            'label' => 'Planta',
            'function' => fn ($entry) => optional($entry->sucursal)->nombre,
        ]);
        CRUD::column('cedula');
        CRUD::column('fecha_inicio');
        CRUD::column('fecha_fin');
        CRUD::column('diagnostico');
        CRUD::column('codigo_cie10');
        CRUD::column('origen');
        CRUD::column('dias_incapacidad');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('cedula')->type('text')->label('Cédula');
        CRUD::field('fecha_inicio')->type('date')->label('Fecha inicio');
        CRUD::field('fecha_fin')->type('date')->label('Fecha fin');
        CRUD::field('diagnostico')->type('text')->label('Diagnóstico');
        CRUD::field('codigo_cie10')->type('text')->label('CIE10');
        CRUD::field('origen')->type('text')->label('Origen');
        CRUD::field('dias_incapacidad')->type('number')->label('Días incapacidad');
        CRUD::field('payload')->type('textarea')->label('Payload (JSON)')->hint('Opcional');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation(): void
    {
        $this->applyListScope();

        CRUD::addColumn([
            'name' => 'empresa_nombre',
            'type' => 'closure',
            'label' => 'Empresa',
            'function' => fn ($entry) => optional($entry->cliente)->nombre,
        ]);
        CRUD::addColumn([
            'name' => 'planta_nombre',
            'type' => 'closure',
            'label' => 'Planta',
            'function' => fn ($entry) => optional($entry->sucursal)->nombre,
        ]);
        CRUD::column('cedula');
        CRUD::column('fecha_inicio');
        CRUD::column('fecha_fin');
        CRUD::column('diagnostico');
        CRUD::column('codigo_cie10');
        CRUD::column('origen');
        CRUD::column('dias_incapacidad');
        CRUD::column('payload')->type('json')->label('Payload');
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

    public function store()
    {
        $response = $this->traitStore();
        $this->applyProgramaSuggestionFor($this->crud->entry);
        return $response;
    }

    public function importForm()
    {
        $this->ensureCanImport();
        return view('admin.incapacidad.import');
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
            $sheet = $spreadsheet->getSheetByName('INCAPACIDADES') ?? $spreadsheet->getActiveSheet();
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

        $count = 0;
        $errors = [];
        $okRows = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNumber = $i + 2;
            $row = array_values($row);

            try {
                $cedula = trim((string) ($row[$idx['cedula']] ?? ''));
                if ($cedula === '') {
                    $errors[] = "Fila {$rowNumber}: CÉDULA vacía.";
                    continue;
                }

                $fechaInicio = $this->parseExcelDate($row[$idx['fecha_inicio']] ?? null);
                $fechaFin = $this->parseExcelDate($row[$idx['fecha_fin']] ?? null);

                $codigo = trim((string) ($row[$idx['codigo_cie10']] ?? ''));
                $diagnostico = trim((string) ($row[$idx['diagnostico_texto']] ?? ''));
                $origen = trim((string) ($row[$idx['origen']] ?? ''));
                $dias = $this->parseNumber($row[$idx['dias']] ?? null);

                $payload = [
                    'empleado' => $this->valueAt($row, $idx['empleado']),
                    'entidad' => $this->valueAt($row, $idx['entidad']),
                    'fecha_inicial_real' => $this->parseExcelDate($row[$idx['fecha_inicio_real']] ?? null),
                    'fecha_fin_real' => $this->parseExcelDate($row[$idx['fecha_fin_real']] ?? null),
                    'valor' => $this->valueAt($row, $idx['valor']),
                    'observacion' => $this->valueAt($row, $idx['observacion']),
                    'planta' => $this->valueAt($row, $idx['planta']),
                    'cruce_tu_recobro' => $this->valueAt($row, $idx['cruce_tu_recobro']),
                    'cruce_axa_colpatria' => $this->valueAt($row, $idx['cruce_axa_colpatria']),
                    'diagnostico_texto' => $this->valueAt($row, $idx['diagnostico_texto']),
                ];

                $empleado = Empleado::where('cedula', $cedula)->first();
                if (! $empleado) {
                    [$clienteId, $sucursalId] = $this->resolveEmpresaPlanta($payload);
                    $empleado = Empleado::create([
                        'cliente_id' => $clienteId,
                        'sucursal_id' => $sucursalId,
                        'nombre' => $payload['empleado'] ?? ('SIN NOMBRE ' . $cedula),
                        'cedula' => $cedula,
                    ]);
                }

                $clienteId = $empleado?->cliente_id;
                $sucursalId = $empleado?->sucursal_id;

                if ($fechaInicio && $fechaFin) {
                    $incapacidad = Incapacidad::updateOrCreate(
                        [
                            'cedula' => $cedula,
                            'fecha_inicio' => $fechaInicio,
                            'fecha_fin' => $fechaFin,
                        ],
                        [
                            'cliente_id' => $clienteId,
                            'sucursal_id' => $sucursalId,
                            'diagnostico' => $diagnostico,
                            'codigo_cie10' => $codigo,
                            'origen' => $origen,
                            'dias_incapacidad' => $dias,
                            'payload' => $payload,
                        ]
                    );
                } else {
                    $incapacidad = Incapacidad::create([
                        'cedula' => $cedula,
                        'cliente_id' => $clienteId,
                        'sucursal_id' => $sucursalId,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                        'diagnostico' => $diagnostico,
                        'codigo_cie10' => $codigo,
                        'origen' => $origen,
                        'dias_incapacidad' => $dias,
                        'payload' => $payload,
                    ]);
                }

                $this->applyProgramaSuggestionFor($incapacidad);
                $count++;
                $okRows[] = $rowNumber;
            } catch (\Throwable $e) {
                $errors[] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }

        Alert::add('success', "Importadas: {$count} incapacidades.")->flash();

        if (! empty($okRows)) {
            $preview = array_slice($okRows, 0, 20);
            $more = count($okRows) > 20 ? ('<br>... y ' . (count($okRows) - 20) . ' más.') : '';
            Alert::add('info', "Filas OK: " . implode(', ', $preview) . $more)->flash();
        }

        if (! empty($errors)) {
            $preview = array_slice($errors, 0, 20);
            $more = count($errors) > 20 ? ('<br>... y ' . (count($errors) - 20) . ' más.') : '';
            Alert::add('warning', "Errores de importación:<br>" . implode('<br>', $preview) . $more)->flash();
        }

        return redirect(backpack_url('incapacidad'));
    }

    public function template()
    {
        $this->ensureCanImport();

        $headers = [
            'CEDULA',
            'EMPLEADO',
            'ENTIDAD',
            'FECHA INICIAL',
            'FECHA FIN',
            'FECHA INICIAL REAL',
            'FECHA FIN REAL',
            'DIAS',
            'TIPO AUSENCIA',
            'DIAGNOSTICO',
            'DIAGNOSTICO_TEXTO',
            'VALOR',
            'OBSERVACION',
            'Planta',
            'CRUCE TU RECOBRO',
            'CRUCE AXA COLPATRIA',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $writer = new Xlsx($spreadsheet);

        $tmp = tempnam(sys_get_temp_dir(), 'incapacidad_template_');
        $writer->save($tmp);

        return response()->download($tmp, 'plantilla_incapacidades.xlsx')->deleteFileAfterSend(true);
    }

    public function reprocess()
    {
        $this->ensureCanImport();

        $total = 0;
        $updated = 0;

        Incapacidad::query()->chunk(200, function ($rows) use (&$total, &$updated) {
            foreach ($rows as $inc) {
                $before = ProgramaCaso::where('empleado_id', optional(Empleado::where('cedula', $inc->cedula)->first())->id)
                    ->where('origen', 'incapacidad')
                    ->count();

                $this->applyProgramaSuggestionFor($inc);

                $after = ProgramaCaso::where('empleado_id', optional(Empleado::where('cedula', $inc->cedula)->first())->id)
                    ->where('origen', 'incapacidad')
                    ->count();

                $total++;
                if ($after > $before) {
                    $updated++;
                }
            }
        });

        Alert::add('success', "Reprocesadas {$total} incapacidades. Casos nuevos: {$updated}.")->flash();
        return redirect(backpack_url('incapacidad'));
    }

    private function applyProgramaSuggestionFor(?Incapacidad $entry): void
    {
        if (! $entry) {
            return;
        }

        $empleado = Empleado::where('cedula', $entry->cedula)->first();
        if (! $empleado) {
            return;
        }

        $map = DiagnosticoProgramaMap::query()
            ->where('regla_activa', true)
            ->when($entry->codigo_cie10, function ($q) use ($entry) {
                // Allow wildcard rules stored in DB (e.g. M%, H0%)
                $q->whereRaw('? like codigo_cie10', [$entry->codigo_cie10]);
            })
            ->when(! $entry->codigo_cie10 && $entry->diagnostico, function ($q) use ($entry) {
                $q->where('diagnostico_texto', 'like', '%' . $entry->diagnostico . '%');
            })
            ->orderByDesc('prioridad')
            ->first();

        if (! $map) {
            $programa = $this->resolveProgramByCie10($entry->codigo_cie10);
            if (! $programa) {
                return;
            }

            $caso = ProgramaCaso::updateOrCreate(
                [
                    'empleado_id' => $empleado->id,
                    'programa_id' => $programa->id,
                ],
                [
                    'estado' => 'No evaluado',
                    'origen' => 'incapacidad',
                    'sugerido_por' => 'cie10_general',
                ]
            );

            $caso->incapacidades()->syncWithoutDetaching([$entry->id]);
            return;
        }

        $caso = ProgramaCaso::updateOrCreate(
            [
                'empleado_id' => $empleado->id,
                'programa_id' => $map->programa_id,
            ],
            [
                'estado' => 'No evaluado',
                'origen' => 'incapacidad',
                'sugerido_por' => 'diagnostico',
            ]
        );

        $caso->incapacidades()->syncWithoutDetaching([$entry->id]);
    }

    private function resolveEmpresaPlanta(array $payload): array
    {
        $plantaNombre = trim((string) ($payload['planta'] ?? ''));

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

    private function resolveProgramByCie10(?string $codigo): ?Programa
    {
        if (! $codigo) {
            return null;
        }

        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return null;
        }

        $letra = $codigo[0];
        $slug = null;

        if ($letra === 'M' || $letra === 'S' || $letra === 'T') {
            $slug = 'osteomuscular';
        } elseif ($letra === 'F') {
            $slug = 'psicosocial';
        } elseif ($letra === 'I') {
            $slug = 'cardiovascular';
        } elseif ($letra === 'H') {
            $second = $codigo[1] ?? '';
            if (in_array($second, ['0','1','2','3','4','5'], true)) {
                $slug = 'visual';
            } elseif (in_array($second, ['6','7','8','9'], true)) {
                $slug = 'auditivo';
            }
        }

        if (! $slug) {
            return null;
        }

        return Programa::where('slug', $slug)->first();
    }

    private function ensureCanImport(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (\App\Support\TenantSelection::isPlatformAdmin()) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general'])) {
            return;
        }

        abort(403);
    }

    private function applyAccessRules(): void
    {
        if (! backpack_user()) {
            abort(403);
        }

        if (\App\Support\TenantSelection::isPlatformAdmin()) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general'])) {
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo general', 'Asesor externo planta'])) {
            $this->crud->denyAccess(['create', 'update', 'delete']);
            return;
        }

        $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
    }

    private function applyListScope(): void
    {
        if (! backpack_user()) {
            return;
        }

        if (\App\Support\TenantSelection::isAdminBypass()) {
            return;
        }

        if (\App\Support\TenantSelection::isPlatformAdmin()) {
            $plantaIds = \App\Support\TenantSelection::plantaIds();
            if (! empty($plantaIds)) {
                $this->crud->addClause('whereIn', 'sucursal_id', $plantaIds);
                return;
            }

            $empresaIds = \App\Support\TenantSelection::empresaIds();
            if (\App\Support\TenantSelection::selectedEmpresaIncludesUnassigned()) {
                $this->crud->addClause(function ($q) use ($empresaIds) {
                    $q->whereIn('cliente_id', $empresaIds ?: [0])
                        ->orWhereNull('cliente_id')
                        ->orWhere('cliente_id', 0);
                });
                return;
            }

            $this->crud->addClause('whereIn', 'cliente_id', $empresaIds ?: [0]);
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador general', 'Asesor externo general'])) {
            $empresaIds = \App\Support\TenantSelection::empresaIds();
            if (\App\Support\TenantSelection::selectedEmpresaIncludesUnassigned()) {
                $this->crud->addClause(function ($q) use ($empresaIds) {
                    $q->whereIn('cliente_id', $empresaIds ?: [0])
                        ->orWhereNull('cliente_id')
                        ->orWhere('cliente_id', 0);
                });
            } else {
                $this->crud->addClause('whereIn', 'cliente_id', $empresaIds ?: [0]);
            }
            return;
        }

        if (backpack_user()->hasAnyRole(['Coordinador de planta', 'Asesor externo planta'])) {
            $plantaIds = \App\Support\TenantSelection::plantaIds();
            $this->crud->addClause('whereIn', 'sucursal_id', $plantaIds ?: [0]);
            return;
        }

        $this->crud->addClause('whereRaw', '1 = 0');
    }

    private function resolveHeaderIndexes(array $header): array
    {
        $find = function (array $names) use ($header) {
            foreach ($names as $name) {
                $idx = array_search($name, $header, true);
                if ($idx !== false) {
                    return $idx;
                }
            }
            return null;
        };

        $idx = [
            'cedula' => $find(['cedula']),
            'empleado' => $find(['empleado']),
            'entidad' => $find(['entidad']),
            'fecha_inicio' => $find(['fecha inicial']),
            'fecha_fin' => $find(['fecha fin']),
            'fecha_inicio_real' => $find(['fecha inicial real']),
            'fecha_fin_real' => $find(['fecha fin real']),
            'dias' => $find(['dias']),
            'origen' => $find(['tipo ausencia']),
            'codigo_cie10' => $find(['diagnostico']),
            'diagnostico_texto' => $find(['diagnostico texto', 'diagnostico_texto']),
            'valor' => $find(['valor']),
            'observacion' => $find(['observacion', 'obesrvacion']),
            'planta' => $find(['planta']),
            'cruce_tu_recobro' => $find(['cruce tu recobro']),
            'cruce_axa_colpatria' => $find(['cruce axa colpatria']),
        ];

        if ($idx['diagnostico_texto'] === null && $idx['codigo_cie10'] !== null) {
            $next = $idx['codigo_cie10'] + 1;
            if (array_key_exists($next, $header) && ($header[$next] === '' || $header[$next] === null)) {
                $idx['diagnostico_texto'] = $next;
            }
        }

        return $idx;
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseNumber($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }

    private function valueAt(array $row, ?int $idx): ?string
    {
        if ($idx === null) {
            return null;
        }
        $value = $row[$idx] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        return trim((string) $value);
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (! $handle) {
            return $rows;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return $rows;
        }

        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        $rows[] = str_getcsv($firstLine, $delimiter);

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $data;
        }

        fclose($handle);
        return $rows;
    }
}
