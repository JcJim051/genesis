<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausa_badges', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('pausa_badges')->insert([
            [
                'code' => 'first_pause',
                'nombre' => 'Primera pausa',
                'descripcion' => 'Completaste tu primera pausa activa.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ten_pauses',
                'nombre' => '10 pausas',
                'descripcion' => 'Completaste 10 pausas activas.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'streak_4w',
                'nombre' => 'Racha 4 semanas',
                'descripcion' => 'Cumpliste 4 semanas seguidas con pausas activas.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'streak_12w',
                'nombre' => 'Racha 12 semanas',
                'descripcion' => 'Cumpliste 12 semanas seguidas con pausas activas.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pausa_badges');
    }
};
