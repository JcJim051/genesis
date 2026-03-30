<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $encuesta->titulo }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; }
        .container { max-width: 820px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        h1 { margin: 0 0 16px 0; font-size: 24px; }
        .meta { color: #666; margin-bottom: 24px; }
        .question { margin-bottom: 20px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 10px; }
        .option { margin: 8px 0; }
        .btn { background: #0f172a; color: #fff; border: 0; padding: 10px 16px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $encuesta->titulo }}</h1>
        <div class="meta">
            {{ $empleado?->nombre ?? 'Empleado' }}
        </div>

        <form method="post" action="">
            @csrf
            @foreach($encuesta->preguntas->sortBy('orden') as $pregunta)
                <div class="question">
                    <strong>{{ $pregunta->texto }}</strong>
                    @foreach($pregunta->opciones->sortBy('orden') as $opcion)
                        <div class="option">
                            <label>
                                <input type="radio" name="pregunta_{{ $pregunta->id }}" value="{{ $opcion->id }}">
                                {{ $opcion->texto }}
                            </label>
                        </div>
                    @endforeach
                    @error('pregunta_'.$pregunta->id)
                        <div style="color: #c00; margin-top: 6px;">Debe seleccionar una opción.</div>
                    @enderror
                </div>
            @endforeach

            <button class="btn" type="submit">Enviar</button>
        </form>
    </div>
</body>
</html>
