<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('singleton')->default(1)->unique();
            $table->json('static_urls')->nullable();
            $table->json('models')->nullable();
            $table->string('default_change_frequency')->nullable();
            $table->decimal('default_priority', 3, 2)->nullable();
            $table->boolean('auto_generate_enabled')->default(false);
            $table->string('auto_generate_frequency', 20)->nullable(); // 'daily', 'hourly'
            $table->string('storage_path', 100)->default('public');
            $table->string('filename', 100)->default('sitemap.xml');
            $table->boolean('gzip_enabled')->default(false);
            $table->unsignedInteger('chunk_size')->default(1000);
            $table->boolean('large_site_mode')->default(false);
            $table->boolean('enable_index_sitemap')->default(false);
            $table->string('output_mode', 20)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('disk', 100)->nullable();
            $table->string('disk_path', 255)->nullable();
            $table->string('visibility', 20)->nullable();

            $table->boolean('crawl_enabled')->nullable();
            $table->string('crawl_url', 500)->nullable();
            $table->unsignedInteger('concurrency')->nullable();
            $table->unsignedInteger('max_count')->nullable();
            $table->unsignedInteger('maximum_depth')->nullable();
            $table->json('exclude_patterns')->nullable();

            $table->string('crawl_profile', 500)->nullable();
            $table->string('should_crawl', 500)->nullable();
            $table->string('has_crawled', 500)->nullable();

            $table->boolean('execute_javascript')->nullable();
            $table->string('chrome_binary_path', 500)->nullable();
            $table->string('node_binary_path', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_settings');
    }
};
