<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostico_programa_map', function (Blueprint $table) {
            $table->foreignId('cie10_id')->nullable()->after('id')->constrained('cie10s')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('diagnostico_programa_map', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cie10_id');
        });
    }
};
