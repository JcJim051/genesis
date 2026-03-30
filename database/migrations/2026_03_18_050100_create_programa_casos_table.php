<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programa_casos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('programa_id')->constrained('programas')->cascadeOnDelete();
            $table->string('estado')->default('No caso');
            $table->string('origen')->nullable();
            $table->string('sugerido_por')->nullable();
            $table->timestamps();

            $table->unique(['empleado_id', 'programa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_casos');
    }
};
