<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activar Telegram</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; background: #f7f7f7; }
        .card { background: #fff; border-radius: 10px; padding: 20px; max-width: 520px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .btn { display: inline-block; padding: 10px 16px; background: #229ED9; color: #fff; border-radius: 6px; text-decoration: none; }
        .muted { color: #666; font-size: 14px; }
        .code { background: #f0f0f0; padding: 6px 10px; border-radius: 6px; display: inline-block; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Activar bot de Telegram</h2>
        <p class="muted">Si estás en el celular, pulsa el botón para abrir Telegram y activar el bot.</p>
        <p><a class="btn" href="{{ $startLink }}">Abrir Telegram</a></p>
        <p class="muted">Si no se abre, copia este enlace en el navegador:</p>
        <p class="code">{{ $startLink }}</p>
        <p class="muted">O en Telegram, busca <strong>{{ '@' . $botUser }}</strong> y envía:</p>
        <p class="code">/start {{ $payload }}</p>
    </div>
    <script>
      // Intento directo a la app (si está instalada)
      setTimeout(() => {
        window.location.href = "{{ $tgLink }}";
      }, 300);
    </script>
</body>
</html>
