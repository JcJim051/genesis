<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IptFormEmptySectionWiringTest extends TestCase
{
    public function test_ipt_fill_show_and_pdf_hide_empty_template_sections(): void
    {
        $form = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/ipt_inspections/form.blade.php');
        $show = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/ipt_inspections/show.blade.php');
        $pdf = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/ipt_inspections/pdf.blade.php');
        $helper = file_get_contents(dirname(__DIR__, 2) . '/app/Services/Ipt/IptFormLayout.php');

        $this->assertIsString($form);
        $this->assertIsString($show);
        $this->assertIsString($pdf);
        $this->assertIsString($helper);

        $this->assertStringContainsString('function sectionIsVisible', $helper);
        $this->assertStringContainsString('function requirementsSectionIsVisible', $helper);
        $this->assertStringContainsString('function visibleQuestionSections', $helper);

        foreach ([$form, $show, $pdf] as $blade) {
            $this->assertStringContainsString('IptFormLayout::hasVisibleQuestionSections', $blade);
            $this->assertStringContainsString('IptFormLayout::visibleQuestionSections', $blade);
            $this->assertStringContainsString('IptFormLayout::requirementsSectionIsVisible', $blade);
        }

        $this->assertStringContainsString('IptFormLayout::activeRequirements', $form);
        $this->assertStringContainsString('Requerimientos estación de trabajo', $form);
        $this->assertStringContainsString('template.requirements', $show);
        $this->assertStringContainsString('template.requirements', $pdf);
    }
}
