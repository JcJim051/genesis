<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\IntegrationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleDriveConfigController extends Controller
{
    public function edit()
    {
        abort_unless(backpack_user() && backpack_user()->hasAnyRole(['Administrador', 'Coordinador general']), 403);

        return view('admin.integrations.google_drive', [
            'enabled' => IntegrationSettings::get('google_drive.enabled', '0') === '1',
            'rootFolderId' => (string) IntegrationSettings::get('google_drive.root_folder_id', ''),
            'oauthClientId' => (string) IntegrationSettings::get('google_drive.oauth_client_id', ''),
            'oauthConnectedEmail' => (string) IntegrationSettings::get('google_drive.oauth_connected_email', ''),
            'oauthConnectedAt' => (string) IntegrationSettings::get('google_drive.oauth_connected_at', ''),
            'oauthLastError' => (string) IntegrationSettings::get('google_drive.oauth_last_error', ''),
            'oauthLastDebug' => (string) IntegrationSettings::get('google_drive.oauth_last_debug', ''),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(backpack_user() && backpack_user()->hasAnyRole(['Administrador', 'Coordinador general']), 403);

        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'root_folder_id' => 'nullable|string|max:255',
            'oauth_client_id' => 'nullable|string|max:255',
            'oauth_client_secret' => 'nullable|string|max:255',
        ]);

        IntegrationSettings::set('google_drive.enabled', (string) (isset($data['enabled']) ? 1 : 0));
        IntegrationSettings::set('google_drive.root_folder_id', trim((string) ($data['root_folder_id'] ?? '')));
        IntegrationSettings::set('google_drive.oauth_client_id', trim((string) ($data['oauth_client_id'] ?? '')));
        IntegrationSettings::set('google_drive.oauth_last_error', '');
        IntegrationSettings::set('google_drive.oauth_last_debug', '');
        $incomingSecret = trim((string) ($data['oauth_client_secret'] ?? ''));
        if ($incomingSecret !== '') {
            IntegrationSettings::set('google_drive.oauth_client_secret', $incomingSecret);
        }

        return back()->with('success', 'Configuración de Google Drive actualizada.');
    }

    public function oauthRedirect()
    {
        abort_unless(backpack_user() && backpack_user()->hasAnyRole(['Administrador', 'Coordinador general']), 403);

        $clientId = trim((string) IntegrationSettings::get('google_drive.oauth_client_id', ''));
        $clientSecret = trim((string) IntegrationSettings::get('google_drive.oauth_client_secret', ''));
        if ($clientId === '' || $clientSecret === '') {
            IntegrationSettings::set('google_drive.oauth_last_error', 'Falta OAuth Client ID o Client Secret antes de redirigir a Google.');
            return back()->withErrors('Configura OAuth Client ID y Client Secret antes de conectar.');
        }

        $state = bin2hex(random_bytes(24));
        session(['google_drive_oauth_state' => $state]);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => route('integraciones.google-drive.oauth-callback'),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/drive',
                'https://www.googleapis.com/auth/spreadsheets',
                'https://www.googleapis.com/auth/userinfo.email',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ];

        IntegrationSettings::set('google_drive.oauth_last_error', '');
        IntegrationSettings::set('google_drive.oauth_last_debug', 'Inicio OAuth: state generado y redirección enviada a Google.');

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    }

    public function oauthCallback(Request $request)
    {
        abort_unless(backpack_user() && backpack_user()->hasAnyRole(['Administrador', 'Coordinador general']), 403);

        if ($request->has('error')) {
            $error = (string) $request->query('error');
            $desc = (string) $request->query('error_description', '');
            IntegrationSettings::set('google_drive.oauth_last_error', 'Google devolvió error en callback: ' . $error . ($desc ? (' - ' . $desc) : ''));
            IntegrationSettings::set('google_drive.oauth_last_debug', json_encode([
                'step' => 'callback_error',
                'error' => $error,
                'error_description' => $desc,
            ], JSON_UNESCAPED_UNICODE));
            return redirect()->route('integraciones.google-drive.edit')
                ->withErrors('Google rechazó la autorización OAuth: ' . $error . ($desc ? (' - ' . $desc) : ''));
        }

        $state = (string) $request->query('state', '');
        if ($state === '' || $state !== (string) session('google_drive_oauth_state')) {
            IntegrationSettings::set('google_drive.oauth_last_error', 'Estado OAuth inválido o expirado.');
            IntegrationSettings::set('google_drive.oauth_last_debug', json_encode([
                'step' => 'state_validation',
                'incoming_state' => $state,
                'session_state' => (string) session('google_drive_oauth_state'),
            ], JSON_UNESCAPED_UNICODE));
            return redirect()->route('integraciones.google-drive.edit')->withErrors('Estado OAuth inválido.');
        }
        session()->forget('google_drive_oauth_state');

        $code = (string) $request->query('code', '');
        if ($code === '') {
            IntegrationSettings::set('google_drive.oauth_last_error', 'Google no devolvió code en callback.');
            IntegrationSettings::set('google_drive.oauth_last_debug', json_encode([
                'step' => 'callback_no_code',
                'query' => $request->query(),
            ], JSON_UNESCAPED_UNICODE));
            return redirect()->route('integraciones.google-drive.edit')->withErrors('Google no devolvió código de autorización.');
        }

        $clientId = trim((string) IntegrationSettings::get('google_drive.oauth_client_id', ''));
        $clientSecret = trim((string) IntegrationSettings::get('google_drive.oauth_client_secret', ''));

        $tokenResp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => route('integraciones.google-drive.oauth-callback'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResp->successful()) {
            IntegrationSettings::set('google_drive.oauth_last_error', 'Falló intercambio code->token.');
            IntegrationSettings::set('google_drive.oauth_last_debug', json_encode([
                'step' => 'token_exchange_failed',
                'status' => $tokenResp->status(),
                'body' => $tokenResp->json() ?: $tokenResp->body(),
            ], JSON_UNESCAPED_UNICODE));
            return redirect()->route('integraciones.google-drive.edit')->withErrors('No se pudo completar OAuth: ' . $tokenResp->body());
        }

        $accessToken = (string) ($tokenResp->json('access_token') ?? '');
        $refreshToken = (string) ($tokenResp->json('refresh_token') ?? '');
        if ($refreshToken === '') {
            $refreshToken = (string) IntegrationSettings::get('google_drive.oauth_refresh_token', '');
        }
        if ($refreshToken === '') {
            IntegrationSettings::set('google_drive.oauth_last_error', 'Google no devolvió refresh_token.');
            IntegrationSettings::set('google_drive.oauth_last_debug', json_encode([
                'step' => 'missing_refresh_token',
                'token_response' => $tokenResp->json(),
            ], JSON_UNESCAPED_UNICODE));
            return redirect()->route('integraciones.google-drive.edit')->withErrors(
                'Google no devolvió refresh_token. Intenta: 1) desconectar, 2) ir a https://myaccount.google.com/permissions y quitar acceso del proyecto, 3) volver a conectar.'
            );
        }

        $email = '';
        if ($accessToken !== '') {
            $userInfo = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
            if ($userInfo->successful()) {
                $email = (string) ($userInfo->json('email') ?? '');
            }
        }

        IntegrationSettings::set('google_drive.oauth_refresh_token', $refreshToken);
        IntegrationSettings::set('google_drive.oauth_connected_email', $email);
        IntegrationSettings::set('google_drive.oauth_connected_at', now()->format('Y-m-d H:i:s'));
        IntegrationSettings::set('google_drive.oauth_last_error', '');
        IntegrationSettings::set('google_drive.oauth_last_debug', json_encode([
            'step' => 'success',
            'connected_email' => $email,
            'has_refresh_token' => true,
        ], JSON_UNESCAPED_UNICODE));

        return redirect()->route('integraciones.google-drive.edit')->with('success', 'Cuenta Google conectada correctamente.');
    }

    public function oauthDisconnect()
    {
        abort_unless(backpack_user() && backpack_user()->hasAnyRole(['Administrador', 'Coordinador general']), 403);
        IntegrationSettings::set('google_drive.oauth_refresh_token', '');
        IntegrationSettings::set('google_drive.oauth_connected_email', '');
        IntegrationSettings::set('google_drive.oauth_connected_at', '');
        IntegrationSettings::set('google_drive.oauth_last_error', 'Conexión OAuth removida manualmente.');
        IntegrationSettings::set('google_drive.oauth_last_debug', '');
        return back()->with('success', 'Conexión OAuth desconectada.');
    }
}
