<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_feed_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('platform_post_id');
            $table->string('post_type', 30)->nullable();
            $table->text('content')->nullable();
            $table->json('media')->nullable();
            $table->json('engagement')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_avatar_url')->nullable();
            $table->string('permalink')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['social_feed_id', 'platform_post_id'], 'social_posts_feed_post_unique');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
