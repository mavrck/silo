<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('translated_title')->nullable()->after('content');
            $table->longText('translated_content')->nullable()->after('translated_title');
            $table->string('translated_language', 8)->nullable()->after('translated_content');
            $table->timestamp('translated_at')->nullable()->after('translated_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn(['translated_title', 'translated_content', 'translated_language', 'translated_at']);
        });
    }
};
