<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osteo_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_caso_id')->constrained('programa_casos')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursals')->nullOnDelete();
            $table->foreignId('template_id')->constrained('osteo_templates')->cascadeOnDelete();
            $table->date('fecha_valoracion');
            $table->string('estado')->default('borrador');
            $table->string('evaluador')->nullable();
            $table->string('licencia')->nullable();
            $table->string('cargo_profesional')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['programa_caso_id', 'fecha_valoracion']);
            $table->index(['empleado_id', 'fecha_valoracion']);
            $table->index(['cliente_id', 'sucursal_id']);
        });

        Schema::create('osteo_evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('osteo_evaluations')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('osteo_template_fields')->cascadeOnDelete();
            $table->string('lado')->nullable();
            $table->text('valor')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_id', 'field_id', 'lado'], 'osteo_eval_field_side_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osteo_evaluation_answers');
        Schema::dropIfExists('osteo_evaluations');
    }
};

