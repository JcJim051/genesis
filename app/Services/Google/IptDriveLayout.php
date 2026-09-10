<?php

namespace App\Services\Google;

use App\Models\Empleado;
use App\Models\IptInspection;
use App\Models\User;
use App\Services\Ipt\IptFormLayout;
use Carbon\Carbon;
use DateTimeInterface;
use RuntimeException;

/**
 * Maps IPT inspections onto the team's Excel/Drive template
 * (FORMATO IPT + SEGUIMIENTOS) and the folder path
 * Genesis / {Empresa} / {AÑO} / {MES} / {Trabajador}/.
 *
 * The spreadsheet copy supplies graphic structure (header, SI/NO/N/A
 * columns, footer blocks). Checklist titles, questions, answers,
 * scores, notes and photos always come from the Genesis IPT record.
 */
class IptDriveLayout
{
    public const DEFAULT_TEMPLATE_ID = '1e6Lr0lzrctebCCr8J5PM8z27TUKVnraU';

    public const FORMATO_TAB = 'FORMATO IPT';

    public const SEGUIMIENTOS_TAB = 'SEGUIMIENTOS';

    public const GENESIS_FOLDER = 'Genesis';

    /** @var array<int, string> */
    public const MONTHS_ES = [
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

    /** First body row under the identity header (section titles + questions). */
    public const CHECKLIST_START_ROW = 7;

    /** Clears leftover plantilla VDT question labels before writing DB content. */
    public const FORMATO_BODY_CLEAR_RANGE = 'A7:H250';

    /**
     * Canonical SEGUIMIENTOS columns (A–L). Values are aligned to the
     * copied sheet's header row by name so Hallazgos never lands in Área/Cargo.
     *
     * @var list<string>
     */
    public const SEGUIMIENTOS_HEADERS = [
        'FECHA',
        'IDENTIFICACION',
        'NOMBRE COMPLETO',
        'AREA',
        'CARGO',
        'HALLAZGOS',
        'RECOMENDACIONES',
        'REQUERIMIENTOS',
        'FECHA DE SEGUIMIENTO',
        'SEGUIMIENTO EXITOSO',
        'OBSERVACIONES DE SEGUIMIENTO',
        'ESTADO',
    ];

    /**
     * Normalized requirement label => cell on row 59 (X mark).
     *
     * @var array<string, string>
     */
    public const REQUIREMENT_CELLS = [
        'MANTENIMIENTO DE SILLA' => 'A59',
        'CAMBIO DE SILLA' => 'D59',
        'APOYAPIES' => 'E59',
        'KIT ERGONOMICO PORTATIL' => 'F59',
        'SOPORTE PARA MONITOR' => 'G59',
        'TECLADO' => 'H59',
    ];

    /**
     * @var array<string, int>
     */
    public const RISK_MARK_ROWS = [
        'bajo' => 61,
        'medio' => 62,
        'alto' => 63,
    ];

    public static function extractSpreadsheetId(string $value): string
    {
        $value = trim($value);
        if ($value !== '' && preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $value, $m)) {
            return $m[1];
        }

        return $value;
    }

    public static function spanishMonthName(DateTimeInterface|string|null $date): string
    {
        $carbon = self::asCarbon($date) ?? Carbon::now();
        $month = (int) $carbon->month;

        return self::MONTHS_ES[$month] ?? 'ENERO';
    }

    public static function year(DateTimeInterface|string|null $date): string
    {
        $carbon = self::asCarbon($date) ?? Carbon::now();

        return $carbon->format('Y');
    }

    public static function isFollowup(IptInspection $inspection): bool
    {
        return $inspection->tipo === 'followup';
    }

    /**
     * Date that owns the monthly worker spreadsheet.
     *
     * Follow-ups (`tipo === followup`) use the linked initial inspection date
     * so they update the same file instead of creating a new month folder.
     * If the initial is missing, the follow-up's own date is used.
     */
    public static function folderDate(IptInspection $inspection): Carbon
    {
        if (self::isFollowup($inspection)) {
            $initial = $inspection->initialInspection;
            if ($initial?->fecha_inspeccion) {
                return self::asCarbon($initial->fecha_inspeccion) ?? Carbon::now();
            }
        }

        return self::asCarbon($inspection->fecha_inspeccion) ?? Carbon::now();
    }

    public static function empleado(IptInspection $inspection): ?Empleado
    {
        return $inspection->empleado ?: $inspection->programaCaso?->empleado;
    }

    public static function empresaNombre(IptInspection $inspection): string
    {
        $empleado = self::empleado($inspection);
        $nombre = trim((string) ($empleado?->cliente?->nombre ?? ''));
        if ($nombre !== '') {
            return self::sanitizeDriveName($nombre);
        }

        $clienteId = (int) ($inspection->cliente_id ?? $empleado?->cliente_id ?? 0);

        return $clienteId > 0 ? 'Empresa_' . $clienteId : 'Empresa';
    }

    public static function workerDisplayName(IptInspection $inspection): string
    {
        $nombre = trim((string) (self::empleado($inspection)?->nombre ?? ''));
        if ($nombre === '') {
            $nombre = 'Sin nombre';
        }

        return mb_strtoupper(self::sanitizeDriveName($nombre), 'UTF-8');
    }

    /**
     * Folder names under the Genesis root: Empresa / AÑO / MES / Trabajador.
     *
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    public static function pathAfterGenesis(IptInspection $inspection): array
    {
        $date = self::folderDate($inspection);

        return [
            self::empresaNombre($inspection),
            self::year($date),
            self::spanishMonthName($date),
            self::workerDisplayName($inspection),
        ];
    }

    /**
     * Human-readable path including the Genesis folder name.
     */
    public static function displayPath(IptInspection $inspection): string
    {
        return self::GENESIS_FOLDER . ' / ' . implode(' / ', self::pathAfterGenesis($inspection));
    }

    public static function spreadsheetTitle(IptInspection $inspection): string
    {
        return self::workerDisplayName($inspection);
    }

    /**
     * Drive folder URL that opens the real location (not a guessed path).
     */
    public static function folderUrl(string $folderId): string
    {
        $folderId = self::sanitizeDriveFileId($folderId);

        return $folderId === '' ? '' : 'https://drive.google.com/drive/folders/' . $folderId;
    }

    public static function spreadsheetEditUrl(string $spreadsheetId): string
    {
        $spreadsheetId = self::sanitizeDriveFileId($spreadsheetId);

        return $spreadsheetId === '' ? '' : 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit';
    }

    /**
     * Keep only File resource fields for Drive files.create / files.copy JSON bodies.
     * Query flags such as supportsAllDrives must never be sent as metadata.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function driveFileMetadata(array $metadata): array
    {
        unset(
            $metadata['supportsAllDrives'],
            $metadata['includeItemsFromAllDrives'],
            $metadata['corpora'],
            $metadata['fields'],
            $metadata['addParents'],
            $metadata['removeParents']
        );

        return $metadata;
    }

    /**
     * Query string for Drive write calls (create / copy / update / get-by-id).
     *
     * @param  array<string, scalar>  $extra
     */
    public static function driveWriteQuery(array $extra = []): string
    {
        return http_build_query(array_merge([
            'supportsAllDrives' => 'true',
        ], $extra));
    }

    /**
     * Query params for Drive files.list so Shared Drives are visible.
     *
     * @param  array<string, scalar>  $extra
     * @return array<string, scalar>
     */
    public static function driveListQueryParams(array $extra = []): array
    {
        return array_merge([
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
            'corpora' => 'allDrives',
        ], $extra);
    }

    /**
     * @param  mixed  $parents
     */
    public static function parentsInclude(mixed $parents, string $parentId): bool
    {
        $parentId = trim($parentId);
        if ($parentId === '' || ! is_array($parents)) {
            return false;
        }

        foreach ($parents as $parent) {
            if ((string) $parent === $parentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Payload returned after a verified IPT → Drive sync.
     *
     * @return array{
     *     spreadsheet_id: string,
     *     spreadsheet_url: string,
     *     folder_id: string,
     *     folder_url: string,
     *     name: string,
     *     path: string,
     *     root_folder_id: string,
     *     root_folder_name: string,
     *     oauth_email: string
     * }
     */
    public static function iptSyncSuccessPayload(
        string $spreadsheetId,
        string $spreadsheetName,
        string $path,
        string $workerFolderId,
        string $rootFolderId,
        string $rootFolderName,
        string $oauthEmail
    ): array {
        return [
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => self::spreadsheetEditUrl($spreadsheetId),
            'folder_id' => $workerFolderId,
            'folder_url' => self::folderUrl($workerFolderId),
            'name' => $spreadsheetName,
            'path' => $path,
            'root_folder_id' => $rootFolderId,
            'root_folder_name' => $rootFolderName,
            'oauth_email' => $oauthEmail,
        ];
    }

    /**
     * HTML flash line: display path, clickable folder + sheet URLs, OAuth/root hint.
     *
     * @param  array<string, mixed>  $result
     */
    public static function formatIptSyncSuccessHtml(array $result): string
    {
        $path = self::escape((string) ($result['path'] ?? ''));
        $name = self::escape((string) ($result['name'] ?? ''));
        $folderUrl = (string) ($result['folder_url'] ?? '');
        $sheetUrl = (string) ($result['spreadsheet_url'] ?? '');
        $oauth = self::escape((string) ($result['oauth_email'] ?? ''));
        $rootName = self::escape((string) ($result['root_folder_name'] ?? ''));
        $rootId = self::escape((string) ($result['root_folder_id'] ?? ''));

        $title = trim($path, ' /');
        if ($name !== '') {
            $title = $title !== '' ? $title . ' / ' . $name : $name;
        }

        $parts = [];
        if ($title !== '') {
            $parts[] = '<strong>' . $title . '</strong>';
        }
        if (self::isSafeGoogleUrl($folderUrl)) {
            $parts[] = '<a href="' . self::escape($folderUrl) . '" target="_blank" rel="noopener noreferrer">Abrir carpeta en Drive</a>';
        }
        if (self::isSafeGoogleUrl($sheetUrl)) {
            $parts[] = '<a href="' . self::escape($sheetUrl) . '" target="_blank" rel="noopener noreferrer">Abrir hoja</a>';
        }

        $html = implode(' — ', $parts);

        $location = [];
        if ($oauth !== '') {
            $location[] = 'cuenta Google: ' . $oauth;
        }
        if ($rootName !== '') {
            $location[] = 'carpeta raíz: «' . $rootName . '»';
        } elseif ($rootId !== '') {
            $location[] = 'carpeta raíz id: ' . $rootId;
        }

        if ($location !== []) {
            $html .= '<br><small>Busca aquí: ' . implode(' · ', $location) . '</small>';
        }

        return $html;
    }

    public static function sanitizeDriveFileId(string $id): string
    {
        $id = trim($id);

        return preg_match('/^[a-zA-Z0-9_-]+$/', $id) === 1 ? $id : '';
    }

    public static function isSafeGoogleUrl(string $url): bool
    {
        return (bool) preg_match('#^https://(?:drive|docs)\.google\.com/#', $url);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function workerSheetSettingsKey(IptInspection $inspection): string
    {
        $date = self::folderDate($inspection);
        $parts = [
            mb_strtolower(self::empresaNombre($inspection), 'UTF-8'),
            self::year($date),
            self::spanishMonthName($date),
            mb_strtolower(self::workerDisplayName($inspection), 'UTF-8'),
        ];

        return 'google_drive.ipt_worker_sheet.' . sha1(implode('|', $parts));
    }

    public static function sanitizeDriveName(string $name): string
    {
        $name = preg_replace('/[\\\\\\/]+/', '-', $name) ?? $name;
        $name = preg_replace('/[\\x00-\\x1F]+/', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\\s+/', ' ', $name) ?? $name);

        return mb_substr($name !== '' ? $name : 'IPT', 0, 200);
    }

    /**
     * Quote a sheet title for Sheets API A1 notation when required.
     *
     * Names with spaces or special characters must be wrapped in single quotes
     * (e.g. FORMATO IPT → 'FORMATO IPT'). Existing quotes are doubled.
     * Simple identifiers such as SEGUIMIENTOS stay unquoted.
     */
    public static function quoteSheetName(string $sheetName): string
    {
        if (! self::sheetNameNeedsQuotes($sheetName)) {
            return $sheetName;
        }

        return "'" . str_replace("'", "''", $sheetName) . "'";
    }

    /**
     * A1 range for values.update / values.batchUpdate.
     *
     * Quotes the tab when it contains spaces or special characters:
     * FORMATO IPT!B2 → 'FORMATO IPT'!B2
     */
    public static function a1(string $tab, string $cell): string
    {
        return self::quoteSheetName($tab) . '!' . $cell;
    }

    private static function sheetNameNeedsQuotes(string $sheetName): bool
    {
        return $sheetName === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $sheetName);
    }

    /**
     * Cell/range portion after the sheet prefix (quoted or not).
     */
    public static function cellFromA1Range(string $range): string
    {
        $pos = strrpos($range, '!');

        return $pos === false ? $range : substr($range, $pos + 1);
    }

    /**
     * Normalize a Google sheet title for matching.
     *
     * Trims, replaces NBSP / other invisible spaces, and collapses whitespace.
     * Does not change case; callers compare case-insensitively.
     */
    public static function normalizeSheetTitle(string $title): string
    {
        $title = str_replace(
            ["\u{00A0}", "\u{2007}", "\u{202F}", "\u{FEFF}", "\u{200B}", "\u{200C}", "\u{200D}"],
            ' ',
            $title
        );
        $title = trim($title);

        return trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
    }

    /**
     * @param  list<string>|array<int, array{properties?: array{title?: mixed}}>  $sheetsOrTitles
     * @return list<string>
     */
    public static function titlesFromSpreadsheetMeta(array $sheetsOrTitles): array
    {
        if (isset($sheetsOrTitles['sheets']) && is_array($sheetsOrTitles['sheets'])) {
            $sheetsOrTitles = $sheetsOrTitles['sheets'];
        }

        $titles = [];
        foreach ($sheetsOrTitles as $item) {
            if (is_string($item)) {
                $title = $item;
            } else {
                $title = (string) ($item['properties']['title'] ?? $item['title'] ?? '');
            }
            if ($title !== '') {
                $titles[] = $title;
            }
        }

        return $titles;
    }

    /**
     * Return the real sheet title from Google that matches a preferred name.
     *
     * Prefers an exact match after normalize + case-insensitive compare.
     * Otherwise a unique contains-match on every needle (also normalized).
     * Returns the original Google title (byte-for-byte), not the preferred constant.
     *
     * @param  list<string>  $titles
     * @param  list<string>  $mustContain
     */
    public static function matchSheetTitle(array $titles, string $preferred, array $mustContain = []): ?string
    {
        $normalizedPreferred = mb_strtolower(self::normalizeSheetTitle($preferred), 'UTF-8');

        $normalized = [];
        foreach ($titles as $title) {
            if (! is_string($title) || $title === '') {
                continue;
            }
            $normalized[] = [
                'raw' => $title,
                'norm' => mb_strtolower(self::normalizeSheetTitle($title), 'UTF-8'),
            ];
        }

        $exact = [];
        foreach ($normalized as $item) {
            if ($item['norm'] === $normalizedPreferred) {
                $exact[] = $item['raw'];
            }
        }
        if ($exact !== []) {
            return $exact[0];
        }

        $needles = [];
        foreach ($mustContain as $needle) {
            $needle = mb_strtolower(self::normalizeSheetTitle((string) $needle), 'UTF-8');
            if ($needle !== '') {
                $needles[] = $needle;
            }
        }
        if ($needles === []) {
            return null;
        }

        $contains = [];
        foreach ($normalized as $item) {
            foreach ($needles as $needle) {
                if (! str_contains($item['norm'], $needle)) {
                    continue 2;
                }
            }
            $contains[] = $item['raw'];
        }

        return count($contains) === 1 ? $contains[0] : null;
    }

    /**
     * Resolve the real FORMATO IPT and SEGUIMIENTOS tab titles from Google.
     *
     * Constants remain the preferred names; the returned strings are the
     * actual titles in the spreadsheet (spaces, NBSP, casing, etc.).
     *
     * @param  list<string>|array<string, mixed>  $titlesOrMeta
     * @return array{formato: string, seguimientos: string}
     */
    public static function resolveIptTabTitles(array $titlesOrMeta): array
    {
        $titles = self::titlesFromSpreadsheetMeta($titlesOrMeta);
        $formato = self::matchSheetTitle($titles, self::FORMATO_TAB, ['FORMATO', 'IPT']);
        $seguimientos = self::matchSheetTitle($titles, self::SEGUIMIENTOS_TAB, ['SEGUIMIENTO']);

        if ($formato === null || $seguimientos === null) {
            $listed = $titles === []
                ? '(ninguna)'
                : implode(', ', array_map(static fn (string $title): string => '"' . $title . '"', $titles));

            throw new RuntimeException(
                'La hoja de cálculo IPT no tiene las pestañas requeridas ('
                . self::FORMATO_TAB . ' y ' . self::SEGUIMIENTOS_TAB
                . '). Pestañas encontradas: ' . $listed . '.'
            );
        }

        return [
            'formato' => $formato,
            'seguimientos' => $seguimientos,
        ];
    }

    public static function formatoBodyClearA1(string $formatoTab): string
    {
        return self::a1($formatoTab, self::FORMATO_BODY_CLEAR_RANGE);
    }

    /**
     * Body for spreadsheets.values.clear. Google expects a JSON object
     * (`{}`). An empty PHP array encodes as `[]` and Sheets returns
     * INVALID_ARGUMENT / Unknown name "".
     */
    public static function sheetsClearBody(): object
    {
        return (object) [];
    }

    /**
     * POST URL for spreadsheets.values.clear.
     */
    public static function sheetsValuesClearUrl(string $spreadsheetId, string $a1): string
    {
        return 'https://sheets.googleapis.com/v4/spreadsheets/'
            . rawurlencode($spreadsheetId)
            . '/values/'
            . rawurlencode($a1)
            . ':clear';
    }

    /**
     * Checklist body from the IPT's Genesis template (sections → questions → answers).
     *
     * @return list<array{type: string, titulo?: string, number?: int, texto?: string, si?: string, no?: string, na?: string}>
     */
    public static function checklistBody(IptInspection $inspection): array
    {
        $rows = [];
        $template = $inspection->template;
        if (! $template) {
            return $rows;
        }

        $answersByQuestion = $inspection->answers?->keyBy('question_id') ?? collect();
        $number = 0;

        foreach (IptFormLayout::visibleQuestionSections($template) as $section) {
            $titulo = trim((string) ($section->titulo ?? ''));
            $rows[] = [
                'type' => 'section',
                'titulo' => $titulo !== '' ? $titulo : 'Sección',
            ];

            foreach (IptFormLayout::sectionQuestions($section)->sortBy('orden')->values() as $question) {
                $number++;
                $ans = $answersByQuestion->get($question->id);
                $kind = self::answerKind($ans?->respuesta ?? null);
                $rows[] = [
                    'type' => 'question',
                    'number' => $number,
                    'texto' => (string) ($question->texto ?? ''),
                    'si' => $kind === 'si' ? 'X' : '',
                    'no' => $kind === 'no' ? 'X' : '',
                    'na' => $kind === 'na' ? 'X' : '',
                ];
            }
        }

        return $rows;
    }

    /**
     * Sheets API value ranges for FORMATO IPT.
     *
     * Header cells stay in the plantilla graphic (B2…H4, A5). The checklist
     * block is rebuilt from the IPT in Genesis, then totals / notes / photos
     * are placed immediately under that block.
     *
     * @param  array{inicial?: string, despues?: string}  $photoLinks
     * @return list<array{range: string, values: array<int, array<int, mixed>>}>
     */
    public static function formatoValueRanges(IptInspection $inspection, array $photoLinks = [], ?string $formatoTab = null): array
    {
        $tab = ($formatoTab !== null && $formatoTab !== '') ? $formatoTab : self::FORMATO_TAB;
        $empleado = self::empleado($inspection);
        $fecha = self::asCarbon($inspection->fecha_inspeccion);
        $ranges = [];

        $push = function (string $cell, mixed $value) use (&$ranges, $tab) {
            $ranges[] = [
                'range' => self::a1($tab, $cell),
                'values' => [[$value]],
            ];
        };

        $push('B2', $fecha ? $fecha->format('d/m/Y') : '');
        $push('E2', self::workerDisplayName($inspection));
        $push('H2', trim((string) ($empleado?->cedula ?? '')));

        $push('B3', self::workerEdad($empleado));
        $push('E3', self::workerArea($empleado));
        $push('H3', trim((string) ($empleado?->sucursal?->nombre ?? '')));

        $push('B4', trim((string) ($empleado?->cliente?->ciudad ?? '')));
        $push('E4', self::workerCargo($empleado));
        $push('H4', self::workerAntiguedad($empleado, $fecha));

        $inspector = self::inspectorLabel($inspection);
        $push('A5', 'Profesional que realiza la inspección (Nombre/cargo): ' . $inspector);

        $body = self::checklistBody($inspection);
        $start = self::CHECKLIST_START_ROW;
        $grid = [];
        foreach ($body as $item) {
            if (($item['type'] ?? '') === 'section') {
                $grid[] = [(string) ($item['titulo'] ?? ''), '', '', '', '', '', '', ''];
                continue;
            }
            $grid[] = [
                (int) ($item['number'] ?? 0),
                (string) ($item['texto'] ?? ''),
                '',
                '',
                '',
                (string) ($item['si'] ?? ''),
                (string) ($item['no'] ?? ''),
                (string) ($item['na'] ?? ''),
            ];
        }

        if ($grid === []) {
            $grid[] = ['', '(Sin preguntas en la plantilla IPT de Genesis)', '', '', '', '', '', ''];
        }

        $end = $start + count($grid) - 1;
        $ranges[] = [
            'range' => self::a1($tab, 'A' . $start . ':H' . $end),
            'values' => $grid,
        ];

        $row = $end + 2;
        $puntaje = $inspection->puntaje_total !== null ? (int) $inspection->puntaje_total : '';
        $push('A' . $row, 'Puntaje total');
        $push('F' . $row, $puntaje);

        $row += 2;
        $reqRows = self::requirementFooterRows($inspection);
        if ($reqRows !== []) {
            $push('A' . $row, 'Requerimientos de estación');
            $row++;
            foreach ($reqRows as $reqRow) {
                $push('A' . $row, $reqRow['nombre']);
                $push('F' . $row, $reqRow['marca']);
                $row++;
            }
            $row++;
        }

        $nivel = mb_strtoupper(trim((string) ($inspection->nivel_riesgo ?? '')), 'UTF-8');
        $push('A' . $row, 'Nivel de riesgo');
        $push('B' . $row, $nivel !== '' ? $nivel : '—');
        $push('E' . $row, $puntaje);
        $row += 2;

        $template = $inspection->template ?? (object) [];
        if (IptFormLayout::showsHallazgosObservacionesField($template)) {
            $push('A' . $row, IptFormLayout::hallazgosObservacionesLabel($template));
            $push('B' . $row, (string) ($inspection->hallazgos ?? ''));
            $row++;
        }
        if (IptFormLayout::showsRecomendaciones($template)) {
            $push('A' . $row, 'Recomendaciones');
            $push('B' . $row, (string) ($inspection->recomendaciones ?? ''));
            $row++;
        }
        if (IptFormLayout::showsAccion($template)) {
            $accion = trim((string) ($inspection->accion ?? ''));
            if ($accion === '' && ! IptFormLayout::showsRecomendaciones($template)) {
                $accion = trim((string) ($inspection->recomendaciones ?? ''));
            }
            $push('A' . $row, 'Acción');
            $push('B' . $row, $accion);
            $row++;
        }
        if (IptFormLayout::showsResponsable($template)) {
            $push('A' . $row, 'Responsable');
            $push('E' . $row, trim((string) ($inspection->responsable ?? '')));
            $row += 2;
        } else {
            $row++;
        }

        $push('A' . $row, 'Evidencia fotográfica');
        $row++;
        $push('A' . $row, 'Inicial / general');
        $push('E' . $row, 'Después');
        $row++;
        $push('A' . $row, self::photoCellValue((string) ($photoLinks['inicial'] ?? '')));
        $push('E' . $row, self::photoCellValue((string) ($photoLinks['despues'] ?? '')));
        $row += 2;

        $creatorName = trim((string) ($inspection->creator?->name ?? ''));
        $push('A' . $row, 'Firma profesional: ' . ($creatorName !== '' ? $creatorName : '_________________________'));

        return $ranges;
    }

    /**
     * @param  array{inicial?: string, despues?: string}  $photoLinks
     * @return array<string, mixed>
     */
    public static function formatoCellMap(IptInspection $inspection, array $photoLinks = []): array
    {
        $map = [];
        foreach (self::formatoValueRanges($inspection, $photoLinks) as $range) {
            $a1 = self::cellFromA1Range($range['range']);
            $values = $range['values'] ?? [];
            $parsed = self::parseA1Start($a1);
            foreach ($values as $r => $cols) {
                if (! is_array($cols)) {
                    continue;
                }
                foreach (array_values($cols) as $c => $value) {
                    $map[self::columnLetter($parsed['col'] + $c) . ($parsed['row'] + $r)] = $value;
                }
            }
        }

        return $map;
    }

    /**
     * @return list<array{nombre: string, marca: string}>
     */
    public static function requirementFooterRows(IptInspection $inspection): array
    {
        $rows = [];
        foreach ($inspection->requirements ?? [] as $req) {
            $nombre = trim((string) ($req->requirement?->nombre ?? ''));
            if ($nombre === '') {
                continue;
            }
            $rows[] = [
                'nombre' => $nombre,
                'marca' => $req->aplica ? 'X' : '',
            ];
        }

        return $rows;
    }

    public static function isUnusableLocalUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://(?:127\.0\.0\.1|localhost|0\.0\.0\.0|\[::1\])(?::\d+)?(?:/|$)#i', $url);
    }

    /**
     * Value for an evidence cell: IMAGE() for Drive/hosted files; never APP_URL localhost.
     */
    public static function photoCellValue(string $linkOrFormula): string
    {
        $link = trim($linkOrFormula);
        if ($link === '' || self::isUnusableLocalUrl($link)) {
            return '';
        }
        if (str_starts_with($link, '=')) {
            return $link;
        }

        $fileId = '';
        if (preg_match('#(?:drive\.google\.com/uc\?[^"\s]*id=|drive\.google\.com/file/d/|lh3\.googleusercontent\.com/d/)([a-zA-Z0-9_-]+)#i', $link, $m)) {
            $fileId = $m[1];
        } elseif (preg_match('#^[a-zA-Z0-9_-]{20,}$#', $link)) {
            $fileId = $link;
        }

        if ($fileId !== '') {
            return '=IMAGE("https://drive.google.com/uc?export=view&id=' . $fileId . '")';
        }

        if (preg_match('#^https://#i', $link)) {
            if (preg_match('#\.(jpe?g|png|gif|webp)(\?|$)#i', $link)) {
                return '=IMAGE("' . str_replace('"', '""', $link) . '")';
            }

            return $link;
        }

        return '';
    }

    public static function driveFileViewUrl(string $fileId): string
    {
        $fileId = self::sanitizeDriveFileId($fileId);

        return $fileId === '' ? '' : 'https://drive.google.com/file/d/' . $fileId . '/view';
    }

    /**
     * @return array{col: int, row: int}
     */
    public static function parseA1Start(string $a1): array
    {
        $a1 = explode(':', $a1)[0];
        if (! preg_match('/^([A-Za-z]+)(\d+)$/', $a1, $m)) {
            return ['col' => 1, 'row' => 1];
        }

        return [
            'col' => self::columnIndex($m[1]),
            'row' => (int) $m[2],
        ];
    }

    public static function columnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $n = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }

        return $n;
    }

    public static function columnLetter(int $index): string
    {
        $index = max(1, $index);
        $letters = '';
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    /**
     * One SEGUIMIENTOS matrix row (A–L) in canonical order.
     *
     * @return array<int, string>
     */
    public static function seguimientosRow(IptInspection $inspection): array
    {
        $empleado = self::empleado($inspection);
        $fecha = self::asCarbon($inspection->fecha_inspeccion);
        $reqs = [];
        foreach ($inspection->requirements ?? [] as $req) {
            if (! $req->aplica) {
                continue;
            }
            $nombre = trim((string) ($req->requirement?->nombre ?? ''));
            if ($nombre !== '') {
                $reqs[] = $nombre;
            }
        }

        $fechaSeguimiento = '';
        $exitoso = '';
        $observaciones = '';

        if (self::isFollowup($inspection)) {
            $fechaSeguimiento = $fecha ? $fecha->format('d/m/Y') : '';
            if ($inspection->seguimiento_exitoso === true) {
                $exitoso = 'SI';
            } elseif ($inspection->seguimiento_exitoso === false) {
                $exitoso = 'NO';
            }
            $observaciones = trim((string) ($inspection->hallazgos ?? ''));
        } else {
            $proxima = self::asCarbon($inspection->fecha_proximo_seguimiento_sugerida);
            $fechaSeguimiento = $proxima ? $proxima->format('d/m/Y') : '';
        }

        $estado = mb_strtoupper(trim((string) ($inspection->estado ?? '')), 'UTF-8');

        return [
            $fecha ? $fecha->format('d/m/Y') : '',
            trim((string) ($empleado?->cedula ?? '')),
            self::workerDisplayName($inspection),
            self::workerArea($empleado),
            self::workerCargo($empleado),
            (string) ($inspection->hallazgos ?? ''),
            (string) ($inspection->recomendaciones ?? ''),
            implode(', ', $reqs),
            $fechaSeguimiento,
            $exitoso,
            $observaciones,
            $estado,
        ];
    }

    /**
     * Place canonical SEGUIMIENTOS values into the sheet's header order.
     *
     * @param  array<int, mixed>  $headerRow
     * @param  array<int, string>  $canonical
     * @return list<string>
     */
    public static function alignSeguimientosRow(array $headerRow, array $canonical): array
    {
        $canonical = array_values($canonical);
        while (count($canonical) < count(self::SEGUIMIENTOS_HEADERS)) {
            $canonical[] = '';
        }

        $headers = [];
        foreach (array_values($headerRow) as $cell) {
            $headers[] = self::normalizeHeaderLabel((string) $cell);
        }

        $indexByKey = self::seguimientosHeaderIndexMap($headers);
        if ($indexByKey === []) {
            return $canonical;
        }

        $width = max(count($headers), count(self::SEGUIMIENTOS_HEADERS), 12);
        $out = array_fill(0, $width, '');
        foreach (self::SEGUIMIENTOS_HEADERS as $i => $label) {
            $key = self::seguimientosHeaderKey($label);
            $target = $indexByKey[$key] ?? $i;
            $out[$target] = (string) ($canonical[$i] ?? '');
        }

        return $out;
    }

    /**
     * @param  list<string>  $normalizedHeaders
     * @return array<string, int>
     */
    public static function seguimientosHeaderIndexMap(array $normalizedHeaders): array
    {
        $map = [];
        foreach ($normalizedHeaders as $index => $header) {
            if ($header === '') {
                continue;
            }
            $key = self::seguimientosHeaderKey($header);
            if ($key !== '' && ! isset($map[$key])) {
                $map[$key] = $index;
            }
        }

        $needed = ['area', 'cargo', 'hallazgos', 'identificacion', 'nombre'];
        $hits = 0;
        foreach ($needed as $key) {
            if (isset($map[$key])) {
                $hits++;
            }
        }

        return $hits >= 3 ? $map : [];
    }

    public static function seguimientosHeaderKey(string $label): string
    {
        $n = self::normalizeHeaderLabel($label);
        if ($n === '') {
            return '';
        }
        if (str_contains($n, 'HALLAZGO')) {
            return 'hallazgos';
        }
        if (str_contains($n, 'RECOMENDACION')) {
            return 'recomendaciones';
        }
        if (str_contains($n, 'REQUERIMIENTO')) {
            return 'requerimientos';
        }
        if (str_contains($n, 'FECHA') && str_contains($n, 'SEGUIMIENTO')) {
            return 'fecha_seguimiento';
        }
        if (str_contains($n, 'EXITOSO')) {
            return 'exitoso';
        }
        if (str_contains($n, 'OBSERVACION')) {
            return 'observaciones';
        }
        if ($n === 'ESTADO' || str_ends_with($n, ' ESTADO')) {
            return 'estado';
        }
        if (str_contains($n, 'CARGO')) {
            return 'cargo';
        }
        if ($n === 'AREA' || str_starts_with($n, 'AREA ') || str_contains($n, ' AREA')) {
            return 'area';
        }
        if (str_contains($n, 'NOMBRE')) {
            return 'nombre';
        }
        if (str_contains($n, 'IDENTIFIC') || str_contains($n, 'CEDULA') || str_contains($n, 'DOCUMENTO')) {
            return 'identificacion';
        }
        if ($n === 'FECHA' || str_starts_with($n, 'FECHA ')) {
            return 'fecha';
        }

        return $n;
    }

    public static function normalizeHeaderLabel(string $label): string
    {
        $label = mb_strtoupper(trim($label), 'UTF-8');
        $label = strtr($label, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'Ñ' => 'N',
        ]);
        $label = preg_replace('/[^A-Z0-9]+/', ' ', $label) ?? $label;

        return trim(preg_replace('/\s+/', ' ', $label) ?? $label);
    }

    public static function requirementCell(string $nombre): ?string
    {
        $normalized = self::normalizeRequirementName($nombre);
        if ($normalized === '') {
            return null;
        }

        if (isset(self::REQUIREMENT_CELLS[$normalized])) {
            return self::REQUIREMENT_CELLS[$normalized];
        }

        foreach (self::REQUIREMENT_CELLS as $label => $cell) {
            if (str_contains($normalized, $label) || str_contains($label, $normalized)) {
                return $cell;
            }
        }

        // Common aliases from the VDT seed / iPad wording.
        $aliases = [
            'MANTENIMIENTO SILLA' => 'MANTENIMIENTO DE SILLA',
            'APOYA PIES' => 'APOYAPIES',
            'APOYAPIES' => 'APOYAPIES',
            'KIT ERGONOMICO' => 'KIT ERGONOMICO PORTATIL',
            'SOPORTE MONITOR' => 'SOPORTE PARA MONITOR',
            'SOPORTE PARA MONITOR' => 'SOPORTE PARA MONITOR',
        ];
        foreach ($aliases as $alias => $canonical) {
            if (str_contains($normalized, $alias) || str_contains($alias, $normalized)) {
                return self::REQUIREMENT_CELLS[$canonical] ?? null;
            }
        }

        return null;
    }

    public static function normalizeRequirementName(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre), 'UTF-8');
        $nombre = strtr($nombre, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
        ]);
        $nombre = preg_replace('/[^A-Z0-9]+/', ' ', $nombre) ?? $nombre;

        return trim(preg_replace('/\s+/', ' ', $nombre) ?? $nombre);
    }

    private static function answerKind(mixed $respuesta): string
    {
        $value = mb_strtolower(trim((string) $respuesta), 'UTF-8');
        $value = strtr($value, ['í' => 'i', 'Í' => 'i']);

        if (in_array($value, ['si', 'sí', 'yes', '1'], true)) {
            return 'si';
        }
        if (in_array($value, ['no', '0'], true)) {
            return 'no';
        }
        if (in_array($value, ['na', 'n/a', 'n.a.', 'n.a', 'ns'], true)) {
            return 'na';
        }

        return '';
    }

    private static function workerEdad(?Empleado $empleado): string|int
    {
        if (! $empleado) {
            return '';
        }
        if ($empleado->edad !== null && $empleado->edad !== '') {
            return (int) $empleado->edad;
        }

        return '';
    }

    private static function workerArea(?Empleado $empleado): string
    {
        if (! $empleado) {
            return '';
        }

        if ($empleado->relationLoaded('areas') && $empleado->areas && $empleado->areas->isNotEmpty()) {
            $current = $empleado->areas->first(fn ($item) => empty($item->fecha_fin))
                ?: $empleado->areas->sortByDesc(fn ($item) => optional($item->fecha_inicio)?->timestamp ?? 0)->first();
            $area = trim((string) ($current?->area ?? ''));
            if ($area !== '') {
                return $area;
            }
        }

        return '';
    }

    private static function workerCargo(?Empleado $empleado): string
    {
        if (! $empleado) {
            return '';
        }

        if ($empleado->relationLoaded('cargos') && $empleado->cargos && $empleado->cargos->isNotEmpty()) {
            $current = $empleado->cargos->first(fn ($item) => empty($item->fecha_fin))
                ?: $empleado->cargos->sortByDesc(fn ($item) => optional($item->fecha_inicio)?->timestamp ?? 0)->first();
            $cargo = trim((string) ($current?->cargo ?? ''));
            if ($cargo !== '') {
                return $cargo;
            }
        }

        return trim((string) ($empleado->cargo ?? ''));
    }

    private static function workerAntiguedad(?Empleado $empleado, ?Carbon $fecha): string
    {
        if (! $empleado) {
            return '';
        }

        $inicio = null;
        if (! empty($empleado->fecha_ingreso)) {
            $inicio = self::asCarbon($empleado->fecha_ingreso);
        }
        if (! $inicio && $empleado->relationLoaded('cargos') && $empleado->cargos && $empleado->cargos->isNotEmpty()) {
            $current = $empleado->cargos->first(fn ($item) => empty($item->fecha_fin))
                ?: $empleado->cargos->sortByDesc(fn ($item) => optional($item->fecha_inicio)?->timestamp ?? 0)->first();
            $inicio = self::asCarbon($current?->fecha_inicio);
        }

        if (! $inicio) {
            return '';
        }

        $hasta = $fecha ? $fecha->copy() : Carbon::now();
        $diff = $inicio->diff($hasta);
        if ($diff->y > 0) {
            return $diff->y . ($diff->y === 1 ? ' AÑO' : ' AÑOS');
        }
        if ($diff->m > 0) {
            return $diff->m . ($diff->m === 1 ? ' MES' : ' MESES');
        }

        return $diff->d . ($diff->d === 1 ? ' DÍA' : ' DÍAS');
    }

    private static function inspectorLabel(IptInspection $inspection): string
    {
        $creator = $inspection->creator;
        if ($creator instanceof User) {
            return trim((string) ($creator->name ?? ''));
        }

        return trim((string) ($inspection->creator?->name ?? ''));
    }

    private static function asCarbon(DateTimeInterface|string|null $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value));
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
