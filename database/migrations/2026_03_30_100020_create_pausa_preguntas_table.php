<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausa_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->constrained('pausa_formularios')->cascadeOnDelete();
            $table->string('texto');
            $table->string('tipo')->default('abierta'); // abierta | opcion
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausa_preguntas');
    }
};
