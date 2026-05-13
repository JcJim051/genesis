<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .title { text-align: center; font-size: 16px; margin-bottom: 12px; }
        .section { margin-bottom: 10px; }
        .genesis-watermark {
            position: fixed;
            right: 10px;
            bottom: 8px;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    @php $genesisLogoPath = public_path('images/brand/genesis-email.png'); @endphp
    <div class="title">Acta de Ingreso - Reincorporación</div>

    <div class="section"><strong>ID Acta:</strong> {{ $acta->id }}</div>
    <div class="section"><strong>Fecha:</strong> {{ optional($acta->fecha_acta)->format('Y-m-d') }}</div>
    <div class="section"><strong>Persona:</strong> {{ optional($acta->reincorporacion->empleado)->nombre }}</div>

    <div class="section">
        <strong>Contenido:</strong><br>
        {!! nl2br(e($acta->contenido)) !!}
    </div>
    <div class="genesis-watermark">
        @if(file_exists($genesisLogoPath))
            <img src="{{ $genesisLogoPath }}" alt="Genesis" style="height:10px; vertical-align:middle; margin-right:4px;">
        @endif
        <span style="vertical-align:middle;">Generado por plataforma Genesis</span>
    </div>
</body>
</html>
