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
            'programaCaso.empleado.cliente',
            'programaCaso.empleado.sucursal',
            'template.sections.questions',
            'answers',
            'requirements.requirement',
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

        $content = $this->buildIptInspectionSheetContent($inspection);
        $empresaFolderId = $this->ensureFolder($http, $content['empresa_nombre'], $rootFolderId);
        $iptFolderId = $this->ensureFolder($http, 'IPT', $empresaFolderId);

        $spreadsheetName = $content['spreadsheet_name'];
        $spreadsheetId = $this->resolveIptInspectionSpreadsheetId($http, $inspection, $spreadsheetName, $iptFolderId);
        $spreadsheetUrl = 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit';

        $this->ensureSheetTitles($http, $spreadsheetId, array_keys($content['tabs']));

        foreach ($content['tabs'] as $tab => $values) {
            $this->writeSheetValues($http, $spreadsheetId, $tab, $values);
        }

        IntegrationSettings::set('google_drive.ipt_sheet.' . $inspection->id, json_encode([
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'folder_id' => $iptFolderId,
            'updated_at' => now()->toDateTimeString(),
        ]));

        return [
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $spreadsheetUrl,
            'name' => $spreadsheetName,
        ];
    }

    /**
     * @return array{spreadsheet_name: string, empresa_nombre: string, tabs: array<string, array<int, array<int, string>>>}
     */
    public function buildIptInspectionSheetContent(IptInspection $inspection): array
    {
        $empleado = $inspection->empleado ?: $inspection->programaCaso?->empleado;
        $persona = trim((string) ($empleado?->nombre ?? 'Sin nombre'));
        $cedula = trim((string) ($empleado?->cedula ?? ''));
        $empresaNombre = trim((string) ($empleado?->cliente?->nombre ?? ''));
        if ($empresaNombre === '') {
            $clienteId = (int) ($inspection->cliente_id ?? $empleado?->cliente_id ?? 0);
            $empresaNombre = $clienteId > 0 ? 'Empresa_' . $clienteId : 'Empresa';
        }

        $fecha = optional($inspection->fecha_inspeccion)->format('Y-m-d') ?: '';
        $spreadsheetName = $this->sanitizeDriveName(
            'IPT-' . $inspection->id . ' - ' . ($persona !== '' ? $persona : 'Sin nombre') . ' - ' . ($fecha !== '' ? $fecha : 's-f')
        );

        $tipo = $inspection->tipo === 'followup' ? 'Seguimiento' : 'Inicial';
        $plantilla = (string) ($inspection->template?->nombre_publico ?? '');
        $personaMeta = trim($persona . ($cedula !== '' ? ' · ' . $cedula : ''));

        $resumen = [];
        $resumen[] = ['Inspección IPT #' . $inspection->id];
        $resumen[] = ['Tipo', $tipo];
        $resumen[] = ['Plantilla', $plantilla];
        $resumen[] = [];
        $resumen[] = ['Persona', $personaMeta];
        $resumen[] = ['Fecha inspección', $fecha];
        $resumen[] = ['Empresa', (string) ($empleado?->cliente?->nombre ?? '—')];
        $resumen[] = ['Planta', (string) ($empleado?->sucursal?->nombre ?? '—')];
        $resumen[] = ['Puntaje', (string) ($inspection->puntaje_total ?? 0)];
        $resumen[] = ['Riesgo', strtoupper((string) ($inspection->nivel_riesgo ?? ''))];
        $resumen[] = ['Próximo seguimiento', optional($inspection->fecha_proximo_seguimiento_sugerida)->format('Y-m-d') ?: '—'];
        $resumen[] = ['Estado', ucfirst((string) ($inspection->estado ?? ''))];

        $fotoModo = (string) ($inspection->template?->evidencia_fotografica_modo ?? 'none');
        if ($fotoModo !== 'none') {
            $resumen[] = [];
            $resumen[] = ['Evidencia fotográfica'];
            if ($fotoModo === 'general') {
                $resumen[] = ['Evidencia general', $this->inspectionPhotoLink($inspection->foto_general)];
            } else {
                $resumen[] = ['Antes', $this->inspectionPhotoLink($inspection->foto_antes)];
                $resumen[] = ['Después', $this->inspectionPhotoLink($inspection->foto_despues)];
            }
        }

        $resumen[] = [];
        $resumen[] = ['Hallazgos y plan'];
        $resumen[] = ['Hallazgos', (string) ($inspection->hallazgos ?: '—')];
        $resumen[] = ['Recomendaciones', (string) ($inspection->recomendaciones ?: '—')];
        if ($inspection->template?->mostrar_accion) {
            $resumen[] = ['Acción', (string) ($inspection->accion ?: '—')];
        }
        if ($inspection->template?->mostrar_responsable) {
            $resumen[] = ['Responsable', (string) ($inspection->responsable ?: '—')];
        }
        $resumen[] = [];
        $resumen[] = ['Generado por plataforma Genesis', now()->format('Y-m-d H:i:s')];

        $answersByQuestion = $inspection->answers->keyBy('question_id');
        $respuestas = [];
        $respuestas[] = ['Respuestas'];
        $sections = $inspection->template?->sections?->sortBy('orden') ?? collect();
        foreach ($sections as $section) {
            $respuestas[] = [];
            $respuestas[] = [(string) ($section->titulo ?? '')];
            $respuestas[] = ['Pregunta', 'Respuesta', 'Puntaje'];
            $questions = $section->questions?->sortBy('orden') ?? collect();
            foreach ($questions as $question) {
                $ans = $answersByQuestion->get($question->id);
                $respuestas[] = [
                    (string) ($question->texto ?? ''),
                    strtoupper((string) ($ans->respuesta ?? '')),
                    (string) ($ans->score ?? 0),
                ];
            }
        }

        $requerimientos = [];
        $requerimientos[] = ['Requerimientos de estación'];
        $requerimientos[] = ['Requerimiento', 'Aplica'];
        $reqs = $inspection->requirements ?? collect();
        if ($reqs->isEmpty()) {
            $requerimientos[] = ['Sin registros', ''];
        } else {
            foreach ($reqs as $req) {
                $requerimientos[] = [
                    (string) ($req->requirement?->nombre ?? ''),
                    $req->aplica ? 'Sí' : 'No',
                ];
            }
        }

        return [
            'spreadsheet_name' => $spreadsheetName,
            'empresa_nombre' => $empresaNombre,
            'tabs' => [
                'Resumen' => $resumen,
                'Respuestas' => $respuestas,
                'Requerimientos' => $requerimientos,
            ],
        ];
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

    private function resolveIptInspectionSpreadsheetId($http, IptInspection $inspection, string $spreadsheetName, string $iptFolderId): string
    {
        $cfg = json_decode((string) IntegrationSettings::get('google_drive.ipt_sheet.' . $inspection->id, ''), true);
        $storedId = trim((string) ($cfg['spreadsheet_id'] ?? ''));

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

                return $storedId;
            }
        }

        $spreadsheet = $this->ensureSpreadsheet($http, $spreadsheetName, $iptFolderId, 'Resumen');

        return (string) $spreadsheet['id'];
    }

    private function ensureSheetTitles($http, string $spreadsheetId, array $wantedTitles): void
    {
        $meta = $http->get(self::SHEETS_BASE . '/' . $spreadsheetId);
        if (! $meta->successful()) {
            throw new RuntimeException('No fue posible leer el spreadsheet IPT: ' . $meta->body());
        }

        $sheets = $meta->json('sheets') ?? [];
        $existingTitles = [];
        foreach ($sheets as $sheet) {
            $existingTitles[] = (string) ($sheet['properties']['title'] ?? '');
        }

        $requests = [];
        foreach ($wantedTitles as $index => $title) {
            if (in_array($title, $existingTitles, true)) {
                continue;
            }

            if ($index === 0 && isset($sheets[0]['properties']['sheetId'])) {
                $currentFirst = (string) ($sheets[0]['properties']['title'] ?? '');
                if (! in_array($currentFirst, $wantedTitles, true)) {
                    $requests[] = [
                        'updateSheetProperties' => [
                            'properties' => [
                                'sheetId' => (int) $sheets[0]['properties']['sheetId'],
                                'title' => $title,
                            ],
                            'fields' => 'title',
                        ],
                    ];
                    $existingTitles[] = $title;
                    continue;
                }
            }

            $requests[] = [
                'addSheet' => [
                    'properties' => ['title' => $title],
                ],
            ];
            $existingTitles[] = $title;
        }

        if ($requests === []) {
            return;
        }

        $resp = $http->post(self::SHEETS_BASE . '/' . $spreadsheetId . ':batchUpdate', [
            'requests' => $requests,
        ]);

        if (! $resp->successful()) {
            throw new RuntimeException('No fue posible preparar pestañas del spreadsheet IPT: ' . $resp->body());
        }
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
            return 'Sin evidencia';
        }

        try {
            return url('storage/' . ltrim($path, '/'));
        } catch (\Throwable $e) {
            return 'Sin evidencia';
        }
    }

    private function sanitizeDriveName(string $name): string
    {
        $name = preg_replace('/[\\\\\\/]+/', '-', $name) ?? $name;
        $name = preg_replace('/[\\x00-\\x1F]+/', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\\s+/', ' ', $name) ?? $name);

        return mb_substr($name !== '' ? $name : 'IPT', 0, 200);
    }

}
