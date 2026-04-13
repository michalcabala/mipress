<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MiPress\Core\Database\Seeders\GlobalSetSeeder;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            GlobalSetSeeder::class,
        ]);

        if (User::count() === 0) {
            $user = User::create([
                'name' => 'Super Admin',
                'email' => config('mipress.admin_email', 'admin@mipress.cz'),
                'password' => Hash::make(config('mipress.admin_password', 'password')),
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::SuperAdmin);

            $this->command->info('Default super admin created: '.$user->email);
        }
    }
}
