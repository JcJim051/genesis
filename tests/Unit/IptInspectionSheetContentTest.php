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
    public function test_formato_ipt_maps_identity_answers_requirements_and_risk(): void
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

        $this->assertSame(1, $cells['F8']);
        $this->assertSame('', $cells['G8']);
        $this->assertSame('', $cells['H8']);
        $this->assertSame('', $cells['F9']);
        $this->assertSame(0, $cells['G9']);
        $this->assertSame('', $cells['F10']);
        $this->assertSame('', $cells['G10']);
        $this->assertSame(0, $cells['H10']);

        $this->assertSame(18, $cells['F56']);
        $this->assertSame(18, $cells['E61']);
        $this->assertSame('X', $cells['B63']);
        $this->assertSame('', $cells['B61']);
        $this->assertSame('', $cells['B62']);

        $this->assertSame('X', $cells['D59']);
        $this->assertSame('', $cells['A59']);
        $this->assertSame('', $cells['E59']);

        $this->assertSame('Pantalla baja', $cells['A65']);
        $this->assertSame('Ajustar puesto', $cells['A68']);
        $this->assertSame('SST', $cells['E68']);
        $this->assertSame('', $cells['A69']);
        $this->assertSame('https://example.test/antes.jpg', $cells['A72']);
        $this->assertSame('', $cells['E72']);
        $this->assertSame('Firma profesional: Maria SST', $cells['A79']);
    }

    public function test_content_uses_template_tabs_not_resumen_respuestas_requerimientos(): void
    {
        $ranges = IptDriveLayout::formatoValueRanges($this->makeInspection());
        $joined = json_encode($ranges);

        $this->assertStringContainsString("'FORMATO IPT'!", $joined);
        $this->assertStringNotContainsString('Resumen', $joined);
        $this->assertStringNotContainsString('Respuestas', $joined);
        $this->assertStringNotContainsString('Requerimientos', $joined);
        $this->assertStringNotContainsString('MATRIZ IPT', $joined);
    }

    public function test_formato_value_ranges_quote_sheet_name_for_sheets_api(): void
    {
        $ranges = IptDriveLayout::formatoValueRanges($this->makeInspection());

        $this->assertNotEmpty($ranges);
        $this->assertSame("'FORMATO IPT'!B2", $ranges[0]['range']);

        foreach ($ranges as $range) {
            $this->assertArrayHasKey('range', $range);
            $this->assertMatchesRegularExpression("/^'FORMATO IPT'![A-Z]+[0-9]+$/", $range['range']);
        }
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
        $inspection->foto_antes = null;
        $inspection->foto_despues = null;
        $inspection->template->evidencia_fotografica_modo = 'general';
        $inspection->foto_general = null;

        $cells = IptDriveLayout::formatoCellMap($inspection, [
            'inicial' => '',
            'despues' => '',
        ]);

        $this->assertSame('', $cells['A72']);
        $this->assertSame('', $cells['E72']);
    }

    public function test_accion_falls_back_to_recomendaciones_when_empty(): void
    {
        $inspection = $this->makeInspection();
        $inspection->accion = '';

        $cells = IptDriveLayout::formatoCellMap($inspection);

        $this->assertSame('Subir monitor', $cells['A68']);
        $this->assertSame('Pantalla baja', $cells['A65']);
    }

    public function test_unanswered_questions_clear_si_no_na_instead_of_inventing_scores(): void
    {
        $inspection = $this->makeInspection();
        $inspection->setRelation('answers', new EloquentCollection());

        $cells = IptDriveLayout::formatoCellMap($inspection);

        $this->assertSame('', $cells['F8']);
        $this->assertSame('', $cells['G8']);
        $this->assertSame('', $cells['H8']);
    }

    public function test_seguimientos_row_for_initial_inspection(): void
    {
        $row = IptDriveLayout::seguimientosRow($this->makeInspection());

        $this->assertSame('15/03/2026', $row[0]);
        $this->assertSame('123456', $row[1]);
        $this->assertSame('ANA PEREZ', $row[2]);
        $this->assertSame('Analista', $row[4]);
        $this->assertSame('Pantalla baja', $row[5]);
        $this->assertSame('Subir monitor', $row[6]);
        $this->assertSame('CAMBIO DE SILLA', $row[7]);
        $this->assertSame('15/06/2026', $row[8]);
        $this->assertSame('', $row[9]);
        $this->assertSame('', $row[10]);
        $this->assertSame('ABIERTO', $row[11]);
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
