<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .title { text-align: center; font-size: 16px; margin-bottom: 12px; }
        .section { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="title">Acta de Seguimiento - Reincorporación</div>

    <div class="section"><strong>ID Acta:</strong> {{ $acta->id }}</div>
    <div class="section"><strong>Fecha:</strong> {{ optional($acta->fecha_acta)->format('Y-m-d') }}</div>
    <div class="section"><strong>Persona:</strong> {{ optional($acta->reincorporacion->empleado)->nombre }}</div>

    <div class="section">
        <strong>Contenido:</strong><br>
        {!! nl2br(e($acta->contenido)) !!}
    </div>
</body>
</html>
