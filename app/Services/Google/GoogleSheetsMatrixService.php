<?php

namespace App\Services\Google;

use App\Models\IptInspection;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsMatrixService
{
    private const DRIVE_BASE = 'https://www.googleapis.com/drive/v3';
    private const SHEETS_BASE = 'https://sheets.googleapis.com/v4/spreadsheets';

    public function syncIptCompanyMatrix(int $clienteId, string $empresaNombre, array $rows, string $scopeLabel): array
    {
        $rootFolderConfig = trim((string) IntegrationSettings::get('google_drive.root_folder_id', ''));
        if ($rootFolderConfig === '') {
            throw new RuntimeException('Falta configurar el ID de carpeta raíz de Google Drive.');
        }

        $accessToken = $this->accessToken();
        $http = Http::withToken($accessToken)->acceptJson();
        $rootFolderId = $this->resolveRootFolderId($http, $rootFolderConfig);
        if ($rootFolderId === '' || mb_strlen($rootFolderId) < 10) {
            throw new RuntimeException('No se pudo resolver un ID válido para la carpeta raíz de Drive.');
        }

        $empresaFolderId = $this->ensureFolder($http, $empresaNombre, $rootFolderId);
        $spreadsheetName = 'Matriz IPT - ' . $empresaNombre;
        $spreadsheet = $this->ensureSpreadsheet($http, $spreadsheetName, $empresaFolderId, 'Matriz IPT');

        $spreadsheetId = $spreadsheet['id'];
        $spreadsheetUrl = 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit';

        $values = [];
        $values[] = ['MATRIZ IPT - GENESIS'];
        $values[] = ['Alcance aplicado', $scopeLabel];
        $values[] = ['Fecha generación', now()->format('Y-m-d H:i:s')];
        $values[] = [];
        $values[] = [
            'EMPRESA', 'PLANTA', 'FECHA', 'PERSONA', 'CÉDULA', 'PLANTILLA', 'TIPO',
            'PUNTAJE', 'RIESGO', 'HALLAZGOS', 'RECOMENDACIONES', 'ACCIÓN', 'RESPONSABLE',
            'FECHA SEGUIMIENTO', 'ESTADO',
        ];

        foreach ($rows as $row) {
            $values[] = $row;
        }

        $http->post(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/Matriz IPT!A1:Z50000:clear');
        $update = $http->put(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/Matriz IPT!A1?valueInputOption=RAW', [
            'range' => 'Matriz IPT!A1',
            'majorDimension' => 'ROWS',
            'values' => $values,
        ]);

        if (! $update->successful()) {
            throw new RuntimeException('No fue posible actualizar la matriz en Sheets: ' . $update->body());
        }

        IntegrationSettings::set('google_drive.company_sheet.' . $clienteId, json_encode([
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'folder_id' => $empresaFolderId,
            'updated_at' => now()->toDateTimeString(),
        ]));

        return [
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'rows' => count($rows),
        ];
    }

    public function syncOsteoCompanyMatrix(int $clienteId, string $empresaNombre, array $rows, string $scopeLabel): array
    {
        $rootFolderConfig = trim((string) IntegrationSettings::get('google_drive.root_folder_id', ''));
        if ($rootFolderConfig === '') {
            throw new RuntimeException('Falta configurar el ID de carpeta raíz de Google Drive.');
        }

        $accessToken = $this->accessToken();
        $http = Http::withToken($accessToken)->acceptJson();
        $rootFolderId = $this->resolveRootFolderId($http, $rootFolderConfig);

        $empresaFolderId = $this->ensureFolder($http, $empresaNombre, $rootFolderId);
        $spreadsheetName = 'Matriz Valoración Osteomuscular - ' . $empresaNombre;
        $spreadsheet = $this->ensureSpreadsheet($http, $spreadsheetName, $empresaFolderId, 'Matriz Osteo');

        $spreadsheetId = $spreadsheet['id'];
        $spreadsheetUrl = 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit';

        $values = [];
        $values[] = ['MATRIZ VALORACIÓN OSTEOMUSCULAR - GENESIS'];
        $values[] = ['Alcance aplicado', $scopeLabel];
        $values[] = ['Fecha generación', now()->format('Y-m-d H:i:s')];
        $values[] = [];
        $values[] = [
            'FECHA', 'EMPRESA', 'PLANTA', 'PERSONA', 'CÉDULA', 'PLANTILLA', 'ESTADO', 'EVALUADOR', 'CARGO PROFESIONAL', 'LICENCIA', 'OBSERVACIONES', 'LINK',
        ];
        foreach ($rows as $row) {
            $values[] = $row;
        }

        $tab = 'Matriz Osteo';
        $http->post(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode($tab . '!A1:Z50000') . ':clear');
        $update = $http->put(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode($tab . '!A1') . '?valueInputOption=RAW', [
            'range' => $tab . '!A1',
            'majorDimension' => 'ROWS',
            'values' => $values,
        ]);
        if (! $update->successful()) {
            $tab = 'Matriz IPT';
            $http->post(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode($tab . '!A1:Z50000') . ':clear');
            $update = $http->put(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode($tab . '!A1') . '?valueInputOption=RAW', [
                'range' => $tab . '!A1',
                'majorDimension' => 'ROWS',
                'values' => $values,
            ]);
        }
        if (! $update->successful()) {
            throw new RuntimeException('No fue posible actualizar matriz osteomuscular en Sheets: ' . $update->body());
        }

        IntegrationSettings::set('google_drive.company_sheet_osteo.' . $clienteId, json_encode([
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'folder_id' => $empresaFolderId,
            'updated_at' => now()->toDateTimeString(),
        ]));

        return [
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'rows' => count($rows),
        ];
    }

    public function syncIptInspectionSheet(IptInspection $inspection): array
    {
        $inspection->loadMissing([
            'empleado.cliente',
            'empleado.sucursal',
            'empleado.cargos',
            'empleado.areas',
            'programaCaso.empleado.cliente',
            'programaCaso.empleado.sucursal',
            'template.sections.questions',
            'answers',
            'requirements.requirement',
            'creator',
            'initialInspection',
        ]);

        $rootFolderConfig = trim((string) IntegrationSettings::get('google_drive.root_folder_id', ''));
        if ($rootFolderConfig === '') {
            throw new RuntimeException('Falta configurar el ID de carpeta raíz de Google Drive.');
        }

        $accessToken = $this->accessToken();
        $http = Http::withToken($accessToken)->acceptJson();
        $rootFolderId = $this->resolveRootFolderId($http, $rootFolderConfig);
        if ($rootFolderId === '' || mb_strlen($rootFolderId) < 10) {
            throw new RuntimeException('No se pudo resolver un ID válido para la carpeta raíz de Drive.');
        }

        $genesisFolderId = $this->ensureGenesisFolder($http, $rootFolderId);
        $workerFolderId = $genesisFolderId;
        foreach (IptDriveLayout::pathAfterGenesis($inspection) as $segment) {
            $workerFolderId = $this->ensureFolder($http, $segment, $workerFolderId);
        }

        $spreadsheetName = IptDriveLayout::spreadsheetTitle($inspection);
        $settingsKey = IptDriveLayout::workerSheetSettingsKey($inspection);
        $resolved = $this->resolveWorkerSpreadsheet($http, $inspection, $spreadsheetName, $workerFolderId, $settingsKey);
        $spreadsheetId = $resolved['id'];
        $created = $resolved['created'];
        $spreadsheetUrl = 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit';

        $isFollowup = IptDriveLayout::isFollowup($inspection);
        $shouldWriteFormato = $created || ! $isFollowup;

        if ($shouldWriteFormato) {
            $photoLinks = $this->inspectionPhotoLinks($inspection);
            $this->writeValueRanges($http, $spreadsheetId, IptDriveLayout::formatoValueRanges($inspection, $photoLinks));
        }

        $this->upsertSeguimientosRow($http, $spreadsheetId, $inspection, $isFollowup && ! $created);

        $meta = json_encode([
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'folder_id' => $workerFolderId,
            'path' => IptDriveLayout::displayPath($inspection),
            'updated_at' => now()->toDateTimeString(),
        ]);
        IntegrationSettings::set($settingsKey, $meta);
        IntegrationSettings::set('google_drive.ipt_sheet.' . $inspection->id, $meta);

        return [
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'name' => $spreadsheetName,
            'path' => IptDriveLayout::displayPath($inspection),
        ];
    }

    public function iptTemplateSpreadsheetId(): string
    {
        $fromSettings = trim((string) IntegrationSettings::get('google_drive.ipt_template_spreadsheet_id', ''));
        if ($fromSettings !== '') {
            return IptDriveLayout::extractSpreadsheetId($fromSettings);
        }

        $fromConfig = trim((string) config('services.google.ipt_template_spreadsheet_id', IptDriveLayout::DEFAULT_TEMPLATE_ID));
        if ($fromConfig !== '') {
            return IptDriveLayout::extractSpreadsheetId($fromConfig);
        }

        return IptDriveLayout::DEFAULT_TEMPLATE_ID;
    }

    public function refreshAccessToken(): string
    {
        return $this->accessToken();
    }

    private function accessToken(): string
    {
        $oauthRefreshToken = trim((string) IntegrationSettings::get('google_drive.oauth_refresh_token', ''));
        $oauthClientId = trim((string) IntegrationSettings::get('google_drive.oauth_client_id', ''));
        $oauthClientSecret = trim((string) IntegrationSettings::get('google_drive.oauth_client_secret', ''));

        if ($oauthRefreshToken === '' || $oauthClientId === '' || $oauthClientSecret === '') {
            throw new RuntimeException('OAuth de Google Drive no está completo. Configura Client ID, Client Secret y conecta la cuenta.');
        }

        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $oauthClientId,
            'client_secret' => $oauthClientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $oauthRefreshToken,
        ]);

        if (! $resp->successful()) {
            throw new RuntimeException('No fue posible renovar token OAuth en Google: ' . $resp->body());
        }

        $token = (string) ($resp->json('access_token') ?? '');
        if ($token === '') {
            throw new RuntimeException('Google no retornó access_token.');
        }

        return $token;
    }

    private function ensureFolder($http, string $name, string $parentId): string
    {
        if (trim($parentId) === '' || mb_strlen(trim($parentId)) < 10) {
            throw new RuntimeException('ID de carpeta padre inválido para crear carpeta en Drive.');
        }

        $query = sprintf(
            "name = '%s' and '%s' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            str_replace("'", "\\'", $name),
            $parentId
        );

        $list = $http->get(self::DRIVE_BASE . '/files', [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]);

        if ($list->successful() && ! empty($list->json('files.0.id'))) {
            return (string) $list->json('files.0.id');
        }

        $create = $http->post(self::DRIVE_BASE . '/files', [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
            'supportsAllDrives' => true,
        ]);

        if (! $create->successful()) {
            throw new RuntimeException('No fue posible crear carpeta en Drive: ' . $create->body());
        }

        return (string) $create->json('id');
    }

    private function resolveRootFolderId($http, string $configured): string
    {
        $configured = trim($configured);
        if ($configured === '') {
            throw new RuntimeException('Configuración de carpeta raíz vacía.');
        }

        // Si guardaron URL completa, extraemos el ID.
        if (str_contains($configured, 'drive.google.com')) {
            if (preg_match('~/folders/([a-zA-Z0-9_-]+)~', $configured, $m)) {
                $configured = $m[1];
            }
        }

        // Heurística: IDs de Drive suelen ser largos. Si es corto (ej: "Genesis"), tratar como nombre.
        $treatAsName = mb_strlen($configured) < 16;

        // 1) Si ya es un ID válido y accesible, lo usamos.
        if (! $treatAsName) {
            $check = $http->get(self::DRIVE_BASE . '/files/' . urlencode($configured), [
                'fields' => 'id,name,mimeType',
                'supportsAllDrives' => 'true',
            ]);
            if ($check->successful() && $check->json('mimeType') === 'application/vnd.google-apps.folder') {
                return (string) $check->json('id');
            }
        }

        // 2) Si no existe como ID, lo tratamos como nombre de carpeta raíz y la buscamos.
        $folderName = $configured;
        $query = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            str_replace("'", "\\'", $folderName)
        );

        $list = $http->get(self::DRIVE_BASE . '/files', [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]);

        if ($list->successful() && ! empty($list->json('files.0.id'))) {
            $id = (string) $list->json('files.0.id');
            IntegrationSettings::set('google_drive.root_folder_id', $id);
            return $id;
        }

        // 3) Si no existe, la creamos en raíz de la service account.
        $create = $http->post(self::DRIVE_BASE . '/files', [
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => ['root'],
        ]);

        if (! $create->successful()) {
            throw new RuntimeException('No fue posible resolver/crear carpeta raíz en Drive: ' . $create->body());
        }

        $id = (string) $create->json('id');
        IntegrationSettings::set('google_drive.root_folder_id', $id);
        return $id;
    }

    private function ensureSpreadsheet($http, string $name, string $parentId, string $sheetTitle = 'Matriz IPT'): array
    {
        $query = sprintf(
            "name = '%s' and '%s' in parents and mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false",
            str_replace("'", "\\'", $name),
            $parentId
        );

        $list = $http->get(self::DRIVE_BASE . '/files', [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        if ($list->successful() && ! empty($list->json('files.0.id'))) {
            return [
                'id' => (string) $list->json('files.0.id'),
                'name' => (string) $list->json('files.0.name'),
            ];
        }

        $create = $http->post(self::DRIVE_BASE . '/files', [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.spreadsheet',
            'parents' => [$parentId],
        ]);

        if (! $create->successful()) {
            throw new RuntimeException('No fue posible crear Google Sheet: ' . $create->body());
        }

        $id = (string) $create->json('id');

        // Renombrar hoja por defecto.
        $meta = $http->get(self::SHEETS_BASE . '/' . $id);
        if ($meta->successful()) {
            $sheetId = $meta->json('sheets.0.properties.sheetId');
            if ($sheetId !== null) {
                $http->post(self::SHEETS_BASE . '/' . $id . ':batchUpdate', [
                    'requests' => [[
                        'updateSheetProperties' => [
                            'properties' => [
                                'sheetId' => (int) $sheetId,
                                'title' => $sheetTitle,
                            ],
                            'fields' => 'title',
                        ],
                    ]],
                ]);
            }
        }

        return ['id' => $id, 'name' => $name];
    }

    private function ensureGenesisFolder($http, string $rootFolderId): string
    {
        $meta = $http->get(self::DRIVE_BASE . '/files/' . urlencode($rootFolderId), [
            'fields' => 'id,name,mimeType',
            'supportsAllDrives' => 'true',
        ]);
        $rootName = trim((string) ($meta->json('name') ?? ''));
        if (strcasecmp($rootName, IptDriveLayout::GENESIS_FOLDER) === 0) {
            return $rootFolderId;
        }

        return $this->ensureFolder($http, IptDriveLayout::GENESIS_FOLDER, $rootFolderId);
    }

    /**
     * @return array{id: string, created: bool}
     */
    private function resolveWorkerSpreadsheet($http, IptInspection $inspection, string $spreadsheetName, string $workerFolderId, string $settingsKey): array
    {
        $storedId = $this->storedSpreadsheetId($settingsKey, $inspection);
        if ($storedId !== '') {
            $check = $http->get(self::DRIVE_BASE . '/files/' . urlencode($storedId), [
                'fields' => 'id,name,trashed',
                'supportsAllDrives' => 'true',
            ]);
            if ($check->successful() && ! $check->json('trashed')) {
                $currentName = (string) ($check->json('name') ?? '');
                if ($currentName !== '' && $currentName !== $spreadsheetName) {
                    $http->patch(self::DRIVE_BASE . '/files/' . urlencode($storedId) . '?supportsAllDrives=true', [
                        'name' => $spreadsheetName,
                    ]);
                }

                return ['id' => $storedId, 'created' => false];
            }
        }

        $existing = $this->findSpreadsheetInFolder($http, $spreadsheetName, $workerFolderId);
        if ($existing !== '') {
            return ['id' => $existing, 'created' => false];
        }

        return [
            'id' => $this->copyIptTemplate($http, $spreadsheetName, $workerFolderId),
            'created' => true,
        ];
    }

    private function storedSpreadsheetId(string $settingsKey, IptInspection $inspection): string
    {
        $cfg = json_decode((string) IntegrationSettings::get($settingsKey, ''), true);
        $id = trim((string) ($cfg['spreadsheet_id'] ?? ''));
        if ($id !== '') {
            return $id;
        }

        $legacy = json_decode((string) IntegrationSettings::get('google_drive.ipt_sheet.' . $inspection->id, ''), true);

        return trim((string) ($legacy['spreadsheet_id'] ?? ''));
    }

    private function findSpreadsheetInFolder($http, string $name, string $parentId): string
    {
        $query = sprintf(
            "name = '%s' and '%s' in parents and mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false",
            str_replace("'", "\\'", $name),
            $parentId
        );

        $list = $http->get(self::DRIVE_BASE . '/files', [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]);

        if ($list->successful() && ! empty($list->json('files.0.id'))) {
            return (string) $list->json('files.0.id');
        }

        return '';
    }

    private function copyIptTemplate($http, string $name, string $parentId): string
    {
        $templateId = $this->iptTemplateSpreadsheetId();
        if ($templateId === '') {
            throw new RuntimeException('Falta el ID de la plantilla IPT de Google Sheets.');
        }

        $copy = $http->post(self::DRIVE_BASE . '/files/' . urlencode($templateId) . '/copy?supportsAllDrives=true&fields=id,parents', [
            'name' => $name,
            'parents' => [$parentId],
        ]);

        if (! $copy->successful()) {
            throw new RuntimeException(
                'No fue posible copiar la plantilla IPT en Drive. Comparte el spreadsheet de plantilla con la cuenta Google conectada y verifica el ID configurado. Detalle: ' . $copy->body()
            );
        }

        $id = (string) $copy->json('id');
        if ($id === '') {
            throw new RuntimeException('Google Drive no devolvió ID al copiar la plantilla IPT.');
        }

        $parents = $copy->json('parents') ?? [];
        if (! is_array($parents) || ! in_array($parentId, $parents, true)) {
            $query = 'supportsAllDrives=true&addParents=' . urlencode($parentId);
            if (is_array($parents) && $parents !== []) {
                $query .= '&removeParents=' . urlencode(implode(',', array_map('strval', $parents)));
            }
            $move = $http->patch(self::DRIVE_BASE . '/files/' . urlencode($id) . '?' . $query, []);
            if (! $move->successful()) {
                throw new RuntimeException('La plantilla IPT se copió, pero no se pudo mover a la carpeta del trabajador: ' . $move->body());
            }
        }

        return $id;
    }

    /**
     * @param  list<array{range: string, values: array<int, array<int, mixed>>}>  $ranges
     */
    private function writeValueRanges($http, string $spreadsheetId, array $ranges): void
    {
        if ($ranges === []) {
            return;
        }

        $resp = $http->post(self::SHEETS_BASE . '/' . $spreadsheetId . '/values:batchUpdate', [
            'valueInputOption' => 'USER_ENTERED',
            'data' => $ranges,
        ]);

        if (! $resp->successful()) {
            throw new RuntimeException('No fue posible actualizar FORMATO IPT: ' . $resp->body());
        }
    }

    private function upsertSeguimientosRow($http, string $spreadsheetId, IptInspection $inspection, bool $append): void
    {
        $tab = IptDriveLayout::SEGUIMIENTOS_TAB;
        $read = $http->get(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode(IptDriveLayout::a1($tab, 'A1:L500')));
        $rows = $read->successful() ? ($read->json('values') ?? []) : [];
        if ($rows === []) {
            $rows = [
                ['MATRIZ DE SEGUMIENTO EVALUACION ERGONOMICA DE ESTACIONES DE TRABAJO'],
                ['FECHA', 'IDENTIFICACION', 'NOMBRE COMPLETO', 'AREA', 'CARGO', 'HALLAZGOS', 'RECOMENDACIONES', 'REQUERIMIENTOS', 'FECHA DE SEGUIMIENTO', 'SEGUIMIENTO EXITOSO', 'OBSERVACIONES DE SEGUIMEINTO', 'ESTADO'],
            ];
        }

        $empleado = IptDriveLayout::empleado($inspection);
        $cedula = trim((string) ($empleado?->cedula ?? ''));
        $nombre = IptDriveLayout::workerDisplayName($inspection);

        if ($append) {
            $rowNumber = $this->nextSeguimientosRowNumber($rows, false);
        } else {
            $rowNumber = $this->findSeguimientosRowNumber($rows, $cedula, $nombre)
                ?? $this->nextSeguimientosRowNumber($rows, true);
        }

        $writeRange = IptDriveLayout::a1($tab, 'A' . $rowNumber);
        $update = $http->put(
            self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode($writeRange) . '?valueInputOption=USER_ENTERED',
            [
                'range' => $writeRange,
                'majorDimension' => 'ROWS',
                'values' => [IptDriveLayout::seguimientosRow($inspection)],
            ]
        );

        if (! $update->successful()) {
            throw new RuntimeException('No fue posible actualizar SEGUIMIENTOS: ' . $update->body());
        }
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function findSeguimientosRowNumber(array $rows, string $cedula, string $nombre): ?int
    {
        foreach ($rows as $index => $row) {
            if ($index < 2) {
                continue;
            }
            $rowCedula = trim((string) ($row[1] ?? ''));
            $rowNombre = trim((string) ($row[2] ?? ''));
            if ($cedula !== '' && strcasecmp($rowCedula, $cedula) === 0) {
                return $index + 1;
            }
            if ($cedula === '' && $nombre !== '' && strcasecmp($rowNombre, $nombre) === 0) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function nextSeguimientosRowNumber(array $rows, bool $reuseBlankIdentity): int
    {
        $lastUsed = 2;
        foreach ($rows as $index => $row) {
            if ($index < 2) {
                continue;
            }
            $hasIdentity = trim((string) ($row[1] ?? '')) !== '' || trim((string) ($row[2] ?? '')) !== '';
            $hasAny = false;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $hasAny = true;
                    break;
                }
            }
            if ($reuseBlankIdentity && $hasAny && ! $hasIdentity) {
                return $index + 1;
            }
            if ($hasAny) {
                $lastUsed = $index + 1;
            }
        }

        return $lastUsed + 1;
    }

    /**
     * @return array{inicial: string, despues: string}
     */
    private function inspectionPhotoLinks(IptInspection $inspection): array
    {
        $modo = (string) ($inspection->template?->evidencia_fotografica_modo ?? 'none');
        $inicial = '';
        $despues = '';

        if ($modo === 'general') {
            $inicial = $this->inspectionPhotoLink($inspection->foto_general);
        } elseif ($modo !== 'none') {
            $inicial = $this->inspectionPhotoLink($inspection->foto_antes);
            $despues = $this->inspectionPhotoLink($inspection->foto_despues);
        }

        return [
            'inicial' => $inicial,
            'despues' => $despues,
        ];
    }

    private function writeSheetValues($http, string $spreadsheetId, string $tab, array $values): void
    {
        $clearRange = $tab . '!A1:Z50000';
        $http->post(self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode($clearRange) . ':clear');

        $update = $http->put(
            self::SHEETS_BASE . '/' . $spreadsheetId . '/values/' . rawurlencode($tab . '!A1') . '?valueInputOption=RAW',
            [
                'range' => $tab . '!A1',
                'majorDimension' => 'ROWS',
                'values' => $values,
            ]
        );

        if (! $update->successful()) {
            throw new RuntimeException('No fue posible actualizar la hoja "' . $tab . '": ' . $update->body());
        }
    }

    private function inspectionPhotoLink(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        try {
            return url('storage/' . ltrim($path, '/'));
        } catch (\Throwable $e) {
            return '';
        }
    }

}
