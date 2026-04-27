<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipt_templates', function (Blueprint $table) {
            $table->string('codigo')->nullable()->after('nombre_publico');
            $table->string('segmento')->nullable()->after('codigo');
            $table->index(['cliente_id', 'activo', 'segmento'], 'ipt_templates_scope_segmento_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ipt_templates', function (Blueprint $table) {
            $table->dropIndex('ipt_templates_scope_segmento_idx');
            $table->dropColumn(['codigo', 'segmento']);
        });
    }
};
