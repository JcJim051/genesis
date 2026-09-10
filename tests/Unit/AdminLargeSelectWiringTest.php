<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminLargeSelectWiringTest extends TestCase
{
    public function test_osteo_create_manual_persona_uses_select2_ajax_without_preloading_empleados(): void
    {
        $controller = $this->read('app/Http/Controllers/Admin/OsteoEvaluationCrudController.php');
        $view = $this->read('resources/views/admin/osteo_evaluations/create_manual.blade.php');
        $partial = $this->read('resources/views/admin/partials/empleado_select2_ajax.blade.php');
        $assets = $this->read('resources/views/admin/partials/empleado_select2_ajax_assets.blade.php');
        $routes = $this->read('routes/backpack/custom.php');

        $this->assertStringContainsString('use FetchesEmpleadosAjax', $controller);
        $this->assertStringContainsString('function createManual', $controller);
        $this->assertStringNotContainsString('limit(500)', $controller);
        $this->assertStringNotContainsString("'empleados' => \$empleados", $controller);

        $this->assertStringContainsString("backpack_url('osteo-evaluation/fetch/empleado')", $view);
        $this->assertStringContainsString('admin.partials.empleado_select2_ajax', $view);
        $this->assertStringContainsString('admin.partials.empleado_select2_ajax_assets', $view);
        $this->assertStringNotContainsString('@foreach($empleados', $view);

        $this->assertStringContainsString('js-empleado-select2-ajax', $partial);
        $this->assertStringContainsString('data-ajax-url', $partial);
        $this->assertStringContainsString('selectLabelWithScope', $partial);

        $this->assertStringContainsString('function initEmpleadoSelect2Ajax', $assets);
        $this->assertStringContainsString('q: params.term', $assets);
        $this->assertStringContainsString('minimumInputLength', $assets);
        $this->assertStringContainsString('No se encontraron personas', $assets);

        $this->assertStringContainsString("osteo-evaluation/fetch/empleado", $routes);
        $this->assertStringContainsString('OsteoEvaluationCrudController::class, \'fetchEmpleado\'', $routes);
    }

    public function test_ipt_create_manual_persona_uses_select2_ajax_and_keeps_template_filter(): void
    {
        $controller = $this->read('app/Http/Controllers/Admin/IptInspectionCrudController.php');
        $view = $this->read('resources/views/admin/ipt_inspections/create_manual.blade.php');
        $routes = $this->read('routes/backpack/custom.php');

        $this->assertStringContainsString('use FetchesEmpleadosAjax', $controller);
        $this->assertStringNotContainsString("'empleados' => \$empleados", $controller);
        $this->assertStringContainsString("'templatePool' => \$templatePool", $controller);

        $this->assertStringContainsString("backpack_url('ipt-inspection/fetch/empleado')", $view);
        $this->assertStringContainsString('admin.partials.empleado_select2_ajax', $view);
        $this->assertStringContainsString('admin.partials.empleado_select2_ajax_assets', $view);
        $this->assertStringNotContainsString('@foreach($empleados', $view);
        $this->assertStringContainsString('data-cliente-id', $view);
        $this->assertStringContainsString('refreshTemplatesByEmployee', $view);
        $this->assertStringContainsString('cliente_id', $view);

        $this->assertStringContainsString("ipt-inspection/fetch/empleado", $routes);
        $this->assertStringContainsString('IptInspectionCrudController::class, \'fetchEmpleado\'', $routes);
    }

    public function test_empleado_cargo_and_area_persona_fields_use_select2_ajax(): void
    {
        $cargo = $this->read('app/Http/Controllers/Admin/EmpleadoCargoCrudController.php');
        $area = $this->read('app/Http/Controllers/Admin/EmpleadoAreaCrudController.php');
        $routes = $this->read('routes/backpack/custom.php');

        foreach ([$cargo, $area] as $controller) {
            $this->assertStringContainsString('use FetchesEmpleadosAjax', $controller);
            $this->assertStringContainsString('empleadoSelect2AjaxField', $controller);
            $this->assertStringContainsString("hasAccess('create')", $controller);
            $this->assertStringContainsString("hasAccess('update')", $controller);
            $this->assertStringNotContainsString("->type('select')", $controller);
        }

        $this->assertStringContainsString("backpack_url('empleado-cargo/fetch/empleado')", $cargo);
        $this->assertStringContainsString("backpack_url('empleado-area/fetch/empleado')", $area);
        $this->assertStringContainsString("empleado-cargo/fetch/empleado", $routes);
        $this->assertStringContainsString("empleado-area/fetch/empleado", $routes);
    }

    public function test_cie10_diagnostico_map_uses_select2_ajax_instead_of_dumping_catalog(): void
    {
        $controller = $this->read('app/Http/Controllers/Admin/DiagnosticoProgramaMapCrudController.php');
        $lookup = $this->read('app/Http/Controllers/Admin/Cie10LookupController.php');
        $field = $this->read('resources/views/vendor/backpack/crud/fields/cie10_select.blade.php');
        $model = $this->read('app/Models/Cie10.php');
        $routes = $this->read('routes/backpack/custom.php');

        $this->assertStringContainsString("'type' => 'cie10_select'", $controller);
        $this->assertStringContainsString("backpack_url('cie10/fetch')", $controller);
        $this->assertStringNotContainsString('Cie10::query()', $controller);
        $this->assertStringNotContainsString("->get()\n                ->mapWithKeys", $controller);

        $this->assertStringContainsString('function fetch', $lookup);
        $this->assertStringContainsString("where('codigo', 'like'", $lookup);
        $this->assertStringContainsString("orWhere('diagnostico', 'like'", $lookup);
        $this->assertStringContainsString('$cie10->selectLabel()', $lookup);

        $this->assertStringContainsString('data-ajax-url', $field);
        $this->assertStringContainsString('minimumInputLength', $field);
        $this->assertStringContainsString('q: params.term', $field);
        $this->assertStringContainsString('No se encontraron códigos CIE10', $field);
        $this->assertStringNotContainsString('@foreach ($options as $key => $value)', $field);

        $this->assertStringContainsString('function selectLabel(): string', $model);
        $this->assertStringContainsString("cie10/fetch", $routes);
    }

    public function test_empresa_and_related_admin_selects_are_searchable_select2(): void
    {
        $empleado = $this->read('app/Http/Controllers/Admin/EmpleadoCrudController.php');
        $sucursal = $this->read('app/Http/Controllers/Admin/SucursalCrudController.php');
        $encuestaEnvio = $this->read('app/Http/Controllers/Admin/EncuestaEnvioCrudController.php');
        $pausaEnvio = $this->read('app/Http/Controllers/Admin/PausaEnvioCrudController.php');
        $osteoBuilder = $this->read('resources/views/admin/osteo_templates/builder.blade.php');
        $encuestaBuilder = $this->read('resources/views/admin/encuestas/builder.blade.php');
        $pausaBuilder = $this->read('resources/views/admin/pausas/builder.blade.php');

        $this->assertStringContainsString("'type' => 'select2'", $empleado);
        $this->assertStringContainsString("'label' => 'Empresa'", $empleado);
        $this->assertStringContainsString("'label' => 'Planta'", $empleado);
        $this->assertStringContainsString("->type('select2')", $sucursal);
        $this->assertStringContainsString("->type('select2')", $encuestaEnvio);
        $this->assertStringContainsString("->type('select2')", $pausaEnvio);
        $this->assertStringContainsString('js-select2-companies', $osteoBuilder);
        $this->assertStringContainsString('js-select2-searchable', $encuestaBuilder);
        $this->assertStringContainsString('js-select2-searchable', $pausaBuilder);
    }

    public function test_shared_empleado_ajax_trait_returns_cliente_id_for_dependent_filters(): void
    {
        $trait = $this->read('app/Http/Controllers/Admin/Traits/FetchesEmpleadosAjax.php');

        $this->assertStringContainsString("'cliente_id' => (int) \$empleado->cliente_id", $trait);
        $this->assertStringContainsString('with([\'cliente\', \'sucursal\'])', $trait);
        $this->assertStringContainsString('paginate($perPage', $trait);
    }

    private function read(string $relative): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $relative);
        $this->assertIsString($contents, $relative . ' should be readable');

        return $contents;
    }
}
