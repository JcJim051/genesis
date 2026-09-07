<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IptADriveWiringTest extends TestCase
{
    public function test_button_label_is_exactly_ipt_a_drive(): void
    {
        $path = dirname(__DIR__, 2) . '/resources/views/vendor/backpack/crud/buttons/ipt_inspection_ipt_a_drive.blade.php';
        $this->assertFileExists($path);

        $html = file_get_contents($path);
        $this->assertIsString($html);
        $this->assertStringContainsString('Ipt a drive', $html);
        $this->assertStringContainsString("route('ipt-inspection.ipt-a-drive')", $html);
        $this->assertStringContainsString('btn btn-sm btn-outline-primary', $html);
        $this->assertStringContainsString('@csrf', $html);
    }

    public function test_route_and_list_button_are_registered_without_removing_matrix_sync(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2) . '/routes/backpack/custom.php');
        $controller = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/IptInspectionCrudController.php');
        $service = file_get_contents(dirname(__DIR__, 2) . '/app/Services/Google/GoogleSheetsMatrixService.php');

        $this->assertIsString($routes);
        $this->assertIsString($controller);
        $this->assertIsString($service);

        $this->assertStringContainsString("ipt-inspection/ipt-a-drive", $routes);
        $this->assertStringContainsString("'ipt-inspection.ipt-a-drive'", $routes);
        $this->assertStringContainsString('syncIptToDrive', $routes);

        $this->assertStringContainsString("ipt-inspection/matriz/sync-drive", $routes);
        $this->assertStringContainsString('syncMatrixToDrive', $routes);
        $this->assertStringContainsString('ipt_inspection_matrix_sync_drive', $controller);
        $this->assertStringContainsString('ipt_inspection_ipt_a_drive', $controller);
        $this->assertStringContainsString('function syncIptToDrive', $controller);
        $this->assertStringContainsString('function syncMatrixToDrive', $controller);
        $this->assertStringContainsString('baseScopedQueryForList', $controller);

        $this->assertStringContainsString('function syncIptInspectionSheet', $service);
        $this->assertStringContainsString('function syncIptCompanyMatrix', $service);
        $this->assertStringContainsString("google_drive.ipt_sheet.", $service);
        $this->assertStringContainsString("google_drive.company_sheet.", $service);
        $this->assertStringContainsString('copyIptTemplate', $service);
        $this->assertStringContainsString('ensureGenesisFolder', $service);
        $this->assertStringContainsString('IptDriveLayout', $service);
        $this->assertStringContainsString('FORMATO IPT', $service);
        $this->assertStringContainsString('SEGUIMIENTOS', $service);
        $this->assertStringContainsString('IptDriveLayout::a1(', $service);
        $this->assertStringContainsString('resolveIptTabsFromGoogle', $service);
        $this->assertStringContainsString('spreadsheetHasIptTabs', $service);
        $this->assertStringContainsString('ensureCopiedFileIsSpreadsheet', $service);
        $this->assertStringContainsString("sheets.properties(sheetId,title),spreadsheetId", $service);
        $this->assertStringContainsString("application/vnd.google-apps.spreadsheet", $service);
        $this->assertStringContainsString("'mimeType' => self::SPREADSHEET_MIME", $service);
        $this->assertStringContainsString('trashStaleSpreadsheet', $service);
        $this->assertStringNotContainsString("\$tab . '!A1:L500'", $service);
        $this->assertStringNotContainsString("\$tab . '!A' . \$rowNumber", $service);
        $this->assertStringNotContainsString("'Resumen'", $service);
        $this->assertStringNotContainsString("'Respuestas'", $service);
        $this->assertStringNotContainsString("'Requerimientos'", $service);

        $layout = file_get_contents(dirname(__DIR__, 2) . '/app/Services/Google/IptDriveLayout.php');
        $this->assertIsString($layout);
        $this->assertStringContainsString('Genesis / {Empresa} / {AÑO} / {MES} / {Trabajador}', $layout);
        $this->assertStringContainsString("1e6Lr0lzrctebCCr8J5PM8z27TUKVnraU", $layout);
        $this->assertStringContainsString("google_drive.ipt_worker_sheet.", $layout);
        $this->assertStringContainsString('function a1', $layout);
        $this->assertStringContainsString('function quoteSheetName', $layout);
        $this->assertStringContainsString('function normalizeSheetTitle', $layout);
        $this->assertStringContainsString('function matchSheetTitle', $layout);
        $this->assertStringContainsString('function resolveIptTabTitles', $layout);
        $this->assertStringContainsString('self::a1($tab, $cell)', $layout);
        $this->assertStringNotContainsString("self::FORMATO_TAB . '!' . \$cell", $layout);
    }
}
