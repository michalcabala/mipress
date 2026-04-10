<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')
                ->nullable()
                ->after('avatar_id');
        });

        DB::table('users')
            ->select('users.id', 'curator.path')
            ->join('curator', 'curator.id', '=', 'users.avatar_id')
            ->whereNull('users.avatar_path')
            ->orderBy('users.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('users')
                    ->where('id', $row->id)
                    ->update(['avatar_path' => $row->path]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('avatar_path');
        });
    }
};
