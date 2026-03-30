<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausa_participacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participacion_id')->constrained('pausa_participaciones')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('pausa_preguntas')->cascadeOnDelete();
            $table->foreignId('opcion_id')->nullable()->constrained('pausa_opciones')->nullOnDelete();
            $table->text('respuesta_texto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausa_participacion_items');
    }
};
