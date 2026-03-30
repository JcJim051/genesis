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
        .video { margin: 16px 0 24px 0; }
        .question { margin-bottom: 20px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 10px; }
        .option { margin: 8px 0; }
        .btn { background: #0f172a; color: #fff; border: 0; padding: 10px 16px; border-radius: 8px; cursor: pointer; }
        .muted { color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $pausa->nombre }}</h1>
        <div class="meta">
            {{ $empleado?->nombre ?? 'Empleado' }} · Tiempo mínimo: {{ $pausa->tiempo_minimo_segundos }}s
        </div>

        <div class="video">
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
                    <iframe width="100%" height="420" src="https://www.youtube.com/embed/{{ $youtubeId }}" frameborder="0" allowfullscreen></iframe>
                @else
                    <video width="100%" height="420" controls>
                        <source src="{{ $url }}" type="video/mp4">
                    </video>
                @endif
            @else
                <div class="muted">No hay video configurado para esta pausa activa.</div>
            @endif
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

            <button class="btn" type="submit">Enviar</button>
        </form>
    </div>

    <script>
        (function() {
            const token = '{{ $participacion->token }}';
            const tiempoEl = document.getElementById('tiempo_activo_total');
            const tabEl = document.getElementById('tab_switch_count');
            let active = true;
            let seconds = 0;
            let tabSwitch = 0;
            let timerId = null;

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

            document.querySelector('form').addEventListener('submit', () => {
                tiempoEl.value = seconds;
                tabEl.value = tabSwitch;
                sendEvent('submit', { tiempo_activo_total: seconds, tab_switch_count: tabSwitch });
            });
        })();
    </script>
</body>
</html>
