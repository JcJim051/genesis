<?php

namespace Tests\Unit;

use App\Models\IptTemplate;
use App\Models\IptTemplateQuestion;
use App\Models\IptTemplateRequirement;
use App\Models\IptTemplateSection;
use App\Services\Ipt\IptFormLayout;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;

class IptFormLayoutTest extends TestCase
{
    public function test_empty_question_section_is_hidden(): void
    {
        $empty = $this->section('Requerimientos de estación', []);
        $filled = $this->section('Silla', ['¿Altura adecuada?']);

        $this->assertFalse(IptFormLayout::sectionIsVisible($empty));
        $this->assertTrue(IptFormLayout::sectionIsVisible($filled));
        $this->assertCount(1, IptFormLayout::sectionQuestions($filled));
        $this->assertCount(0, IptFormLayout::sectionQuestions($empty));
    }

    public function test_template_hides_empty_sections_and_keeps_filled_ones(): void
    {
        $template = new IptTemplate(['nombre_publico' => 'VDT']);
        $template->setRelation('sections', new EloquentCollection([
            $this->section('Vacía', []),
            $this->section('Puesto', ['¿Monitor a la altura de los ojos?']),
        ]));
        $template->setRelation('requirements', new EloquentCollection([]));

        $this->assertTrue(IptFormLayout::hasVisibleQuestionSections($template));
        $visible = IptFormLayout::visibleQuestionSections($template);
        $this->assertCount(1, $visible);
        $this->assertSame('Puesto', $visible->first()->titulo);

        $allEmpty = new IptTemplate(['nombre_publico' => 'Vacía']);
        $allEmpty->setRelation('sections', new EloquentCollection([
            $this->section('Sin preguntas', []),
        ]));
        $this->assertFalse(IptFormLayout::hasVisibleQuestionSections($allEmpty));
    }

    public function test_requirements_section_hidden_when_template_has_none_active(): void
    {
        $template = new IptTemplate(['nombre_publico' => 'VDT']);
        $template->setRelation('requirements', new EloquentCollection([]));
        $this->assertFalse(IptFormLayout::requirementsSectionIsVisible($template));
        $this->assertCount(0, IptFormLayout::activeRequirements($template));

        $inactiveOnly = new IptTemplate(['nombre_publico' => 'VDT']);
        $inactive = new IptTemplateRequirement(['nombre' => 'Silla', 'activo' => false]);
        $inactiveOnly->setRelation('requirements', new EloquentCollection([$inactive]));
        $this->assertFalse(IptFormLayout::requirementsSectionIsVisible($inactiveOnly));

        $withActive = new IptTemplate(['nombre_publico' => 'VDT']);
        $active = new IptTemplateRequirement(['nombre' => 'Apoyapiés', 'activo' => true]);
        $withActive->setRelation('requirements', new EloquentCollection([$inactive, $active]));
        $this->assertTrue(IptFormLayout::requirementsSectionIsVisible($withActive));
        $this->assertCount(1, IptFormLayout::activeRequirements($withActive));
        $this->assertSame('Apoyapiés', IptFormLayout::activeRequirements($withActive)->first()->nombre);
    }

    public function test_capture_field_flags_default_on_and_can_hide_notes_section(): void
    {
        $legacy = new IptTemplate(['nombre_publico' => 'VDT']);
        $this->assertTrue(IptFormLayout::showsHallazgos($legacy));
        $this->assertTrue(IptFormLayout::showsObservaciones($legacy));
        $this->assertTrue(IptFormLayout::showsHallazgosObservacionesField($legacy));
        $this->assertTrue(IptFormLayout::showsRecomendaciones($legacy));
        $this->assertTrue(IptFormLayout::showsAccion($legacy));
        $this->assertTrue(IptFormLayout::showsResponsable($legacy));
        $this->assertTrue(IptFormLayout::showsEstado($legacy));
        $this->assertTrue(IptFormLayout::captureNotesSectionIsVisible($legacy));
        $this->assertTrue(IptFormLayout::captureFieldsRowIsVisible($legacy));
        $this->assertSame('Hallazgos / observaciones', IptFormLayout::hallazgosObservacionesLabel($legacy));

        $hiddenNotes = new IptTemplate([
            'mostrar_hallazgos' => false,
            'mostrar_observaciones' => false,
            'mostrar_recomendaciones' => false,
            'mostrar_accion' => false,
            'mostrar_responsable' => false,
            'mostrar_estado' => true,
        ]);
        $this->assertFalse(IptFormLayout::showsHallazgosObservacionesField($hiddenNotes));
        $this->assertFalse(IptFormLayout::captureNotesSectionIsVisible($hiddenNotes));
        $this->assertTrue(IptFormLayout::captureFieldsRowIsVisible($hiddenNotes));
        $this->assertFalse(IptFormLayout::captureFieldsRowIsVisible(
            new IptTemplate([
                'mostrar_hallazgos' => false,
                'mostrar_observaciones' => false,
                'mostrar_recomendaciones' => false,
                'mostrar_accion' => false,
                'mostrar_responsable' => false,
                'mostrar_estado' => false,
            ])
        ));
        $this->assertTrue(IptFormLayout::captureFieldsRowIsVisible(
            new IptTemplate([
                'mostrar_hallazgos' => false,
                'mostrar_observaciones' => false,
                'mostrar_recomendaciones' => false,
                'mostrar_accion' => false,
                'mostrar_responsable' => false,
                'mostrar_estado' => false,
            ]),
            true
        ));
    }

    public function test_hallazgos_and_observaciones_share_one_field_with_dynamic_label(): void
    {
        $onlyHallazgos = new IptTemplate([
            'mostrar_hallazgos' => true,
            'mostrar_observaciones' => false,
        ]);
        $this->assertTrue(IptFormLayout::showsHallazgosObservacionesField($onlyHallazgos));
        $this->assertSame('Hallazgos', IptFormLayout::hallazgosObservacionesLabel($onlyHallazgos));

        $onlyObservaciones = new IptTemplate([
            'mostrar_hallazgos' => false,
            'mostrar_observaciones' => true,
        ]);
        $this->assertTrue(IptFormLayout::showsHallazgosObservacionesField($onlyObservaciones));
        $this->assertSame('Observaciones', IptFormLayout::hallazgosObservacionesLabel($onlyObservaciones));

        $neither = new IptTemplate([
            'mostrar_hallazgos' => false,
            'mostrar_observaciones' => false,
        ]);
        $this->assertFalse(IptFormLayout::showsHallazgosObservacionesField($neither));
    }

    /**
     * @param  list<string>  $questionTexts
     */
    private function section(string $titulo, array $questionTexts): IptTemplateSection
    {
        $section = new IptTemplateSection(['titulo' => $titulo, 'orden' => 0]);
        $questions = [];
        foreach ($questionTexts as $i => $texto) {
            $questions[] = new IptTemplateQuestion([
                'texto' => $texto,
                'orden' => $i,
            ]);
        }
        $section->setRelation('questions', new EloquentCollection($questions));

        return $section;
    }
}
