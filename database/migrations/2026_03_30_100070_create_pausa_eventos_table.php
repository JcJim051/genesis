<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausa_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participacion_id')->constrained('pausa_participaciones')->cascadeOnDelete();
            $table->string('tipo');
            $table->timestamp('timestamp');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausa_eventos');
    }
};
