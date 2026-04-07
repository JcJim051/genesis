<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EncuestaPublicController;
use App\Http\Controllers\PausaPublicController;
use App\Http\Controllers\TelegramActivationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/encuestas/{token}', [EncuestaPublicController::class, 'show'])->name('encuestas.show');
Route::post('/encuestas/{token}', [EncuestaPublicController::class, 'submit'])->name('encuestas.submit');

Route::get('/pausas/{token}', [PausaPublicController::class, 'show'])->name('pausas.show');
Route::post('/pausas/{token}', [PausaPublicController::class, 'submit'])->name('pausas.submit');
Route::post('/pausas/{token}/event', [PausaPublicController::class, 'event'])->name('pausas.event');

Route::get('/a/{token}', TelegramActivationController::class)->name('telegram.activate');

Route::get('/preview-telegram-email', function () {
    $empleado = (object) ['nombre' => 'Alejandra Nupia'];
    $link = 'https://genesis.testiapp.com/a/DEMO123';
    return view('emails.telegram_activation', compact('empleado', 'link'));
});
