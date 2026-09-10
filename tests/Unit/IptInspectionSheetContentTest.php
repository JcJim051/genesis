<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\IptInspection;
use App\Models\IptInspectionAnswer;
use App\Models\IptInspectionRequirement;
use App\Models\IptTemplate;
use App\Models\IptTemplateQuestion;
use App\Models\IptTemplateRequirement;
use App\Models\IptTemplateSection;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\Google\IptDriveLayout;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class IptInspectionSheetContentTest extends TestCase
{
    public function test_formato_ipt_writes_db_section_titles_questions_and_answers(): void
    {
        $inspection = $this->makeInspection();
        $cells = IptDriveLayout::formatoCellMap($inspection, [
            'inicial' => 'https://example.test/antes.jpg',
            'despues' => '',
        ]);

        $this->assertSame('15/03/2026', $cells['B2']);
        $this->assertSame('ANA PEREZ', $cells['E2']);
        $this->assertSame('123456', $cells['H2']);
        $this->assertSame(41, $cells['B3']);
        $this->assertSame('Planta Norte', $cells['H3']);
        $this->assertSame('Villavicencio', $cells['B4']);
        $this->assertSame('Analista', $cells['E4']);
        $this->assertSame('2 AÑOS', $cells['H4']);
        $this->assertStringContainsString('Maria SST', $cells['A5']);
        $this->assertStringContainsString('Profesional que realiza la inspección', $cells['A5']);

        $this->assertSame('ASPECTOS GENERALES', $cells['A7']);
        $this->assertSame(1, $cells['A8']);
        $this->assertSame('¿La silla es ajustable?', $cells['B8']);
        $this->assertSame('X', $cells['F8']);
        $this->assertSame('', $cells['G8']);
        $this->assertSame('', $cells['H8']);
        $this->assertSame('¿Hay pausas?', $cells['B9']);
        $this->assertSame('X', $cells['G9']);
        $this->assertSame('¿El mouse es adecuado?', $cells['B10']);
        $this->assertSame('X', $cells['H10']);

        $this->assertContains('Puntaje total', $cells);
        $this->assertContains(18, $cells);
        $this->assertContains('ALTO', $cells);
        $this->assertContains('CAMBIO DE SILLA', $cells);
        $this->assertContains('X', $cells);
        $this->assertContains('Pantalla baja', $cells);
        $this->assertContains('Subir monitor', $cells);
        $this->assertContains('Ajustar puesto', $cells);
        $this->assertContains('SST', $cells);
        $this->assertStringContainsString('Firma profesional: Maria SST', implode("\n", array_map('strval', $cells)));
        $this->assertStringContainsString('=IMAGE("https://example.test/antes.jpg")', implode("\n", array_map('strval', $cells)));
    }

    public function test_formato_uses_genesis_sections_not_plantilla_vdt_labels(): void
    {
        $inspection = $this->makeInspection();
        $ambiente = new IptTemplateSection([
            'titulo' => 'ASPECTO A EVALUAR DEL AMBIENTE',
            'orden' => 2,
        ]);
        $q = new IptTemplateQuestion([
            'texto' => 'La iluminación del área es uniforme y sin sombras',
            'orden' => 1,
        ]);
        $q->id = 50;
        $ambiente->setRelation('questions', new EloquentCollection([$q]));
        $inspection->template->setRelation('sections', new EloquentCollection([
            $inspection->template->sections->first(),
            $ambiente,
        ]));
        $answers = $inspection->answers;
        $answers->push(new IptInspectionAnswer(['question_id' => 50, 'respuesta' => 'si', 'score' => 1]));
        $inspection->setRelation('answers', $answers);

        $cells = IptDriveLayout::formatoCellMap($inspection);
        $joined = implode("\n", array_map('strval', $cells));

        $this->assertStringContainsString('ASPECTO A EVALUAR DEL AMBIENTE', $joined);
        $this->assertStringContainsString('La iluminación del área es uniforme y sin sombras', $joined);
        $this->assertSame('ASPECTO A EVALUAR DEL AMBIENTE', $cells['A11']);
        $this->assertSame('La iluminación del área es uniforme y sin sombras', $cells['B12']);
        $this->assertSame('X', $cells['F12']);

        $this->assertStringNotContainsString('La pantalla cuenta con condiciones adecuadas de iluminación, sin presencia de reflejos', $joined);
    }

    public function test_content_uses_template_tabs_not_resumen_respuestas_requerimientos(): void
    {
        $ranges = IptDriveLayout::formatoValueRanges($this->makeInspection());
        $joined = json_encode($ranges);

        $this->assertStringContainsString("'FORMATO IPT'!", $joined);
        $this->assertStringNotContainsString('Resumen', $joined);
        $this->assertStringNotContainsString('Respuestas', $joined);
        $this->assertStringNotContainsString('MATRIZ IPT', $joined);
    }

    public function test_formato_value_ranges_quote_sheet_name_for_sheets_api(): void
    {
        $ranges = IptDriveLayout::formatoValueRanges($this->makeInspection());

        $this->assertNotEmpty($ranges);
        $this->assertSame("'FORMATO IPT'!B2", $ranges[0]['range']);

        foreach ($ranges as $range) {
            $this->assertArrayHasKey('range', $range);
            $this->assertMatchesRegularExpression("/^'FORMATO IPT'![A-Z]+[0-9]+(?::[A-Z]+[0-9]+)?$/", $range['range']);
        }
    }

    public function test_formato_value_ranges_use_resolved_real_tab_title(): void
    {
        $realTitle = "Formato\u{00A0}Ipt";
        $ranges = IptDriveLayout::formatoValueRanges($this->makeInspection(), [], $realTitle);

        $this->assertNotEmpty($ranges);
        $this->assertSame(IptDriveLayout::a1($realTitle, 'B2'), $ranges[0]['range']);
        $this->assertStringNotContainsString("'FORMATO IPT'!", json_encode($ranges));
    }

    public function test_worker_and_folder_names_strip_path_separators(): void
    {
        $inspection = $this->makeInspection();
        $inspection->empleado->nombre = 'Ana / Perez';

        $this->assertSame('ANA - PEREZ', IptDriveLayout::spreadsheetTitle($inspection));
        $this->assertSame('ANA - PEREZ', IptDriveLayout::pathAfterGenesis($inspection)[3]);
        $this->assertStringNotContainsString('/', IptDriveLayout::spreadsheetTitle($inspection));
    }

    public function test_missing_photos_leave_evidence_cells_blank(): void
    {
        $inspection = $this->makeInspection();
        $cells = IptDriveLayout::formatoCellMap($inspection, [
            'inicial' => '',
            'despues' => '',
        ]);
        $joined = implode("\n", array_map('strval', $cells));

        $this->assertStringContainsString('Evidencia fotográfica', $joined);
        $this->assertStringNotContainsString('127.0.0.1', $joined);
        $this->assertStringNotContainsString('/storage/ipt-evidencias', $joined);
        $this->assertStringNotContainsString('=IMAGE(', $joined);
    }

    public function test_localhost_photo_urls_are_rejected(): void
    {
        $this->assertTrue(IptDriveLayout::isUnusableLocalUrl('http://127.0.0.1:8000/storage/ipt-evidencias/antes.jpg'));
        $this->assertTrue(IptDriveLayout::isUnusableLocalUrl('http://localhost/storage/x.jpg'));
        $this->assertSame('', IptDriveLayout::photoCellValue('http://127.0.0.1:8000/storage/ipt-evidencias/antes.jpg'));
        $this->assertSame(
            '=IMAGE("https://drive.google.com/uc?export=view&id=1OOQyEjYcuRzbuaViySddY9MQSJc5B3jiOXRwhxiKW4w")',
            IptDriveLayout::photoCellValue('https://drive.google.com/file/d/1OOQyEjYcuRzbuaViySddY9MQSJc5B3jiOXRwhxiKW4w/view')
        );
    }

    public function test_accion_falls_back_to_recomendaciones_when_recomendaciones_hidden(): void
    {
        $inspection = $this->makeInspection();
        $inspection->accion = '';
        $inspection->template->mostrar_recomendaciones = false;
        $inspection->template->mostrar_accion = true;

        $cells = IptDriveLayout::formatoCellMap($inspection);
        $this->assertContains('Subir monitor', $cells);
        $this->assertContains('Pantalla baja', $cells);
        $this->assertContains('Acción', $cells);
        $this->assertNotContains('Recomendaciones', $cells);
    }

    public function test_unanswered_questions_clear_si_no_na_instead_of_inventing_scores(): void
    {
        $inspection = $this->makeInspection();
        $inspection->setRelation('answers', new EloquentCollection());

        $cells = IptDriveLayout::formatoCellMap($inspection);

        $this->assertSame('', $cells['F8']);
        $this->assertSame('', $cells['G8']);
        $this->assertSame('', $cells['H8']);
        $this->assertSame('¿La silla es ajustable?', $cells['B8']);
    }

    public function test_seguimientos_row_for_initial_inspection(): void
    {
        $row = IptDriveLayout::seguimientosRow($this->makeInspection());

        $this->assertSame('15/03/2026', $row[0]);
        $this->assertSame('123456', $row[1]);
        $this->assertSame('ANA PEREZ', $row[2]);
        $this->assertSame('', $row[3]);
        $this->assertSame('Analista', $row[4]);
        $this->assertSame('Pantalla baja', $row[5]);
        $this->assertSame('Subir monitor', $row[6]);
        $this->assertSame('CAMBIO DE SILLA', $row[7]);
        $this->assertSame('15/06/2026', $row[8]);
        $this->assertSame('', $row[9]);
        $this->assertSame('', $row[10]);
        $this->assertSame('ABIERTO', $row[11]);
    }

    public function test_seguimientos_aligns_hallazgos_to_header_not_area_or_cargo(): void
    {
        $canonical = IptDriveLayout::seguimientosRow($this->makeInspection());
        $headers = ['FECHA', 'NOMBRE COMPLETO', 'IDENTIFICACION', 'ÁREA', 'CARGO', 'HALLAZGOS', 'RECOMENDACIONES'];
        $aligned = IptDriveLayout::alignSeguimientosRow($headers, $canonical);

        $this->assertSame('ANA PEREZ', $aligned[1]);
        $this->assertSame('123456', $aligned[2]);
        $this->assertSame('', $aligned[3]);
        $this->assertSame('Analista', $aligned[4]);
        $this->assertSame('Pantalla baja', $aligned[5]);
        $this->assertNotSame('Pantalla baja', $aligned[3]);
        $this->assertNotSame('Pantalla baja', $aligned[4]);
    }

    public function test_followup_appends_seguimientos_fields_and_keeps_initial_folder_month(): void
    {
        $initial = $this->makeInspection();
        $followup = $this->makeInspection();
        $followup->tipo = 'followup';
        $followup->fecha_inspeccion = '2026-11-02';
        $followup->seguimiento_exitoso = true;
        $followup->hallazgos = 'Ajustes verificados';
        $followup->estado = 'cerrado';
        $followup->setRelation('initialInspection', $initial);

        $this->assertTrue(IptDriveLayout::isFollowup($followup));
        $this->assertSame(['Acme Corp', '2026', 'MARZO', 'ANA PEREZ'], IptDriveLayout::pathAfterGenesis($followup));
        $this->assertSame(
            IptDriveLayout::workerSheetSettingsKey($initial),
            IptDriveLayout::workerSheetSettingsKey($followup)
        );

        $row = IptDriveLayout::seguimientosRow($followup);
        $this->assertSame('02/11/2026', $row[0]);
        $this->assertSame('02/11/2026', $row[8]);
        $this->assertSame('SI', $row[9]);
        $this->assertSame('Ajustes verificados', $row[10]);
        $this->assertSame('CERRADO', $row[11]);
    }

    public function test_body_clear_range_quotes_formato_tab(): void
    {
        $this->assertSame("'FORMATO IPT'!A7:H250", IptDriveLayout::formatoBodyClearA1(IptDriveLayout::FORMATO_TAB));
    }

    private function makeInspection(): IptInspection
    {
        $cliente = new Cliente([
            'nombre' => 'Acme Corp',
            'ciudad' => 'Villavicencio',
        ]);
        $sucursal = new Sucursal(['nombre' => 'Planta Norte']);
        $empleado = new Empleado([
            'nombre' => 'Ana Perez',
            'cedula' => '123456',
            'cargo' => 'Analista',
            'edad' => 41,
            'fecha_ingreso' => '2024-03-15',
        ]);
        $empleado->setRelation('cliente', $cliente);
        $empleado->setRelation('sucursal', $sucursal);
        $empleado->setRelation('cargos', new EloquentCollection());
        $empleado->setRelation('areas', new EloquentCollection());

        $questions = [];
        foreach (['¿La silla es ajustable?', '¿Hay pausas?', '¿El mouse es adecuado?'] as $i => $texto) {
            $question = new IptTemplateQuestion([
                'texto' => $texto,
                'orden' => $i + 1,
            ]);
            $question->id = $i + 7;
            $questions[] = $question;
        }

        $section = new IptTemplateSection([
            'titulo' => 'ASPECTOS GENERALES',
            'orden' => 1,
        ]);
        $section->setRelation('questions', new EloquentCollection($questions));

        $template = new IptTemplate([
            'nombre_publico' => 'Plantilla VDT',
            'evidencia_fotografica_modo' => 'before_after',
            'mostrar_accion' => true,
            'mostrar_responsable' => true,
            'mostrar_hallazgos' => true,
            'mostrar_observaciones' => true,
            'mostrar_recomendaciones' => true,
        ]);
        $template->setRelation('sections', new EloquentCollection([$section]));

        $answers = new EloquentCollection([
            new IptInspectionAnswer(['question_id' => 7, 'respuesta' => 'si', 'score' => 1]),
            new IptInspectionAnswer(['question_id' => 8, 'respuesta' => 'no', 'score' => 0]),
            new IptInspectionAnswer(['question_id' => 9, 'respuesta' => 'na', 'score' => 0]),
        ]);

        $reqDefYes = new IptTemplateRequirement(['nombre' => 'CAMBIO DE SILLA']);
        $reqYes = new IptInspectionRequirement(['aplica' => true]);
        $reqYes->setRelation('requirement', $reqDefYes);

        $reqDefNo = new IptTemplateRequirement(['nombre' => 'APOYAPIÉS']);
        $reqNo = new IptInspectionRequirement(['aplica' => false]);
        $reqNo->setRelation('requirement', $reqDefNo);

        $inspection = new IptInspection([
            'tipo' => 'initial',
            'puntaje_total' => 18,
            'nivel_riesgo' => 'alto',
            'estado' => 'abierto',
            'hallazgos' => 'Pantalla baja',
            'recomendaciones' => 'Subir monitor',
            'accion' => 'Ajustar puesto',
            'responsable' => 'SST',
            'foto_antes' => 'ipt-evidencias/antes.jpg',
            'foto_despues' => null,
            'fecha_inspeccion' => '2026-03-15',
            'fecha_proximo_seguimiento_sugerida' => '2026-06-15',
        ]);
        $inspection->id = 42;
        $inspection->setRelation('empleado', $empleado);
        $inspection->setRelation('template', $template);
        $inspection->setRelation('answers', $answers);
        $inspection->setRelation('requirements', new EloquentCollection([$reqYes, $reqNo]));
        $inspection->setRelation('programaCaso', null);
        $inspection->setRelation('initialInspection', null);

        $creator = new User(['name' => 'Maria SST']);
        $inspection->setRelation('creator', $creator);

        return $inspection;
    }
}
