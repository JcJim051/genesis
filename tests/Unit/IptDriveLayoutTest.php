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
        $this->assertSame(7, IptDriveLayout::CHECKLIST_START_ROW);
        $this->assertSame('A7:H250', IptDriveLayout::FORMATO_BODY_CLEAR_RANGE);
    }

    public function test_a1_ranges_quote_sheet_names_with_spaces_and_special_chars(): void
    {
        $this->assertSame("'FORMATO IPT'!B2", IptDriveLayout::a1(IptDriveLayout::FORMATO_TAB, 'B2'));
        $this->assertSame("'FORMATO IPT'!A1:L500", IptDriveLayout::a1(IptDriveLayout::FORMATO_TAB, 'A1:L500'));
        $this->assertSame('SEGUIMIENTOS!A3', IptDriveLayout::a1(IptDriveLayout::SEGUIMIENTOS_TAB, 'A3'));
        $this->assertSame('SEGUIMIENTOS!A1:L500', IptDriveLayout::a1(IptDriveLayout::SEGUIMIENTOS_TAB, 'A1:L500'));
        $this->assertSame("'O''Brien Sheet'!A1", IptDriveLayout::a1("O'Brien Sheet", 'A1'));
        $this->assertSame("'Matriz IPT'!A1", IptDriveLayout::a1('Matriz IPT', 'A1'));
        $this->assertSame('B2', IptDriveLayout::cellFromA1Range("'FORMATO IPT'!B2"));
        $this->assertSame('A1:L500', IptDriveLayout::cellFromA1Range('SEGUIMIENTOS!A1:L500'));
        $this->assertSame("'Formato Ipt 2024'!B2", IptDriveLayout::a1('Formato Ipt 2024', 'B2'));
    }

    public function test_folder_url_and_spreadsheet_url_use_verified_ids(): void
    {
        $folderId = '1AbCdEfGhIjK_workerFolder';
        $sheetId = '1e6Lr0lzrctebCCr8J5PM8z27TUKVnraU';

        $this->assertSame(
            'https://drive.google.com/drive/folders/' . $folderId,
            IptDriveLayout::folderUrl($folderId)
        );
        $this->assertSame(
            'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit',
            IptDriveLayout::spreadsheetEditUrl($sheetId)
        );
        $this->assertSame('', IptDriveLayout::folderUrl('https://evil.example/x'));
        $this->assertSame('', IptDriveLayout::spreadsheetEditUrl('not an id'));
    }

    public function test_drive_query_helpers_keep_supports_all_drives_out_of_json_body(): void
    {
        $clean = IptDriveLayout::driveFileMetadata([
            'name' => 'MAYO',
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => ['parent123'],
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
            'corpora' => 'allDrives',
            'fields' => 'id,parents',
            'addParents' => 'parent123',
        ]);
        $this->assertSame(
            [
                'name' => 'MAYO',
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => ['parent123'],
            ],
            $clean
        );

        parse_str(IptDriveLayout::driveWriteQuery(['fields' => 'id,parents']), $write);
        $this->assertSame('true', $write['supportsAllDrives']);
        $this->assertSame('id,parents', $write['fields']);

        $list = IptDriveLayout::driveListQueryParams(['q' => "name = 'Genesis'", 'pageSize' => 1]);
        $this->assertSame('true', $list['supportsAllDrives']);
        $this->assertSame('true', $list['includeItemsFromAllDrives']);
        $this->assertSame('allDrives', $list['corpora']);
        $this->assertSame("name = 'Genesis'", $list['q']);
        $this->assertSame(1, $list['pageSize']);
    }

    public function test_parents_include_checks_worker_folder_id(): void
    {
        $worker = '1workerFolderIdXx';
        $this->assertTrue(IptDriveLayout::parentsInclude([$worker, 'other'], $worker));
        $this->assertFalse(IptDriveLayout::parentsInclude(['other'], $worker));
        $this->assertFalse(IptDriveLayout::parentsInclude(null, $worker));
        $this->assertFalse(IptDriveLayout::parentsInclude([], $worker));
    }

    public function test_ipt_sync_success_payload_includes_clickable_folder_url(): void
    {
        $workerFolderId = '1WorkerFolderIdVerified';
        $spreadsheetId = '1SpreadsheetIdVerifiedX';
        $payload = IptDriveLayout::iptSyncSuccessPayload(
            $spreadsheetId,
            'PATERNINA VIERA MAYRA ALEJANDRA',
            'Genesis / Concremak sas / 2026 / MAYO / PATERNINA VIERA MAYRA ALEJANDRA',
            $workerFolderId,
            '1RootFolderIdVerifiedX',
            'Genesis',
            'jonathan.c.jimenez@gmail.com'
        );

        $this->assertSame('https://drive.google.com/drive/folders/' . $workerFolderId, $payload['folder_url']);
        $this->assertSame('https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit', $payload['spreadsheet_url']);
        $this->assertSame($workerFolderId, $payload['folder_id']);
        $this->assertSame('Genesis', $payload['root_folder_name']);
        $this->assertSame('jonathan.c.jimenez@gmail.com', $payload['oauth_email']);

        $html = IptDriveLayout::formatIptSyncSuccessHtml($payload);
        $this->assertStringContainsString('Genesis / Concremak sas / 2026 / MAYO / PATERNINA VIERA MAYRA ALEJANDRA', $html);
        $this->assertStringContainsString('href="' . $payload['folder_url'] . '"', $html);
        $this->assertStringContainsString('Abrir carpeta en Drive', $html);
        $this->assertStringContainsString('href="' . $payload['spreadsheet_url'] . '"', $html);
        $this->assertStringContainsString('Abrir hoja', $html);
        $this->assertStringContainsString('cuenta Google: jonathan.c.jimenez@gmail.com', $html);
        $this->assertStringContainsString('carpeta raíz: «Genesis»', $html);

        $escaped = IptDriveLayout::formatIptSyncSuccessHtml([
            'path' => 'Genesis / <script>alert(1)</script>',
            'name' => 'X',
            'folder_url' => 'https://evil.example/phish',
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit',
            'oauth_email' => 'a@b.com',
            'root_folder_name' => 'Root',
        ]);
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringNotContainsString('https://evil.example/phish', $escaped);
        $this->assertStringContainsString('href="https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit"', $escaped);
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
