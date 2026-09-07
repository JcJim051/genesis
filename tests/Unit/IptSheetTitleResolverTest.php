<?php

namespace Tests\Unit;

use App\Services\Google\IptDriveLayout;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class IptSheetTitleResolverTest extends TestCase
{
    public function test_normalize_trims_collapses_spaces_and_replaces_nbsp(): void
    {
        $this->assertSame('FORMATO IPT', IptDriveLayout::normalizeSheetTitle('FORMATO IPT'));
        $this->assertSame('FORMATO IPT', IptDriveLayout::normalizeSheetTitle('  FORMATO   IPT  '));
        $this->assertSame('FORMATO IPT', IptDriveLayout::normalizeSheetTitle("FORMATO\u{00A0}IPT"));
        $this->assertSame('FORMATO IPT', IptDriveLayout::normalizeSheetTitle("  FORMATO\u{00A0}\u{00A0}IPT\u{200B}  "));
        $this->assertSame('FORMATO IPT', IptDriveLayout::normalizeSheetTitle("FORMATO\u{FEFF} IPT"));
    }

    public function test_match_prefers_exact_normalized_case_insensitive_title(): void
    {
        $titles = ['Hoja 1', 'formato ipt', 'SEGUIMIENTOS'];

        $this->assertSame('formato ipt', IptDriveLayout::matchSheetTitle($titles, IptDriveLayout::FORMATO_TAB, ['FORMATO', 'IPT']));
        $this->assertSame('SEGUIMIENTOS', IptDriveLayout::matchSheetTitle($titles, IptDriveLayout::SEGUIMIENTOS_TAB, ['SEGUIMIENTO']));
    }

    public function test_match_treats_nbsp_and_extra_spaces_as_exact(): void
    {
        $formato = "FORMATO\u{00A0}IPT";
        $seguimientos = '  SEGUIMIENTOS  ';
        $titles = ['Sheet1', $formato, $seguimientos];

        $this->assertSame($formato, IptDriveLayout::matchSheetTitle($titles, 'FORMATO IPT', ['FORMATO', 'IPT']));
        $this->assertSame($seguimientos, IptDriveLayout::matchSheetTitle($titles, 'SEGUIMIENTOS', ['SEGUIMIENTO']));
    }

    public function test_match_falls_back_to_unique_contains_when_title_differs(): void
    {
        $titles = ['Hoja 1', 'FORMATO IPT 2024', 'Matriz de SEGUIMIENTO'];

        $this->assertSame('FORMATO IPT 2024', IptDriveLayout::matchSheetTitle($titles, IptDriveLayout::FORMATO_TAB, ['FORMATO', 'IPT']));
        $this->assertSame('Matriz de SEGUIMIENTO', IptDriveLayout::matchSheetTitle($titles, IptDriveLayout::SEGUIMIENTOS_TAB, ['SEGUIMIENTO']));
    }

    public function test_match_returns_null_when_contains_is_ambiguous(): void
    {
        $titles = ['FORMATO IPT A', 'FORMATO IPT B', 'SEGUIMIENTOS'];

        $this->assertNull(IptDriveLayout::matchSheetTitle($titles, IptDriveLayout::FORMATO_TAB, ['FORMATO', 'IPT']));
    }

    public function test_match_prefers_exact_over_other_contains_hits(): void
    {
        $titles = ['FORMATO IPT extra', 'FORMATO IPT', 'SEGUIMIENTOS'];

        $this->assertSame('FORMATO IPT', IptDriveLayout::matchSheetTitle($titles, IptDriveLayout::FORMATO_TAB, ['FORMATO', 'IPT']));
    }

    public function test_resolve_returns_real_google_titles_not_constants(): void
    {
        $meta = [
            'spreadsheetId' => 'abc',
            'sheets' => [
                ['properties' => ['sheetId' => 0, 'title' => "Formato\u{00A0}Ipt"]],
                ['properties' => ['sheetId' => 1, 'title' => 'Seguimientos']],
            ],
        ];

        $resolved = IptDriveLayout::resolveIptTabTitles($meta);

        $this->assertSame("Formato\u{00A0}Ipt", $resolved['formato']);
        $this->assertSame('Seguimientos', $resolved['seguimientos']);
        $this->assertNotSame(IptDriveLayout::FORMATO_TAB, $resolved['formato']);
        $this->assertNotSame(IptDriveLayout::SEGUIMIENTOS_TAB, $resolved['seguimientos']);
    }

    public function test_resolve_throws_listing_actual_titles_when_tabs_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pestañas encontradas: "Hoja 1", "Resumen".');
        $this->expectExceptionMessage('FORMATO IPT y SEGUIMIENTOS');

        IptDriveLayout::resolveIptTabTitles(['Hoja 1', 'Resumen']);
    }

    public function test_resolve_throws_when_spreadsheet_has_no_sheets(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('(ninguna)');

        IptDriveLayout::resolveIptTabTitles([]);
    }

    public function test_titles_from_spreadsheet_meta_reads_google_payload(): void
    {
        $titles = IptDriveLayout::titlesFromSpreadsheetMeta([
            'spreadsheetId' => 'xyz',
            'sheets' => [
                ['properties' => ['sheetId' => 10, 'title' => 'FORMATO IPT']],
                ['properties' => ['sheetId' => 20, 'title' => '']],
                ['properties' => ['sheetId' => 30, 'title' => 'SEGUIMIENTOS']],
            ],
        ]);

        $this->assertSame(['FORMATO IPT', 'SEGUIMIENTOS'], $titles);
    }
}
