<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Database\Seeders\SettingSeeder;
use MiPress\Core\Enums\UserRole;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SettingSeeder::class,
        ]);

        if (User::count() !== 0) {
            return;
        }

        $adminEmail = config('mipress.admin_email');
        $adminPassword = config('mipress.admin_password');

        if (! is_string($adminEmail) || trim($adminEmail) === '' || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->command?->warn('Bootstrap super admin was not created: set a valid MIPRESS_ADMIN_EMAIL value.');

            return;
        }

        if (! is_string($adminPassword) || $adminPassword === '') {
            $this->command?->warn('Bootstrap super admin was not created: set a non-empty MIPRESS_ADMIN_PASSWORD value.');

            return;
        }

        $user = User::create([
            'name' => 'Super Admin',
            'email' => trim($adminEmail),
            'password' => Hash::make($adminPassword),
            'email_verified_at' => now(),
        ]);

        $user->assignRole(UserRole::SuperAdmin);

        $this->command?->info('Bootstrap super admin created: '.$user->email);
    }
}
