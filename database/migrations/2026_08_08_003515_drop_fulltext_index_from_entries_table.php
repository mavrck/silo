<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nothing to drop on drivers where the earlier migration never
        // created the index in the first place (see that migration).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('entries', function (Blueprint $table) {
            $table->dropFullText(['title', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('entries', function (Blueprint $table) {
            $table->fullText(['title', 'content']);
        });
    }
};
