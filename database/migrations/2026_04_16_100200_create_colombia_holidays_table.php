<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colombia_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre');
            $table->unsignedSmallInteger('anio');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['anio', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colombia_holidays');
    }
};
