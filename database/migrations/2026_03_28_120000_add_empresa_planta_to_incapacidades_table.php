<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incapacidades', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('cedula')->constrained('clientes')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->after('cliente_id')->constrained('sucursals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incapacidades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropConstrainedForeignId('sucursal_id');
        });
    }
};
