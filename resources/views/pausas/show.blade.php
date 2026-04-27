<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pausa->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        h1 { margin: 0 0 12px 0; font-size: 24px; }
        .meta { color: #666; margin-bottom: 16px; }
        .media-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin: 16px 0 24px 0; }
        .media-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; }
        .media-title { font-weight: bold; margin-bottom: 8px; }
        .media-frame { width: 100%; height: 380px; border: 0; border-radius: 10px; }
        .open-link { display: inline-block; margin-top: 10px; font-size: 13px; color: #2563eb; text-decoration: none; }
        @media (min-width: 900px) {
            .media-grid { grid-template-columns: 1fr 1fr; }
            .media-frame { height: 360px; }
        }
        .question { margin-bottom: 20px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 10px; }
        .option { margin: 8px 0; }
        .btn { background: #0f172a; color: #fff; border: 0; padding: 10px 16px; border-radius: 8px; cursor: pointer; }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .btn-secondary { background: #2563eb; }
        .muted { color: #6b7280; font-size: 12px; }
        .timer-box { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; margin: 12px 0 20px; }
        .timer-pill { background: #0f172a; color: #fff; border-radius: 999px; padding: 6px 12px; font-weight: bold; }
        .timer-label { font-size: 13px; color: #475569; }
        .timer-warn { color: #b91c1c; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $pausa->nombre }}</h1>
        <div class="meta">
            {{ $empleado?->nombre ?? 'Empleado' }} · Tiempo mínimo: {{ $pausa->tiempo_minimo_segundos }}s
        </div>

        <div class="timer-box">
            <div class="timer-pill">
                <span id="time_elapsed">0</span>s / <span id="time_min">{{ $pausa->tiempo_minimo_segundos }}</span>s
            </div>
            <div class="timer-label">Mantén esta página activa hasta completar el tiempo mínimo.</div>
            <div class="timer-warn" id="timer_status">En progreso</div>
        </div>

        <div class="media-grid">
            <div class="media-card">
                <div class="media-title">Video</div>
            @php
                $url = $pausa->video_url ?? '';
                $isYoutube = str_contains($url, 'youtube') || str_contains($url, 'youtu.be');
                $youtubeId = '';
                if ($isYoutube) {
                    if (str_contains($url, 'youtu.be/')) {
                        $youtubeId = trim(parse_url($url, PHP_URL_PATH), '/');
                    } else {
                        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $q);
                        $youtubeId = $q['v'] ?? '';
                    }
                }
            @endphp
            @if ($url)
                @if ($isYoutube && $youtubeId)
                    <iframe class="media-frame" src="https://www.youtube.com/embed/{{ $youtubeId }}" allowfullscreen></iframe>
                @else
                    <video class="media-frame" controls>
                        <source src="{{ $url }}" type="video/mp4">
                    </video>
                @endif
            @else
                <div class="muted">No hay video configurado para esta pausa activa.</div>
            @endif
            </div>

            <div class="media-card">
                <div class="media-title">Actividad externa</div>
                @if ($pausa->external_url)
                    @php
                        $externalUrl = $pausa->external_url;
                        if (str_contains($externalUrl, 'educaplay.com/recursos-educativos/')) {
                            $externalUrl = str_replace('/recursos-educativos/', '/juego/', $externalUrl);
                        }
                    @endphp
                    <iframe
                        id="external_game_frame"
                        class="media-frame"
                        src="{{ $externalUrl }}"
                        loading="lazy"
                        allow="fullscreen; autoplay; allow-top-navigation-by-user-activation"
                        allowfullscreen
                        frameborder="0"
                        referrerpolicy="no-referrer"></iframe>
                    <div style="margin-top:10px; display:flex; gap:10px; align-items:center;">
                        <button type="button" class="btn btn-secondary" id="btn-maximize-game">Maximizar juego</button>
                        <span class="muted" id="game_status">Debes maximizar el juego para finalizar.</span>
                    </div>
                    <div class="muted" style="margin-top:8px;">Si no ves el juego aquí, este proveedor no permite ser embebido.</div>
                @else
                    <div class="muted">No hay actividad externa configurada.</div>
                @endif
            </div>
        </div>

        <form method="post" action="">
            @csrf
            <input type="hidden" name="tiempo_activo_total" id="tiempo_activo_total" value="0">
            <input type="hidden" name="tab_switch_count" id="tab_switch_count" value="0">

            @foreach(($formulario?->preguntas ?? collect())->sortBy('orden') as $pregunta)
                <div class="question">
                    <strong>{{ $pregunta->texto }}</strong>
                    @if ($pregunta->tipo === 'opcion')
                        @foreach($pregunta->opciones->sortBy('orden') as $opcion)
                            <div class="option">
                                <label>
                                    <input type="radio" name="pregunta_{{ $pregunta->id }}" value="{{ $opcion->id }}">
                                    {{ $opcion->texto }}
                                </label>
                            </div>
                        @endforeach
                    @else
                        <div class="option">
                            <textarea name="pregunta_{{ $pregunta->id }}" rows="3" style="width:100%;"></textarea>
                        </div>
                    @endif
                    @error('pregunta_'.$pregunta->id)
                        <div style="color: #c00; margin-top: 6px;">Este campo es obligatorio.</div>
                    @enderror
                </div>
            @endforeach

            @error('tiempo_activo_total')
                <div style="color:#c00; margin-bottom: 10px;">Tiempo inválido.</div>
            @enderror

            <button class="btn" id="btn-submit" type="submit" disabled>Finalicé</button>
        </form>
    </div>

    <script>
        (function() {
            const token = '{{ $participacion->token }}';
            const tiempoEl = document.getElementById('tiempo_activo_total');
            const tabEl = document.getElementById('tab_switch_count');
            const timeElapsedEl = document.getElementById('time_elapsed');
            const timeMinEl = document.getElementById('time_min');
            const timerStatusEl = document.getElementById('timer_status');
            let active = true;
            let seconds = 0;
            let tabSwitch = 0;
            let timerId = null;
            const minSeconds = parseInt(timeMinEl?.textContent || '0', 10);
            let gameMaximized = false;
            let gameMaximizeConfirmed = false;
            const gameFrame = document.getElementById('external_game_frame');
            const maximizeBtn = document.getElementById('btn-maximize-game');
            const gameStatus = document.getElementById('game_status');
            const submitBtn = document.getElementById('btn-submit');

            const canSubmit = () => {
                const hasGame = !!gameFrame;
                if (hasGame) {
                    return seconds >= minSeconds && gameMaximizeConfirmed;
                }
                return seconds >= minSeconds;
            };

            const updateSubmitState = () => {
                if (!submitBtn) return;
                submitBtn.disabled = !canSubmit();
            };

            const sendEvent = (tipo, metadata = {}) => {
                fetch('/pausas/' + token + '/event', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        tipo,
                        timestamp: new Date().toISOString(),
                        metadata,
                    })
                }).catch(() => {});
            };

            const tick = () => {
                if (active) {
                    seconds += 1;
                    tiempoEl.value = seconds;
                    if (timeElapsedEl) timeElapsedEl.textContent = seconds;
                    if (timerStatusEl) {
                        timerStatusEl.textContent = seconds >= minSeconds ? 'Tiempo mínimo alcanzado' : 'En progreso';
                        timerStatusEl.style.color = seconds >= minSeconds ? '#166534' : '#b91c1c';
                    }
                    updateSubmitState();
                }
            };

            timerId = setInterval(tick, 1000);

            window.addEventListener('focus', () => {
                active = true;
                sendEvent('focus');
            });

            window.addEventListener('blur', () => {
                active = false;
                tabSwitch += 1;
                tabEl.value = tabSwitch;
                sendEvent('blur');
            });

            if (maximizeBtn && gameFrame) {
                maximizeBtn.addEventListener('click', () => {
                    // Confirmamos el intento explícito del usuario.
                    gameMaximizeConfirmed = true;
                    if (gameFrame.requestFullscreen) {
                        gameFrame.requestFullscreen().catch(() => {});
                    } else if (gameFrame.webkitRequestFullscreen) {
                        gameFrame.webkitRequestFullscreen();
                    }
                    gameMaximized = true;
                    if (gameStatus) gameStatus.textContent = 'Intento de maximización registrado.';
                    updateSubmitState();
                });

                document.addEventListener('fullscreenchange', () => {
                    if (document.fullscreenElement) {
                        gameMaximized = true;
                        if (gameStatus) gameStatus.textContent = 'Juego maximizado.';
                    } else {
                        gameMaximized = false;
                        if (gameStatus) {
                            gameStatus.textContent = gameMaximizeConfirmed
                                ? 'Maximización registrada. Puedes finalizar al cumplir el tiempo mínimo.'
                                : 'Debes maximizar el juego para finalizar.';
                        }
                    }
                    updateSubmitState();
                });
            } else {
                updateSubmitState();
            }

            document.querySelector('form').addEventListener('submit', () => {
                tiempoEl.value = seconds;
                tabEl.value = tabSwitch;
                sendEvent('submit', { tiempo_activo_total: seconds, tab_switch_count: tabSwitch });
            });
        })();
    </script>
</body>
</html>
