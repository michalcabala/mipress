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
        Schema::table('curator', function (Blueprint $table) {
            $table->unsignedTinyInteger('focal_point_x')->default(50)->after('curations');
            $table->unsignedTinyInteger('focal_point_y')->default(50)->after('focal_point_x');
        });
    }

    public function down(): void
    {
        Schema::table('curator', function (Blueprint $table) {
            $table->dropColumn(['focal_point_x', 'focal_point_y']);
        });
    }
};
