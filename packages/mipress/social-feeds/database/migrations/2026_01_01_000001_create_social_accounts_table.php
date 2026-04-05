<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 30);
            $table->string('platform_account_id');
            $table->string('name');
            $table->string('username')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('meta')->nullable();
            $table->json('errors')->nullable();
            $table->foreignId('connected_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'platform_account_id'], 'social_accounts_platform_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
