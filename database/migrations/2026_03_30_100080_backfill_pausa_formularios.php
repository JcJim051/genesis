<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pausas = DB::table('pausas')->get(['id']);
        foreach ($pausas as $pausa) {
            $exists = DB::table('pausa_formularios')->where('pausa_id', $pausa->id)->exists();
            if (! $exists) {
                DB::table('pausa_formularios')->insert([
                    'pausa_id' => $pausa->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // no-op
    }
};
