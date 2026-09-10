<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProgramaCasoPersonaSelectWiringTest extends TestCase
{
    public function test_programa_caso_persona_field_uses_searchable_select2_ajax(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/ProgramaCasoCrudController.php');
        $routes = file_get_contents(dirname(__DIR__, 2) . '/routes/backpack/custom.php');
        $field = file_get_contents(dirname(__DIR__, 2) . '/resources/views/vendor/backpack/crud/fields/select2_from_ajax.blade.php');
        $empleado = file_get_contents(dirname(__DIR__, 2) . '/app/Models/Empleado.php');

        $this->assertIsString($controller);
        $this->assertIsString($routes);
        $this->assertIsString($field);
        $this->assertIsString($empleado);

        $this->assertStringContainsString("'type' => 'select2_from_ajax'", $controller);
        $this->assertStringContainsString("'label' => 'Persona'", $controller);
        $this->assertStringContainsString("backpack_url('programa-caso/fetch/empleado')", $controller);
        $this->assertStringContainsString('Buscar persona por nombre o cédula', $controller);
        $this->assertStringContainsString('function fetchEmpleado', $controller);
        $this->assertStringContainsString("where('nombre', 'like'", $controller);
        $this->assertStringContainsString("orWhere('cedula', 'like'", $controller);
        $this->assertStringContainsString('scopedEmpleadosQuery', $controller);
        $this->assertStringContainsString('applyScopeByFields($query, \'cliente_id\', \'sucursal_id\')', $controller);
        $this->assertStringContainsString('TenantSelection::isAdminBypass()', $controller);
        $this->assertStringContainsString('function selectLabel', $empleado);
        $this->assertStringContainsString('$empleado->selectLabel()', $controller);

        $this->assertStringContainsString("programa-caso/fetch/empleado", $routes);
        $this->assertStringContainsString('fetchEmpleado', $routes);

        $this->assertStringContainsString('bpFieldInitSelect2FromAjaxElement', $field);
        $this->assertStringContainsString('data-ajax-url', $field);
        $this->assertStringContainsString('minimumInputLength', $field);
        $this->assertStringContainsString('ajax:', $field);
        $this->assertStringContainsString('q: params.term', $field);
        $this->assertStringContainsString('No se encontraron personas', $field);
        $this->assertStringContainsString('select2.min.js', $field);

        $this->assertStringNotContainsString("field('empleado_id')", $controller);
        $this->assertMatchesRegularExpression("/'name'\\s*=>\\s*'empleado_id'[\\s\\S]*?'type'\\s*=>\\s*'select2_from_ajax'/", $controller);
    }

    public function test_empleado_select_label_joins_nombre_and_cedula(): void
    {
        $empleado = file_get_contents(dirname(__DIR__, 2) . '/app/Models/Empleado.php');
        $this->assertIsString($empleado);
        $this->assertStringContainsString('function selectLabel(): string', $empleado);
        $this->assertStringContainsString('$this->nombre', $empleado);
        $this->assertStringContainsString('$this->cedula', $empleado);
        $this->assertStringContainsString("implode(' · '", $empleado);
    }
}
