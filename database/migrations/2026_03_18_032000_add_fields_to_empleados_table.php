<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('cedula')->nullable()->after('nombre');
            $table->string('eps')->nullable()->after('cedula');
            $table->string('telefono')->nullable()->after('cargo');
            $table->string('tipo_contrato')->nullable()->after('correo_electronico');
            $table->unsignedTinyInteger('edad')->nullable()->after('tipo_contrato');
            $table->string('lateralidad')->nullable()->after('edad');
            $table->string('genero')->nullable()->after('lateralidad');
            $table->date('fecha_nacimiento')->nullable()->after('genero');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn([
                'cedula',
                'eps',
                'telefono',
                'tipo_contrato',
                'edad',
                'lateralidad',
                'genero',
                'fecha_nacimiento',
            ]);
        });
    }
};
