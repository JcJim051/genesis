<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipt_templates', function (Blueprint $table) {
            $table->boolean('mostrar_hallazgos')->default(true)->after('mostrar_responsable');
            $table->boolean('mostrar_observaciones')->default(true)->after('mostrar_hallazgos');
            $table->boolean('mostrar_recomendaciones')->default(true)->after('mostrar_observaciones');
            $table->boolean('mostrar_estado')->default(true)->after('mostrar_recomendaciones');
        });
    }

    public function down(): void
    {
        Schema::table('ipt_templates', function (Blueprint $table) {
            $table->dropColumn([
                'mostrar_hallazgos',
                'mostrar_observaciones',
                'mostrar_recomendaciones',
                'mostrar_estado',
            ]);
        });
    }
};
