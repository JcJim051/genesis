<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_pausa_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id')->unique();
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedInteger('total_completadas')->default(0);
            $table->unsignedInteger('current_streak_weeks')->default(0);
            $table->unsignedInteger('best_streak_weeks')->default(0);
            $table->string('current_week_key')->nullable();
            $table->unsignedInteger('current_week_count')->default(0);
            $table->timestamp('last_completed_at')->nullable();
            $table->timestamps();

            $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_pausa_stats');
    }
};
