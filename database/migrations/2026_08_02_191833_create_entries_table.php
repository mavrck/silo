<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->string('guid');
            $table->string('url')->nullable();
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['feed_id', 'guid']);
            $table->index(['feed_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
