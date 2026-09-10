<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProgramaCasoPersonaSelectWiringTest extends TestCase
{
    public function test_programa_caso_persona_field_uses_searchable_select2_ajax(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/ProgramaCasoCrudController.php');
        $trait = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/Traits/FetchesEmpleadosAjax.php');
        $routes = file_get_contents(dirname(__DIR__, 2) . '/routes/backpack/custom.php');
        $field = file_get_contents(dirname(__DIR__, 2) . '/resources/views/vendor/backpack/crud/fields/select2_from_ajax.blade.php');
        $empleado = file_get_contents(dirname(__DIR__, 2) . '/app/Models/Empleado.php');

        $this->assertIsString($controller);
        $this->assertIsString($trait);
        $this->assertIsString($routes);
        $this->assertIsString($field);
        $this->assertIsString($empleado);

        $this->assertStringContainsString('use FetchesEmpleadosAjax', $controller);
        $this->assertStringContainsString('empleadoSelect2AjaxField(backpack_url(\'programa-caso/fetch/empleado\'))', $controller);
        $this->assertStringContainsString('function authorizeEmpleadoAjaxFetch', $controller);
        $this->assertStringContainsString("hasAccess('create')", $controller);
        $this->assertStringContainsString("hasAccess('update')", $controller);

        $this->assertStringContainsString("'type' => 'select2_from_ajax'", $trait);
        $this->assertStringContainsString("'label' => 'Persona'", $trait);
        $this->assertStringContainsString('Buscar persona por nombre o cédula', $trait);
        $this->assertStringContainsString('function fetchEmpleado', $trait);
        $this->assertStringContainsString("where('nombre', 'like'", $trait);
        $this->assertStringContainsString("orWhere('cedula', 'like'", $trait);
        $this->assertStringContainsString('scopedEmpleadosForSelect2Query', $trait);
        $this->assertStringContainsString('applyScopeByFields($query, \'cliente_id\', \'sucursal_id\')', $trait);
        $this->assertStringContainsString('TenantSelection::isAdminBypass()', $trait);
        $this->assertStringContainsString('$empleado->selectLabelWithScope()', $trait);

        $this->assertStringContainsString('function selectLabel', $empleado);
        $this->assertStringContainsString('function selectLabelWithScope', $empleado);

        $this->assertStringContainsString("programa-caso/fetch/empleado", $routes);
        $this->assertStringContainsString('fetchEmpleado', $routes);

        $this->assertStringContainsString('bpFieldInitSelect2FromAjaxElement', $field);
        $this->assertStringContainsString('data-ajax-url', $field);
        $this->assertStringContainsString('minimumInputLength', $field);
        $this->assertStringContainsString('ajax:', $field);
        $this->assertStringContainsString('q: params.term', $field);
        $this->assertStringContainsString('No se encontraron personas', $field);
        $this->assertStringContainsString('select2.min.js', $field);
        $this->assertStringContainsString('selectLabelWithScope', $field);

        $this->assertStringNotContainsString("field('empleado_id')", $controller);
    }

    public function test_empleado_select_label_joins_nombre_and_cedula(): void
    {
        $empleado = file_get_contents(dirname(__DIR__, 2) . '/app/Models/Empleado.php');
        $this->assertIsString($empleado);
        $this->assertStringContainsString('function selectLabel(): string', $empleado);
        $this->assertStringContainsString('$this->nombre', $empleado);
        $this->assertStringContainsString('$this->cedula', $empleado);
        $this->assertStringContainsString("implode(' · '", $empleado);
        $this->assertStringContainsString('function selectLabelWithScope(): string', $empleado);
        $this->assertStringContainsString('$this->cliente?->nombre', $empleado);
        $this->assertStringContainsString('$this->sucursal?->nombre', $empleado);
    }
}
