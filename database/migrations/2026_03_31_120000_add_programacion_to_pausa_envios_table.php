<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pausa_envios', function (Blueprint $table) {
            $table->timestamp('programado_para')->nullable()->after('procesado_en');
            $table->string('programado_modo')->nullable()->after('programado_para');
            $table->boolean('programado_solo_no_completados')->default(true)->after('programado_modo');
        });
    }

    public function down(): void
    {
        Schema::table('pausa_envios', function (Blueprint $table) {
            $table->dropColumn(['programado_para', 'programado_modo', 'programado_solo_no_completados']);
        });
    }
};
