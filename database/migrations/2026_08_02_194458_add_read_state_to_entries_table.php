<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('published_at');
            $table->timestamp('read_at')->nullable()->after('is_read');
            $table->boolean('is_starred')->default(false)->after('read_at');

            $table->index(['feed_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex(['feed_id', 'is_read']);
            $table->dropColumn(['is_read', 'read_at', 'is_starred']);
        });
    }
};
