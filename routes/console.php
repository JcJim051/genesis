<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pausas:gamification-recalc', function () {
    app(\App\Services\PausaGamificationService::class)->recalculateAll();
    $this->info('Gamificación de pausas recalculada.');
})->purpose('Recalcular gamificación de pausas activas');

Schedule::command('encuestas:procesar-envios')->hourly();
Schedule::call(function () {
    app(\App\Http\Controllers\Admin\PausaEnvioCrudController::class)->procesarProgramados();
})->everyMinute();
Schedule::call(function () {
    app(\App\Http\Controllers\Admin\EncuestaEnvioCrudController::class)->procesarProgramados();
})->everyMinute();
