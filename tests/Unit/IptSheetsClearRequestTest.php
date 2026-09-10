<?php

namespace Tests\Unit;

use App\Services\Google\GoogleSheetsMatrixService;
use App\Services\Google\IptDriveLayout;
use PHPUnit\Framework\TestCase;

class IptSheetsClearRequestTest extends TestCase
{
    public function test_values_clear_body_is_empty_object_not_array(): void
    {
        $body = IptDriveLayout::sheetsClearBody();
        $this->assertIsObject($body);

        $json = json_encode($body, JSON_THROW_ON_ERROR);
        $this->assertSame('{}', $json);
        $this->assertStringNotContainsString('""', $json);

        $decodedObject = json_decode($json);
        $this->assertIsObject($decodedObject);
        $this->assertSame([], get_object_vars($decodedObject));

        $decodedArray = json_decode($json, true);
        $this->assertIsArray($decodedArray);
        $this->assertArrayNotHasKey('', $decodedArray);

        $this->assertSame('[]', json_encode([]));
        $this->assertNotSame('[]', $json);
    }

    public function test_values_clear_url_targets_sheets_clear_endpoint(): void
    {
        $a1 = IptDriveLayout::formatoBodyClearA1(IptDriveLayout::FORMATO_TAB);
        $url = IptDriveLayout::sheetsValuesClearUrl('ssid123', $a1);

        $this->assertStringStartsWith('https://sheets.googleapis.com/v4/spreadsheets/ssid123/values/', $url);
        $this->assertStringEndsWith(':clear', $url);
        $this->assertStringContainsString(rawurlencode($a1), $url);
        $this->assertStringContainsString(rawurlencode("'FORMATO IPT'!A7:H250"), $url);
    }

    public function test_clear_sheet_range_posts_object_body_not_bare_url(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2) . '/app/Services/Google/GoogleSheetsMatrixService.php');
        $this->assertIsString($service);

        $this->assertStringContainsString('function clearSheetRange', $service);
        $this->assertStringContainsString('No fue posible limpiar el bloque de preguntas de FORMATO IPT', $service);
        $this->assertStringContainsString('IptDriveLayout::sheetsValuesClearUrl($spreadsheetId, $a1)', $service);
        $this->assertStringContainsString('IptDriveLayout::sheetsClearBody()', $service);
        $this->assertStringContainsString('->asJson()->post(', $service);
        $this->assertStringNotContainsString("->post(self::SHEETS_BASE . '/' . \$spreadsheetId . '/values/' . rawurlencode(\$a1) . ':clear')", $service);
        $this->assertTrue(class_exists(GoogleSheetsMatrixService::class));
    }
}
