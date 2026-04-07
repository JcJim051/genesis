<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activación Telegram</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Activación de Telegram - Genesis SST</h2>
    <p>Hola {{ $empleado->nombre }},</p>
    <p>Para activar tu canal de Telegram y recibir notificaciones, haz clic en el siguiente enlace:</p>
    <p><a href="{{ $link }}" target="_blank">Activar Telegram</a></p>
    <p>Si el enlace no abre, copia y pega esta URL en tu navegador:</p>
    <p>{{ $link }}</p>
    <p style="margin-top: 24px;">Gracias,</p>
    <p>Equipo Genesis SST</p>
</body>
</html>
