<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programa_caso_incapacidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_caso_id')->constrained('programa_casos')->cascadeOnDelete();
            $table->foreignId('incapacidad_id')->constrained('incapacidades')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['programa_caso_id', 'incapacidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_caso_incapacidad');
    }
};
