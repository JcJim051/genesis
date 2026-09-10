<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IptCaptureFieldVisibilityWiringTest extends TestCase
{
    public function test_template_builder_exposes_all_capture_field_toggles(): void
    {
        $builder = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/ipt_templates/builder.blade.php');
        $this->assertIsString($builder);
        $this->assertStringContainsString('Campos visibles en el formulario de inspección', $builder);
        $this->assertStringContainsString('name="mostrar_hallazgos"', $builder);
        $this->assertStringContainsString('name="mostrar_observaciones"', $builder);
        $this->assertStringContainsString('name="mostrar_recomendaciones"', $builder);
        $this->assertStringContainsString('name="mostrar_accion"', $builder);
        $this->assertStringContainsString('name="mostrar_responsable"', $builder);
        $this->assertStringContainsString('name="mostrar_estado"', $builder);
        $this->assertStringContainsString('Mostrar hallazgos', $builder);
        $this->assertStringContainsString('Mostrar observaciones', $builder);
        $this->assertStringContainsString('Mostrar recomendaciones', $builder);
        $this->assertStringContainsString('Mostrar acción', $builder);
        $this->assertStringContainsString('Mostrar responsable', $builder);
        $this->assertStringContainsString('Mostrar estado', $builder);
    }

    public function test_fill_show_and_pdf_honor_capture_field_flags(): void
    {
        $form = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/ipt_inspections/form.blade.php');
        $show = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/ipt_inspections/show.blade.php');
        $pdf = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/ipt_inspections/pdf.blade.php');
        $helper = file_get_contents(dirname(__DIR__, 2) . '/app/Services/Ipt/IptFormLayout.php');
        $inspectionController = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/IptInspectionCrudController.php');
        $templateController = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/IptTemplateCrudController.php');

        $this->assertIsString($form);
        $this->assertIsString($show);
        $this->assertIsString($pdf);
        $this->assertIsString($helper);
        $this->assertIsString($inspectionController);
        $this->assertIsString($templateController);

        foreach (['showsHallazgos', 'showsObservaciones', 'showsHallazgosObservacionesField', 'showsRecomendaciones', 'showsAccion', 'showsResponsable', 'showsEstado', 'captureNotesSectionIsVisible'] as $fn) {
            $this->assertStringContainsString('function ' . $fn, $helper);
        }

        foreach ([$form, $show, $pdf] as $blade) {
            $this->assertStringContainsString('IptFormLayout::showsHallazgosObservacionesField', $blade);
            $this->assertStringContainsString('IptFormLayout::showsRecomendaciones', $blade);
            $this->assertStringContainsString('IptFormLayout::showsAccion', $blade);
            $this->assertStringContainsString('IptFormLayout::showsResponsable', $blade);
            $this->assertStringContainsString('IptFormLayout::showsEstado', $blade);
        }

        $this->assertStringContainsString('IptFormLayout::captureFieldsRowIsVisible', $form);
        $this->assertStringContainsString('IptFormLayout::captureNotesSectionIsVisible', $show);
        $this->assertStringContainsString('IptFormLayout::captureNotesSectionIsVisible', $pdf);

        $this->assertStringContainsString('IptFormLayout::showsHallazgosObservacionesField($template) ? \'nullable\' : \'prohibited\'', $inspectionController);
        $this->assertStringContainsString('IptFormLayout::showsRecomendaciones($template) ? \'nullable\' : \'prohibited\'', $inspectionController);
        $this->assertStringContainsString('IptFormLayout::showsEstado($template) ? \'nullable|in:abierto,cerrado\' : \'prohibited\'', $inspectionController);

        $this->assertStringContainsString("'mostrar_hallazgos'", $templateController);
        $this->assertStringContainsString("'mostrar_observaciones'", $templateController);
        $this->assertStringContainsString("'mostrar_recomendaciones'", $templateController);
        $this->assertStringContainsString("'mostrar_estado'", $templateController);
    }
}
