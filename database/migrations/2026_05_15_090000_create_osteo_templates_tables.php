<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osteo_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre_publico');
            $table->string('codigo')->nullable();
            $table->string('segmento')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['cliente_id', 'activo']);
        });

        Schema::create('osteo_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('osteo_templates')->cascadeOnDelete();
            $table->string('titulo');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->index(['template_id', 'orden']);
        });

        Schema::create('osteo_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('osteo_template_sections')->cascadeOnDelete();
            $table->string('key_name')->nullable();
            $table->string('label');
            $table->string('tipo')->default('text');
            $table->json('options_json')->nullable();
            $table->json('meta_json')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->index(['section_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osteo_template_fields');
        Schema::dropIfExists('osteo_template_sections');
        Schema::dropIfExists('osteo_templates');
    }
};

