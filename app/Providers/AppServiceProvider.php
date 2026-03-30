<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Empleado;
use App\Models\Programa;
use App\Observers\EmpleadoObserver;
use App\Observers\ProgramaObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Empleado::observe(EmpleadoObserver::class);
        Programa::observe(ProgramaObserver::class);
    }
}
