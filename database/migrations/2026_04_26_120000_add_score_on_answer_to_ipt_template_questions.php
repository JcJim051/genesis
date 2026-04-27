<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipt_template_questions', function (Blueprint $table) {
            $table->string('score_on_answer', 5)->default('si')->after('si_score');
        });
    }

    public function down(): void
    {
        Schema::table('ipt_template_questions', function (Blueprint $table) {
            $table->dropColumn('score_on_answer');
        });
    }
};

