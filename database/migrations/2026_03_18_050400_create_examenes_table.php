<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->string('cedula');
            $table->date('fecha_examen')->nullable();
            $table->string('tipo_examen')->nullable();
            $table->string('resultado_apto')->nullable();
            $table->text('restricciones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
