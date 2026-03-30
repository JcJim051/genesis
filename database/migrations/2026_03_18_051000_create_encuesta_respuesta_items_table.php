<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_respuesta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('respuesta_id')->constrained('encuesta_respuestas')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('encuesta_preguntas')->cascadeOnDelete();
            $table->foreignId('opcion_id')->constrained('encuesta_opciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('puntaje')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_respuesta_items');
    }
};
