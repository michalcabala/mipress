<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('social_account_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('feed_type', 30)->default('timeline');
            $table->string('layout', 20)->default('list');
            $table->unsignedSmallInteger('posts_count')->default(5);
            $table->unsignedInteger('cache_ttl')->default(3600);
            $table->json('settings')->nullable();
            $table->json('filter_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_feeds');
    }
};
