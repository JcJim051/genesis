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
use App\Services\Google\GoogleSheetsMatrixService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class IptInspectionSheetContentTest extends TestCase
{
    public function test_sheet_content_mirrors_pdf_structure_not_matrix_columns(): void
    {
        $inspection = $this->makeInspection();
        $content = (new GoogleSheetsMatrixService())->buildIptInspectionSheetContent($inspection);

        $this->assertSame('IPT-42 - Ana Perez - 2026-03-15', $content['spreadsheet_name']);
        $this->assertSame('Acme Corp', $content['empresa_nombre']);
        $this->assertSame(['Resumen', 'Respuestas', 'Requerimientos'], array_keys($content['tabs']));

        $resumen = $content['tabs']['Resumen'];
        $this->assertSame(['Inspección IPT #42'], $resumen[0]);
        $this->assertContains(['Tipo', 'Inicial'], $resumen);
        $this->assertContains(['Plantilla', 'Plantilla VDT'], $resumen);
        $this->assertContains(['Persona', 'Ana Perez · 123456'], $resumen);
        $this->assertContains(['Fecha inspección', '2026-03-15'], $resumen);
        $this->assertContains(['Empresa', 'Acme Corp'], $resumen);
        $this->assertContains(['Planta', 'Planta Norte'], $resumen);
        $this->assertContains(['Puntaje', '18'], $resumen);
        $this->assertContains(['Riesgo', 'ALTO'], $resumen);
        $this->assertContains(['Estado', 'Abierto'], $resumen);
        $this->assertContains(['Hallazgos', 'Pantalla baja'], $resumen);
        $this->assertContains(['Recomendaciones', 'Subir monitor'], $resumen);
        $this->assertContains(['Acción', 'Ajustar puesto'], $resumen);
        $this->assertContains(['Responsable', 'SST'], $resumen);

        $antes = collect($resumen)->first(fn ($row) => ($row[0] ?? null) === 'Antes');
        $this->assertIsArray($antes);
        $this->assertStringContainsString('/storage/ipt-evidencias/antes.jpg', (string) $antes[1]);

        $despues = collect($resumen)->first(fn ($row) => ($row[0] ?? null) === 'Después');
        $this->assertIsArray($despues);
        $this->assertSame('Sin evidencia', $despues[1]);

        $respuestas = $content['tabs']['Respuestas'];
        $this->assertSame(['Respuestas'], $respuestas[0]);
        $this->assertContains(['Puesto de trabajo'], $respuestas);
        $this->assertContains(['Pregunta', 'Respuesta', 'Puntaje'], $respuestas);
        $this->assertContains(['¿La silla es ajustable?', 'SI', '2'], $respuestas);

        $requerimientos = $content['tabs']['Requerimientos'];
        $this->assertSame(['Requerimientos de estación'], $requerimientos[0]);
        $this->assertContains(['Requerimiento', 'Aplica'], $requerimientos);
        $this->assertContains(['Silla ergonómica', 'Sí'], $requerimientos);
        $this->assertContains(['Apoya pies', 'No'], $requerimientos);

        $flat = json_encode($content['tabs']);
        $this->assertStringNotContainsString('MATRIZ IPT', $flat);
        $this->assertStringNotContainsString('CÉDULA', $flat);
    }

    public function test_spreadsheet_name_strips_path_separators(): void
    {
        $inspection = $this->makeInspection();
        $inspection->empleado->nombre = 'Ana / Perez';

        $content = (new GoogleSheetsMatrixService())->buildIptInspectionSheetContent($inspection);

        $this->assertSame('IPT-42 - Ana - Perez - 2026-03-15', $content['spreadsheet_name']);
        $this->assertStringNotContainsString('/', $content['spreadsheet_name']);
    }

    public function test_missing_photos_do_not_break_content(): void
    {
        $inspection = $this->makeInspection();
        $inspection->foto_antes = null;
        $inspection->foto_despues = null;
        $inspection->template->evidencia_fotografica_modo = 'general';
        $inspection->foto_general = null;

        $content = (new GoogleSheetsMatrixService())->buildIptInspectionSheetContent($inspection);
        $general = collect($content['tabs']['Resumen'])->first(fn ($row) => ($row[0] ?? null) === 'Evidencia general');

        $this->assertIsArray($general);
        $this->assertSame('Sin evidencia', $general[1]);
    }

    public function test_accion_and_responsable_are_omitted_when_template_hides_them(): void
    {
        $inspection = $this->makeInspection();
        $inspection->template->mostrar_accion = false;
        $inspection->template->mostrar_responsable = false;

        $resumen = (new GoogleSheetsMatrixService())->buildIptInspectionSheetContent($inspection)['tabs']['Resumen'];

        $this->assertNull(collect($resumen)->first(fn ($row) => ($row[0] ?? null) === 'Acción'));
        $this->assertNull(collect($resumen)->first(fn ($row) => ($row[0] ?? null) === 'Responsable'));
        $this->assertContains(['Hallazgos', 'Pantalla baja'], $resumen);
    }

    private function makeInspection(): IptInspection
    {
        $cliente = new Cliente(['nombre' => 'Acme Corp']);
        $sucursal = new Sucursal(['nombre' => 'Planta Norte']);
        $empleado = new Empleado([
            'nombre' => 'Ana Perez',
            'cedula' => '123456',
        ]);
        $empleado->setRelation('cliente', $cliente);
        $empleado->setRelation('sucursal', $sucursal);

        $question = new IptTemplateQuestion([
            'texto' => '¿La silla es ajustable?',
            'orden' => 1,
        ]);
        $question->id = 7;

        $section = new IptTemplateSection([
            'titulo' => 'Puesto de trabajo',
            'orden' => 1,
        ]);
        $section->setRelation('questions', new EloquentCollection([$question]));

        $template = new IptTemplate([
            'nombre_publico' => 'Plantilla VDT',
            'evidencia_fotografica_modo' => 'before_after',
            'mostrar_accion' => true,
            'mostrar_responsable' => true,
        ]);
        $template->setRelation('sections', new EloquentCollection([$section]));

        $answer = new IptInspectionAnswer([
            'question_id' => 7,
            'respuesta' => 'si',
            'score' => 2,
        ]);

        $reqDefYes = new IptTemplateRequirement(['nombre' => 'Silla ergonómica']);
        $reqYes = new IptInspectionRequirement(['aplica' => true]);
        $reqYes->setRelation('requirement', $reqDefYes);

        $reqDefNo = new IptTemplateRequirement(['nombre' => 'Apoya pies']);
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
        $inspection->setRelation('answers', new EloquentCollection([$answer]));
        $inspection->setRelation('requirements', new EloquentCollection([$reqYes, $reqNo]));
        $inspection->setRelation('programaCaso', null);

        return $inspection;
    }
}
