<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipt_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre_publico');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['cliente_id', 'activo']);
        });

        Schema::create('ipt_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ipt_templates')->cascadeOnDelete();
            $table->string('titulo');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'orden']);
        });

        Schema::create('ipt_template_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('ipt_template_sections')->cascadeOnDelete();
            $table->text('texto');
            $table->string('tipo')->default('si_no_na');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('scorable')->default(true);
            $table->unsignedSmallInteger('si_score')->default(1);
            $table->timestamps();

            $table->index(['section_id', 'orden']);
        });

        Schema::create('ipt_template_risk_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ipt_templates')->cascadeOnDelete();
            $table->string('nivel');
            $table->integer('min_score');
            $table->integer('max_score');
            $table->unsignedSmallInteger('followup_months');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'orden']);
            $table->unique(['template_id', 'nivel']);
        });

        Schema::create('ipt_template_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ipt_templates')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['template_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipt_template_requirements');
        Schema::dropIfExists('ipt_template_risk_rules');
        Schema::dropIfExists('ipt_template_questions');
        Schema::dropIfExists('ipt_template_sections');
        Schema::dropIfExists('ipt_templates');
    }
};
