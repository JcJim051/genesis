<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('arl')->nullable()->after('eps');
            $table->string('fondo_pensiones')->nullable()->after('arl');
            $table->date('fecha_ingreso')->nullable()->after('fecha_nacimiento');
            $table->string('direccion')->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn([
                'arl',
                'fondo_pensiones',
                'fecha_ingreso',
                'direccion',
            ]);
        });
    }
};
