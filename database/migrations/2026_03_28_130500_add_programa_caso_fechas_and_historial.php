<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_casos', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('sugerido_por');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });

        Schema::create('programa_caso_historials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_caso_id')->constrained('programa_casos')->cascadeOnDelete();
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_caso_historials');

        Schema::table('programa_casos', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio', 'fecha_fin']);
        });
    }
};
