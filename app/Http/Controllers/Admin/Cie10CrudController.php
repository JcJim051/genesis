<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cie10;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class Cie10CrudController extends CrudController
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

        CRUD::setModel(Cie10::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cie10');
        CRUD::setEntityNameStrings('CIE10', 'CIE10');
    }

    protected function setupListOperation(): void
    {
        $this->crud->addButtonFromView('top', 'import', 'cie10_import', 'beginning');

        CRUD::column('codigo')->label('CIE10');
        CRUD::column('diagnostico')->label('Diagnóstico');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('codigo')->type('text')->label('CIE10');
        CRUD::field('diagnostico')->type('text')->label('Diagnóstico');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    public function importForm()
    {
        return view('admin.cie10.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $file = $request->file('archivo');
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if ($ext === 'xlsx') {
            $rows = Excel::toArray([], $file)[0] ?? [];
        } else {
            $rows = $this->parseCsv($file->getRealPath());
        }

        if (count($rows) < 2) {
            return back()->withErrors(['archivo' => 'El archivo no contiene filas válidas.']);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $codigoIndex = array_search('codigo', $header, true);
        $diagnosticoIndex = array_search('diagnostico', $header, true);

        if ($codigoIndex === false || $diagnosticoIndex === false) {
            return back()->withErrors(['archivo' => 'El archivo debe tener columnas: codigo, diagnostico.']);
        }

        $count = 0;
        foreach (array_slice($rows, 1) as $row) {
            $codigo = trim((string) ($row[$codigoIndex] ?? ''));
            $diagnostico = trim((string) ($row[$diagnosticoIndex] ?? ''));
            if ($codigo === '' || $diagnostico === '') {
                continue;
            }
            Cie10::updateOrCreate(
                ['codigo' => $codigo],
                ['diagnostico' => $diagnostico]
            );
            $count++;
        }

        return redirect(backpack_url('cie10'))->with('success', "Importados: {$count} registros.");
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
