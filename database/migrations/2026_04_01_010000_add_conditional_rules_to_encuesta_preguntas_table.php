<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encuesta_preguntas', function (Blueprint $table) {
            $table->json('conditional_rules')->nullable()->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('encuesta_preguntas', function (Blueprint $table) {
            $table->dropColumn('conditional_rules');
        });
    }
};
