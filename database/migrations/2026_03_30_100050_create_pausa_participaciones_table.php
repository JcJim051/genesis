<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausa_participaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envio_id')->constrained('pausa_envios')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->string('estado')->default('pendiente');
            $table->unsignedInteger('tiempo_activo_total')->default(0);
            $table->unsignedInteger('tab_switch_count')->default(0);
            $table->timestamp('respondido_en')->nullable();
            $table->string('whatsapp_message_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausa_participaciones');
    }
};
