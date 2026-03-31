<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EncuestaPublicController;
use App\Http\Controllers\PausaPublicController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/encuestas/{token}', [EncuestaPublicController::class, 'show'])->name('encuestas.show');
Route::post('/encuestas/{token}', [EncuestaPublicController::class, 'submit'])->name('encuestas.submit');

Route::get('/pausas/{token}', [PausaPublicController::class, 'show'])->name('pausas.show');
Route::post('/pausas/{token}', [PausaPublicController::class, 'submit'])->name('pausas.submit');
Route::post('/pausas/{token}/event', [PausaPublicController::class, 'event'])->name('pausas.event');
