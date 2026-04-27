<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantScope;
use App\Http\Requests\EmpleadoCargoRequest;
use App\Models\Empleado;
use App\Models\EmpleadoCargo;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Prologue\Alerts\Facades\Alert;

class EmpleadoCargoCrudController extends CrudController
{
    use TenantScope;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; edit as traitEdit; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation { show as traitShow; }

    public function setup(): void
    {
        CRUD::setModel(EmpleadoCargo::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/empleado-cargo');
        CRUD::setEntityNameStrings('cargo', 'cargos');

        $this->scopeMode = 'empleado';
        $this->scopeRelation = 'empleado';
        $this->scopeModelClass = EmpleadoCargo::class;
        $this->applyTenantScope($this->crud);
    }

    protected function setupListOperation(): void
    {
        $this->crud->addButtonFromView('top', 'import', 'empleado_cargo_import', 'beginning');

        CRUD::addColumn([
            'name' => 'empleado',
            'type' => 'closure',
            'label' => 'Persona',
            'escaped' => false,
            'function' => function ($entry) {
                return \App\Support\EmpleadoLink::render($entry->empleado);
            },
        ]);
        CRUD::column('cargo');
        CRUD::column('fecha_inicio');
        CRUD::column('fecha_fin');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(EmpleadoCargoRequest::class);

        CRUD::field('empleado_id')
            ->type('select')
            ->label('Persona')
            ->entity('empleado')
            ->model(\App\Models\Empleado::class)
            ->attribute('nombre');

        CRUD::field('cargo')->type('text')->label('Cargo');
        CRUD::field('fecha_inicio')->type('date')->label('Fecha inicio');
        CRUD::field('fecha_fin')->type('date')->label('Fecha fin');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $request = $this->crud->getRequest();
        $empleadoId = $request->input('empleado_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        if ($empleadoId && $fechaInicio) {
            $nuevaFechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFinAnterior = $nuevaFechaInicio->copy()->subDay()->toDateString();

            $prev = EmpleadoCargo::query()
                ->where('empleado_id', $empleadoId)
                ->where('fecha_inicio', '<', $nuevaFechaInicio->toDateString())
                ->where(function ($q) use ($nuevaFechaInicio) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', $nuevaFechaInicio->toDateString());
                })
                ->orderByDesc('fecha_inicio')
                ->first();

            $ignoreId = null;
            if ($prev) {
                $prev->update(['fecha_fin' => $fechaFinAnterior]);
                $ignoreId = $prev->id;
            }

            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $ignoreId);
        } else {
            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, null);
        }

        return $this->traitStore();
    }

    public function update()
    {
        $this->enforceEntryScopeOrFail((int) $this->crud->getCurrentEntryId());
        $request = $this->crud->getRequest();
        $empleadoId = $request->input('empleado_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $entryId = $this->crud->getCurrentEntryId();

        if ($empleadoId && $fechaInicio) {
            $nuevaFechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFinAnterior = $nuevaFechaInicio->copy()->subDay()->toDateString();

            $prev = EmpleadoCargo::query()
                ->where('empleado_id', $empleadoId)
                ->where('id', '!=', $entryId)
                ->where('fecha_inicio', '<', $nuevaFechaInicio->toDateString())
                ->where(function ($q) use ($nuevaFechaInicio) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', $nuevaFechaInicio->toDateString());
                })
                ->orderByDesc('fecha_inicio')
                ->first();

            $ignoreId = null;
            if ($prev) {
                $prev->update(['fecha_fin' => $fechaFinAnterior]);
                $ignoreId = $prev->id;
            }

            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $ignoreId ? [$entryId, $ignoreId] : $entryId);
        } else {
            $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $entryId);
        }

        return $this->traitUpdate();
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

    public function importForm()
    {
        return view('admin.empleado_cargos.import');
    }

    public function import(Request $request)
    {
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

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNumber = $i + 2;
            $row = array_values($row);

            try {
                $cedula = trim((string) ($row[$idx['cedula']] ?? ''));
                $cargo = trim((string) ($row[$idx['cargo']] ?? ''));
                $fechaInicio = $this->parseExcelDate($row[$idx['fecha_inicio']] ?? null);
                $fechaFin = $this->parseExcelDate($row[$idx['fecha_fin']] ?? null);

                if ($cedula === '' || $cargo === '' || ! $fechaInicio) {
                    $errors[] = "Fila {$rowNumber}: CEDULA, CARGO y FECHA_INICIO son obligatorios.";
                    continue;
                }

                $empleado = Empleado::where('cedula', $cedula)->first();
                if (! $empleado) {
                    $errors[] = "Fila {$rowNumber}: No existe persona con cédula {$cedula}.";
                    continue;
                }

                $empleadoId = $empleado->id;
                $nuevaFechaInicio = Carbon::parse($fechaInicio)->startOfDay();
                $fechaFinAnterior = $nuevaFechaInicio->copy()->subDay()->toDateString();

                $prev = EmpleadoCargo::query()
                    ->where('empleado_id', $empleadoId)
                    ->where('fecha_inicio', '<', $nuevaFechaInicio->toDateString())
                    ->where(function ($q) use ($nuevaFechaInicio) {
                        $q->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', $nuevaFechaInicio->toDateString());
                    })
                    ->orderByDesc('fecha_inicio')
                    ->first();

                $ignoreId = null;
                if ($prev) {
                    $prev->update(['fecha_fin' => $fechaFinAnterior]);
                    $ignoreId = $prev->id;
                }

                $this->validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $ignoreId);

                $cargoEntry = EmpleadoCargo::updateOrCreate(
                    [
                        'empleado_id' => $empleadoId,
                        'fecha_inicio' => $fechaInicio,
                    ],
                    [
                        'cargo' => $cargo,
                        'fecha_fin' => $fechaFin,
                    ]
                );

                if ($cargoEntry->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }

        $summary = "Importados: " . ($created + $updated) . ". Nuevos: {$created}. Actualizados: {$updated}.";
        Alert::add('success', $summary)->flash();

        if (! empty($errors)) {
            $preview = array_slice($errors, 0, 20);
            $more = count($errors) > 20 ? ('<br>... y ' . (count($errors) - 20) . ' más.') : '';
            Alert::add('warning', "Errores de importación:<br>" . implode('<br>', $preview) . $more)->flash();
        }

        return redirect(backpack_url('empleado-cargo'));
    }

    public function template()
    {
        $headers = [
            'CEDULA',
            'CARGO',
            'FECHA_INICIO',
            'FECHA_FIN',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $writer = new Xlsx($spreadsheet);

        $tmp = tempnam(sys_get_temp_dir(), 'empleado_cargos_template_');
        $writer->save($tmp);

        return response()->download($tmp, 'plantilla_cargos.xlsx')->deleteFileAfterSend(true);
    }

    private function validateNoOverlap($empleadoId, $fechaInicio, $fechaFin, $ignoreId): void
    {
        if (! $empleadoId || ! $fechaInicio) {
            return;
        }

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        if ($fin && $fin->lt($inicio)) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha fin no puede ser anterior a la fecha inicio.',
            ]);
        }

        $endDate = $fin ? $fin->toDateString() : '9999-12-31';

        $overlapQuery = EmpleadoCargo::query()
            ->where('empleado_id', $empleadoId)
            ->when($ignoreId, function ($q) use ($ignoreId) {
                if (is_array($ignoreId)) {
                    $q->whereNotIn('id', $ignoreId);
                } else {
                    $q->where('id', '!=', $ignoreId);
                }
            })
            ->where('fecha_inicio', '<=', $endDate)
            ->where(function ($q) use ($inicio) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $inicio->toDateString());
            });

        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'El rango de fechas se cruza con otro cargo existente para esta persona.',
            ]);
        }
    }

    private function resolveHeaderIndexes(array $header): array
    {
        $map = array_flip($header);

        return [
            'cedula' => $map['cedula'] ?? $map['cédula'] ?? null,
            'cargo' => $map['cargo'] ?? null,
            'fecha_inicio' => $map['fecha_inicio'] ?? $map['fecha inicio'] ?? null,
            'fecha_fin' => $map['fecha_fin'] ?? $map['fecha fin'] ?? null,
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
