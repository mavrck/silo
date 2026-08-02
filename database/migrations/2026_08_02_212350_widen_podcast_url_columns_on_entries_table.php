<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real-world enclosure URLs are frequently chained through several
     * tracking/redirect services and blow well past varchar(255).
     */
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn(['enclosure_url', 'image_url']);
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->text('enclosure_url')->nullable()->after('url');
            $table->text('image_url')->nullable()->after('season_number');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn(['enclosure_url', 'image_url']);
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->string('enclosure_url')->nullable()->after('url');
            $table->string('image_url')->nullable()->after('season_number');
        });
    }
};
