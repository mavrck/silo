<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('enclosure_url')->nullable()->after('url');
            $table->string('enclosure_type')->nullable()->after('enclosure_url');
            $table->unsignedBigInteger('enclosure_length')->nullable()->after('enclosure_type');
            $table->unsignedInteger('duration_seconds')->nullable()->after('enclosure_length');
            $table->unsignedInteger('episode_number')->nullable()->after('duration_seconds');
            $table->unsignedInteger('season_number')->nullable()->after('episode_number');
            $table->string('image_url')->nullable()->after('season_number');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn([
                'enclosure_url',
                'enclosure_type',
                'enclosure_length',
                'duration_seconds',
                'episode_number',
                'season_number',
                'image_url',
            ]);
        });
    }
};
