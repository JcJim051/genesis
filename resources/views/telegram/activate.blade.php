<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activar Telegram</title>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f4f9ff 0%, #f3fbf7 55%, #f7fbff 100%);
            color: #0f172a;
        }
        .wrap { padding: 28px 16px 40px; }
        .card {
            background: #fff;
            border-radius: 16px;
            max-width: 560px;
            margin: 0 auto;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.10);
            overflow: hidden;
        }
        .header {
            padding: 10px 20px 8px;
            text-align: center;
            background: linear-gradient(135deg, #f4f9ff 0%, #f3fbf7 55%, #f7fbff 100%);
        }
        .header img { max-width: 380px; width: 90%; height: auto; display: inline-block; }
        .content { padding: 24px 26px 8px; }
        .title { margin: 0 0 10px; font-size: 22px; }
        .muted { color: #475569; font-size: 14px; line-height: 1.6; }
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background: #2563eb;
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
        }
        .code {
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 8px;
            display: inline-block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 13px;
            word-break: break-all;
        }
        .footer {
            padding: 0 26px 22px;
            color: #64748b;
            font-size: 12px;
            text-align: center;
        }
        .section { margin: 14px 0; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header">
                <img src="{{ asset('images/brand/genesis-email.png') }}" alt="Genesis SST">
            </div>
            <div class="content">
                <h2 class="title">Activar bot de Telegram</h2>
                <p class="muted section">Si estás en el celular, pulsa el botón para abrir Telegram y activar el bot.</p>
                <p class="section"><a class="btn" href="{{ $startLink }}">Abrir Telegram</a></p>
                <p class="muted section">Si no se abre, copia este enlace en el navegador:</p>
                <p class="code section">{{ $startLink }}</p>
                <p class="muted section">O en Telegram, busca <strong>{{ '@' . $botUser }}</strong> y envía:</p>
                <p class="code section">/start {{ $payload }}</p>
            </div>
            <div class="footer">
                Este enlace es personal. Si no solicitaste esta activación, ignóralo.
            </div>
        </div>
    </div>
    <script>
      // Intento directo a la app (si está instalada)
      setTimeout(() => {
        window.location.href = "{{ $tgLink }}";
      }, 300);
    </script>
</body>
</html>
