<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipt_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_caso_id')->constrained('programa_casos')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursals')->nullOnDelete();
            $table->foreignId('template_id')->constrained('ipt_templates')->cascadeOnDelete();
            $table->string('tipo')->default('initial');
            $table->unsignedBigInteger('initial_inspection_id')->nullable();
            $table->date('fecha_inspeccion');
            $table->unsignedInteger('puntaje_total')->default(0);
            $table->string('nivel_riesgo')->nullable();
            $table->date('fecha_proximo_seguimiento_sugerida')->nullable();
            $table->text('hallazgos')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->text('accion')->nullable();
            $table->string('responsable')->nullable();
            $table->string('estado')->default('abierto');
            $table->boolean('seguimiento_exitoso')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('initial_inspection_id')
                ->references('id')
                ->on('ipt_inspections')
                ->nullOnDelete();

            $table->index(['programa_caso_id', 'fecha_inspeccion']);
            $table->index(['empleado_id', 'fecha_inspeccion']);
            $table->index(['cliente_id', 'sucursal_id']);
            $table->index(['tipo', 'initial_inspection_id']);
        });

        Schema::create('ipt_inspection_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('ipt_inspections')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('ipt_template_questions')->cascadeOnDelete();
            $table->string('respuesta')->nullable();
            $table->unsignedSmallInteger('score')->default(0);
            $table->timestamps();

            $table->unique(['inspection_id', 'question_id']);
        });

        Schema::create('ipt_inspection_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('ipt_inspections')->cascadeOnDelete();
            $table->foreignId('requirement_id')->constrained('ipt_template_requirements')->cascadeOnDelete();
            $table->boolean('aplica')->default(false);
            $table->timestamps();

            $table->unique(['inspection_id', 'requirement_id']);
        });

        // SQLite (local) no soporta este ALTER TABLE ADD CONSTRAINT en esta forma.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ipt_inspections ADD CONSTRAINT chk_ipt_tipo CHECK (tipo IN ('initial','followup'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ipt_inspection_requirements');
        Schema::dropIfExists('ipt_inspection_answers');
        Schema::dropIfExists('ipt_inspections');
    }
};
