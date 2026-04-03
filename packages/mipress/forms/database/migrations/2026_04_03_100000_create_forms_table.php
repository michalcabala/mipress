<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('handle')->unique();
            $table->text('description')->nullable();
            $table->json('fields');
            $table->json('recipients')->nullable();
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('auto_reply_subject')->nullable();
            $table->text('auto_reply_body')->nullable();
            $table->string('success_message')->default('Dekuujeme, formular byl odeslan.');
            $table->string('spam_protection')->default('honeypot');
            $table->string('recaptcha_site_key')->nullable();
            $table->string('recaptcha_secret_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
