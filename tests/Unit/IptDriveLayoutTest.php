<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\IptInspection;
use App\Services\Google\IptDriveLayout;
use Carbon\Carbon;
use Tests\TestCase;

class IptDriveLayoutTest extends TestCase
{
    public function test_spanish_month_names_are_uppercase_full_names(): void
    {
        $expected = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        foreach ($expected as $month => $name) {
            $date = Carbon::create(2026, $month, 7);
            $this->assertSame($name, IptDriveLayout::spanishMonthName($date));
            $this->assertSame('2026', IptDriveLayout::year($date));
        }

        $this->assertSame('SEPTIEMBRE', IptDriveLayout::spanishMonthName('2026-09-15'));
        $this->assertNotSame('2026-09', IptDriveLayout::spanishMonthName('2026-09-15'));
    }

    public function test_path_is_empresa_year_spanish_month_worker(): void
    {
        $inspection = $this->inspection('SECRETARIA DE EDUCACIÓN', 'BARBERI PEÑA RENE MAURICIO', '2026-09-07');

        $this->assertSame(
            [
                'SECRETARIA DE EDUCACIÓN',
                '2026',
                'SEPTIEMBRE',
                'BARBERI PEÑA RENE MAURICIO',
            ],
            IptDriveLayout::pathAfterGenesis($inspection)
        );
        $this->assertSame(
            'Genesis / SECRETARIA DE EDUCACIÓN / 2026 / SEPTIEMBRE / BARBERI PEÑA RENE MAURICIO',
            IptDriveLayout::displayPath($inspection)
        );
        $this->assertSame('BARBERI PEÑA RENE MAURICIO', IptDriveLayout::spreadsheetTitle($inspection));
    }

    public function test_worker_sheet_settings_key_is_stable_for_same_empresa_year_month_worker(): void
    {
        $a = $this->inspection('Acme', 'Ana Perez', '2026-03-15');
        $b = $this->inspection('Acme', 'Ana Perez', '2026-03-31');
        $c = $this->inspection('Acme', 'Ana Perez', '2026-04-01');
        $d = $this->inspection('Otra', 'Ana Perez', '2026-03-15');

        $this->assertSame(
            IptDriveLayout::workerSheetSettingsKey($a),
            IptDriveLayout::workerSheetSettingsKey($b)
        );
        $this->assertNotSame(
            IptDriveLayout::workerSheetSettingsKey($a),
            IptDriveLayout::workerSheetSettingsKey($c)
        );
        $this->assertNotSame(
            IptDriveLayout::workerSheetSettingsKey($a),
            IptDriveLayout::workerSheetSettingsKey($d)
        );
        $this->assertStringStartsWith('google_drive.ipt_worker_sheet.', IptDriveLayout::workerSheetSettingsKey($a));
    }

    public function test_extract_spreadsheet_id_from_url_or_plain_id(): void
    {
        $id = IptDriveLayout::DEFAULT_TEMPLATE_ID;
        $this->assertSame($id, IptDriveLayout::extractSpreadsheetId($id));
        $this->assertSame(
            $id,
            IptDriveLayout::extractSpreadsheetId('https://docs.google.com/spreadsheets/d/' . $id . '/edit#gid=0')
        );
    }

    public function test_requirement_cells_match_template_labels_with_accents(): void
    {
        $this->assertSame('A59', IptDriveLayout::requirementCell('MANTENIMIENTO DE SILLA'));
        $this->assertSame('D59', IptDriveLayout::requirementCell('CAMBIO DE SILLA'));
        $this->assertSame('E59', IptDriveLayout::requirementCell('APOYAPIÉS'));
        $this->assertSame('F59', IptDriveLayout::requirementCell('KIT ERGONÓMICO PORTÁTIL'));
        $this->assertSame('G59', IptDriveLayout::requirementCell('SOPORTE PARA MONITOR'));
        $this->assertSame('H59', IptDriveLayout::requirementCell('TECLADO'));
        $this->assertNull(IptDriveLayout::requirementCell('Algo que no existe'));
    }

    public function test_default_template_id_matches_public_spreadsheet(): void
    {
        $this->assertSame('1e6Lr0lzrctebCCr8J5PM8z27TUKVnraU', IptDriveLayout::DEFAULT_TEMPLATE_ID);
        $this->assertSame('FORMATO IPT', IptDriveLayout::FORMATO_TAB);
        $this->assertSame('SEGUIMIENTOS', IptDriveLayout::SEGUIMIENTOS_TAB);
        $this->assertCount(43, IptDriveLayout::QUESTION_ROWS);
        $this->assertSame(8, IptDriveLayout::QUESTION_ROWS[1]);
        $this->assertSame(55, IptDriveLayout::QUESTION_ROWS[43]);
    }

    private function inspection(string $empresa, string $trabajador, string $fecha): IptInspection
    {
        $cliente = new Cliente(['nombre' => $empresa]);
        $empleado = new Empleado(['nombre' => $trabajador]);
        $empleado->setRelation('cliente', $cliente);

        $inspection = new IptInspection([
            'tipo' => 'initial',
            'fecha_inspeccion' => $fecha,
        ]);
        $inspection->setRelation('empleado', $empleado);
        $inspection->setRelation('programaCaso', null);
        $inspection->setRelation('initialInspection', null);

        return $inspection;
    }
}
