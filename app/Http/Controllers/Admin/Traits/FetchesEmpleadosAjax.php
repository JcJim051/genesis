<?php

namespace App\Http\Controllers\Admin\Traits;

use App\Models\Empleado;
use App\Support\TenantSelection;
use Illuminate\Http\Request;

trait FetchesEmpleadosAjax
{
    public function fetchEmpleado(Request $request)
    {
        $this->authorizeEmpleadoAjaxFetch();

        $term = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $query = $this->scopedEmpleadosForSelect2Query();

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', '%' . $term . '%')
                    ->orWhere('cedula', 'like', '%' . $term . '%');
            });
        }

        $paginator = $query->orderBy('nombre')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $paginator->getCollection()->map(fn (Empleado $empleado) => [
                'id' => $empleado->id,
                'text' => $empleado->selectLabelWithScope(),
                'cliente_id' => (int) $empleado->cliente_id,
            ])->values(),
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    protected function authorizeEmpleadoAjaxFetch(): void
    {
        if (! backpack_user()) {
            abort(403);
        }
    }

    protected function scopedEmpleadosForSelect2Query()
    {
        $query = Empleado::query()->with(['cliente', 'sucursal']);

        if (! TenantSelection::isAdminBypass()) {
            $this->applyScopeByFields($query, 'cliente_id', 'sucursal_id');
        }

        return $query;
    }

    protected function empleadoSelect2AjaxField(string $dataSource, array $overrides = []): array
    {
        return array_merge([
            'name' => 'empleado_id',
            'label' => 'Persona',
            'type' => 'select2_from_ajax',
            'entity' => 'empleado',
            'model' => Empleado::class,
            'attribute' => 'nombre',
            'data_source' => $dataSource,
            'placeholder' => 'Buscar persona por nombre o cédula...',
            'minimum_input_length' => 1,
            'hint' => 'Escribe el nombre o la cédula para buscar. Se respetan las empresas/plantas de tu vista actual.',
            'allows_null' => true,
        ], $overrides);
    }
}
