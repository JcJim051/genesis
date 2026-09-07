<?php

namespace App\Services\Google;

use App\Models\Empleado;
use App\Models\IptInspection;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * Maps IPT inspections onto the team's Excel/Drive template
 * (FORMATO IPT + SEGUIMIENTOS) and the folder path
 * Genesis / {Empresa} / {AÑO} / {MES} / {Trabajador}/.
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

    /**
     * Checklist item number (1–43) => FORMATO IPT row.
     *
     * @var array<int, int>
     */
    public const QUESTION_ROWS = [
        1 => 8, 2 => 9, 3 => 10, 4 => 11, 5 => 12, 6 => 13,
        7 => 15, 8 => 16, 9 => 17, 10 => 18, 11 => 19, 12 => 20, 13 => 21, 14 => 22, 15 => 23,
        16 => 25, 17 => 26, 18 => 27, 19 => 28, 20 => 29, 21 => 30, 22 => 31, 23 => 32,
        24 => 34, 25 => 35, 26 => 36, 27 => 37,
        28 => 39, 29 => 40, 30 => 41, 31 => 42,
        32 => 44, 33 => 45, 34 => 46, 35 => 47, 36 => 48, 37 => 49, 38 => 50, 39 => 51, 40 => 52, 41 => 53, 42 => 54, 43 => 55,
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
     * Quote a sheet title for Sheets API A1 notation.
     *
     * Names with spaces or special characters must be wrapped in single quotes
     * (e.g. FORMATO IPT → 'FORMATO IPT'). Existing quotes are doubled.
     */
    public static function quoteSheetName(string $sheetName): string
    {
        return "'" . str_replace("'", "''", $sheetName) . "'";
    }

    /**
     * A1 range for values.update / values.batchUpdate (quoted sheet + cell).
     */
    public static function a1Range(string $sheetName, string $a1): string
    {
        return self::quoteSheetName($sheetName) . '!' . $a1;
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
     * Sheets API value ranges for FORMATO IPT (does not rewrite labels).
     *
     * @param  array{inicial?: string, despues?: string}  $photoLinks
     * @return list<array{range: string, values: array<int, array<int, mixed>>}>
     */
    public static function formatoValueRanges(IptInspection $inspection, array $photoLinks = []): array
    {
        $empleado = self::empleado($inspection);
        $fecha = self::asCarbon($inspection->fecha_inspeccion);
        $ranges = [];

        $push = function (string $cell, mixed $value) use (&$ranges) {
            $ranges[] = [
                'range' => self::a1Range(self::FORMATO_TAB, $cell),
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

        $answersByQuestion = $inspection->answers?->keyBy('question_id') ?? collect();
        $orderedQuestions = [];
        $sections = $inspection->template?->sections?->sortBy('orden') ?? collect();
        foreach ($sections as $section) {
            $questions = $section->questions?->sortBy('orden') ?? collect();
            foreach ($questions as $question) {
                $orderedQuestions[] = $question;
            }
        }

        foreach (self::QUESTION_ROWS as $number => $row) {
            $question = $orderedQuestions[$number - 1] ?? null;
            $si = '';
            $no = '';
            $na = '';
            if ($question) {
                $ans = $answersByQuestion->get($question->id);
                $kind = self::answerKind($ans?->respuesta ?? null);
                if ($kind === 'si') {
                    $si = 1;
                } elseif ($kind === 'no') {
                    $no = 0;
                } elseif ($kind === 'na') {
                    $na = 0;
                }
            }
            $push('F' . $row, $si);
            $push('G' . $row, $no);
            $push('H' . $row, $na);
        }

        $push('F56', $inspection->puntaje_total !== null ? (int) $inspection->puntaje_total : '');

        foreach (self::REQUIREMENT_CELLS as $cell) {
            $push($cell, '');
        }
        foreach ($inspection->requirements ?? [] as $req) {
            if (! $req->aplica) {
                continue;
            }
            $cell = self::requirementCell((string) ($req->requirement?->nombre ?? ''));
            if ($cell !== null) {
                $push($cell, 'X');
            }
        }

        foreach (self::RISK_MARK_ROWS as $row) {
            $push('B' . $row, '');
        }
        $nivel = mb_strtolower(trim((string) ($inspection->nivel_riesgo ?? '')), 'UTF-8');
        if (isset(self::RISK_MARK_ROWS[$nivel])) {
            $push('B' . self::RISK_MARK_ROWS[$nivel], 'X');
        }
        $push('E61', $inspection->puntaje_total !== null ? (int) $inspection->puntaje_total : '');

        $push('A65', (string) ($inspection->hallazgos ?? ''));

        $accion = trim((string) ($inspection->accion ?? ''));
        if ($accion === '') {
            $accion = trim((string) ($inspection->recomendaciones ?? ''));
        }
        $push('A68', $accion);
        $push('E68', trim((string) ($inspection->responsable ?? '')));
        // Clear the template's sample second recommendation row.
        $push('A69', '');
        $push('E69', '');

        $push('A72', (string) ($photoLinks['inicial'] ?? ''));
        $push('E72', (string) ($photoLinks['despues'] ?? ''));

        $creatorName = trim((string) ($inspection->creator?->name ?? ''));
        $push('A79', 'Firma profesional: ' . ($creatorName !== '' ? $creatorName : '_________________________'));

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
            $cell = self::cellFromA1Range($range['range']);
            $map[$cell] = $range['values'][0][0] ?? null;
        }

        return $map;
    }

    /**
     * One SEGUIMIENTOS matrix row (A–L).
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
