<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipt_templates', function (Blueprint $table) {
            $table->string('evidencia_fotografica_modo', 20)
                ->default('none')
                ->after('activo');
            $table->boolean('mostrar_accion')->default(true)->after('evidencia_fotografica_modo');
            $table->boolean('mostrar_responsable')->default(true)->after('mostrar_accion');
        });

        Schema::table('ipt_inspections', function (Blueprint $table) {
            $table->string('foto_general')->nullable()->after('foto_despues');
        });
    }

    public function down(): void
    {
        Schema::table('ipt_inspections', function (Blueprint $table) {
            $table->dropColumn('foto_general');
        });

        Schema::table('ipt_templates', function (Blueprint $table) {
            $table->dropColumn(['evidencia_fotografica_modo', 'mostrar_accion', 'mostrar_responsable']);
        });
    }
};

