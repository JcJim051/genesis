<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pausas', function (Blueprint $table) {
            $table->string('external_url')->nullable()->after('video_url');
            $table->string('external_provider')->nullable()->after('external_url');
        });
    }

    public function down(): void
    {
        Schema::table('pausas', function (Blueprint $table) {
            $table->dropColumn(['external_url', 'external_provider']);
        });
    }
};
