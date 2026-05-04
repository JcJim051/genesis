<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipt_inspections', function (Blueprint $table) {
            $table->string('foto_antes')->nullable()->after('fecha_proximo_seguimiento_sugerida');
            $table->string('foto_despues')->nullable()->after('foto_antes');
        });
    }

    public function down(): void
    {
        Schema::table('ipt_inspections', function (Blueprint $table) {
            $table->dropColumn(['foto_antes', 'foto_despues']);
        });
    }
};

