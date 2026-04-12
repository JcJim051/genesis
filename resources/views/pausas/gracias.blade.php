<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pausa activa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; }
        .container { max-width: 700px; margin: 80px auto; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 8px 24px rgba(0,0,0,.08); text-align: center; }
        h1 { margin: 0 0 12px 0; font-size: 24px; }
        p { color: #374151; }
        .stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 20px; }
        .stat-card { background: #f8fafc; border-radius: 10px; padding: 12px; text-align: center; }
        .stat-card h3 { margin: 0; font-size: 18px; color: #0f172a; }
        .stat-card span { display: block; margin-top: 4px; font-size: 12px; color: #64748b; }
        .badges { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 16px; }
        .badge { background: #e2e8f0; color: #0f172a; border-radius: 999px; padding: 6px 10px; font-size: 12px; }
        .new { background: #dcfce7; color: #166534; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gracias</h1>
        <p>{{ $mensaje }}</p>

        @if (!empty($stats))
            <div class="stats">
                <div class="stat-card">
                    <h3>{{ $stats->total_points }}</h3>
                    <span>Puntos</span>
                </div>
                <div class="stat-card">
                    <h3>{{ $stats->total_completadas }}</h3>
                    <span>Pausas completadas</span>
                </div>
                <div class="stat-card">
                    <h3>{{ $stats->current_streak_weeks }}</h3>
                    <span>Racha actual (semanas)</span>
                </div>
                <div class="stat-card">
                    <h3>{{ $stats->best_streak_weeks }}</h3>
                    <span>Mejor racha</span>
                </div>
            </div>

            @if (!empty($awardedBadges) && $awardedBadges->count() > 0)
                <p style="margin-top:16px;">¡Desbloqueaste nuevos logros!</p>
                <div class="badges">
                    @foreach ($awardedBadges as $badge)
                        <span class="badge new">{{ $badge->nombre }}</span>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</body>
</html>
