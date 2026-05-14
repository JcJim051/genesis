@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-12 col-xl-8">
        <div class="card p-4 mb-3">
            <h4 class="mb-1">Configuración Google Drive</h4>
            <p class="text-muted mb-0">Conecta Drive para generar y actualizar matrices colaborativas desde GENESIS.</p>
        </div>

        <div class="card p-4 mb-3">
            <h5 class="mb-3">Datos de conexión (OAuth)</h5>
            @if(!empty($oauthLastError))
                <div class="alert alert-danger mb-3">
                    <strong>Error OAuth:</strong> {{ $oauthLastError }}
                </div>
            @endif
            @if(!empty($oauthLastDebug))
                <div class="alert alert-light border mb-3">
                    <div class="small text-muted mb-1">Diagnóstico técnico:</div>
                    <pre style="white-space:pre-wrap; margin:0; font-size:12px;">{{ $oauthLastDebug }}</pre>
                </div>
            @endif
            <form method="POST">
                @csrf
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="enabled">Activar integración Google Drive</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">ID carpeta raíz (Genesis)</label>
                    <input type="text" class="form-control" name="root_folder_id" value="{{ old('root_folder_id', $rootFolderId) }}" placeholder="1AbCdEfGhIjK...">
                    <small class="text-muted">Ejemplo de estructura final: <strong>Genesis / {Empresa} / {Matrices}</strong></small>
                </div>

                <h6 class="mb-2">Cuenta Google del profesional</h6>
                <p class="small text-muted">Los archivos se crearán con esta cuenta (evita errores de cuota de cuentas técnicas).</p>
                <div class="mb-3">
                    <label class="form-label">OAuth Client ID</label>
                    <input type="text" class="form-control" name="oauth_client_id" value="{{ old('oauth_client_id', $oauthClientId ?? '') }}" placeholder="xxxx.apps.googleusercontent.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">OAuth Client Secret</label>
                    <input type="password" class="form-control" name="oauth_client_secret" value="" placeholder="Pegar solo cuando quieras actualizar">
                    <small class="text-muted">Si no lo cambias, se conserva el actual.</small>
                </div>
                <div class="alert alert-light border small">
                    URL de redirección autorizada en Google Cloud:<br>
                    <code>{{ route('integraciones.google-drive.oauth-callback') }}</code>
                </div>
                <div class="d-flex gap-2 align-items-center mb-3">
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('integraciones.google-drive.oauth-redirect') }}">
                        <i class="la la-google"></i> Conectar cuenta Google
                    </a>
                    @if(!empty($oauthConnectedEmail))
                        <form method="POST" action="{{ route('integraciones.google-drive.oauth-disconnect') }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm" type="submit">
                                <i class="la la-unlink"></i> Desconectar
                            </button>
                        </form>
                    @endif
                </div>
                <div class="small text-muted mb-2">
                    Cuenta conectada:
                    @if(!empty($oauthConnectedEmail))
                        <strong>{{ $oauthConnectedEmail }}</strong>
                        ({{ $oauthConnectedAt ?: 'sin fecha' }})
                    @else
                        Sin conectar
                    @endif
                </div>

                <button class="btn btn-primary" type="submit">
                    <i class="la la-save"></i> Guardar configuración
                </button>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card p-4 mb-3">
            <h5 class="mb-3">Paso a paso OAuth</h5>
            <ol class="ps-3 mb-0">
                <li class="mb-2">Crear proyecto Google Cloud (sí, es obligatorio para OAuth):
                    <a href="https://console.cloud.google.com/projectcreate" target="_blank" rel="noopener">Crear proyecto</a>
                </li>
                <li class="mb-2">Configurar pantalla de consentimiento OAuth:
                    <a href="https://console.cloud.google.com/apis/credentials/consent" target="_blank" rel="noopener">OAuth consent screen</a>
                </li>
                <li class="mb-2">Crear credencial OAuth Client ID (Aplicación web):
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Credentials</a>
                </li>
                <li class="mb-2">Activar API de Drive:
                    <a href="https://console.cloud.google.com/apis/library/drive.googleapis.com" target="_blank" rel="noopener">Drive API</a>
                </li>
                <li class="mb-2">Activar API de Sheets:
                    <a href="https://console.cloud.google.com/apis/library/sheets.googleapis.com" target="_blank" rel="noopener">Sheets API</a>
                </li>
                <li class="mb-2">Agregar esta URL como <strong>Authorized redirect URI</strong> en OAuth Client:
                    <br><code>{{ route('integraciones.google-drive.oauth-callback') }}</code>
                </li>
                <li class="mb-2">Copiar Client ID y Client Secret en esta pantalla y guardar.</li>
                <li class="mb-2">Pulsar <strong>Conectar cuenta Google</strong> y aceptar permisos.</li>
                <li class="mb-2">Crear carpeta <strong>Genesis</strong> en Drive.</li>
                <li class="mb-0">Copiar el ID de carpeta y pegarlo en <strong>ID carpeta raíz</strong>.</li>
            </ol>
        </div>

        <div class="card p-4">
            <h6 class="mb-2">Estado de conexión</h6>
            <div class="small">
                @if(!empty($oauthConnectedEmail))
                    <span class="badge bg-success">Conectado</span>
                    <div class="mt-2 text-muted">{{ $oauthConnectedEmail }}</div>
                    <div class="text-muted">Conectado el: {{ $oauthConnectedAt ?: '-' }}</div>
                @else
                    <span class="badge bg-secondary">Sin conectar</span>
                    <div class="mt-2 text-muted">Conecta la cuenta Google para habilitar la sincronización.</div>
                @endif
            </div>
            <hr>
            <div class="small text-muted">
                Guía oficial Google OAuth:
                <a href="https://developers.google.com/identity/protocols/oauth2" target="_blank" rel="noopener">Ver documentación</a>
            </div>
        </div>
    </div>
</div>
@endsection
