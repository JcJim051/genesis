<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reincorporaciones', function (Blueprint $table) {
            $table->json('acta_payload')->nullable()->after('fecha_ingreso');
            $table->string('acta_pdf_path')->nullable()->after('acta_payload');
            $table->string('evidencia_pdf_path')->nullable()->after('acta_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('reincorporaciones', function (Blueprint $table) {
            $table->dropColumn(['acta_payload', 'acta_pdf_path', 'evidencia_pdf_path']);
        });
    }
};
