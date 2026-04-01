<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pausas', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('categoria')->constrained('clientes')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->after('cliente_id')->constrained('sucursals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pausas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sucursal_id');
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
