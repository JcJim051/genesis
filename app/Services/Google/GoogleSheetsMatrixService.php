<?php

namespace App\Services\Google;

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

}
