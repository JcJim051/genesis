<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_pausa_badges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('badge_id');
            $table->timestamp('awarded_at')->nullable();

            $table->unique(['empleado_id', 'badge_id']);
            $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
            $table->foreign('badge_id')->references('id')->on('pausa_badges')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_pausa_badges');
    }
};
