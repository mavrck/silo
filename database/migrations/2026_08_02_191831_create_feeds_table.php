<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('site_url')->nullable();
            $table->text('description')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('etag')->nullable();
            $table->timestamp('last_modified_at')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->text('last_fetch_error')->nullable();
            $table->unsignedInteger('fetch_interval_minutes')->default(30);
            $table->timestamps();

            $table->unique(['user_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeds');
    }
};
