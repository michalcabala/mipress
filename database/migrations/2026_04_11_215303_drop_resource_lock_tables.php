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
        Schema::dropIfExists('resource_lock_audit');
        Schema::dropIfExists('resource_locks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('resource_locks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained();
            $table->morphs('lockable');
        });

        Schema::create('resource_lock_audit', function (Blueprint $table) {
            $table->id();
            $table->string('action', 32);
            $table->morphs('lockable');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
