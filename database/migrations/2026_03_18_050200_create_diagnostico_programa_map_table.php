<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostico_programa_map', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_cie10')->nullable();
            $table->string('diagnostico_texto')->nullable();
            $table->foreignId('programa_id')->constrained('programas')->cascadeOnDelete();
            $table->boolean('regla_activa')->default(true);
            $table->unsignedSmallInteger('prioridad')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostico_programa_map');
    }
};
