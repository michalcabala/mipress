<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use MiPress\Core\Enums\UserRole;

it('does not create bootstrap super admin when credentials are missing', function () {
    config()->set('mipress.admin_email', null);
    config()->set('mipress.admin_password', null);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0);
});

it('creates bootstrap super admin when explicit credentials are configured', function () {
    config()->set('mipress.admin_email', 'admin@example.test');
    config()->set('mipress.admin_password', 'super-secret-password');

    $this->seed(DatabaseSeeder::class);

    $user = User::query()->sole();

    expect($user->email)->toBe('admin@example.test')
        ->and(Hash::check('super-secret-password', $user->password))->toBeTrue()
        ->and($user->hasRole(UserRole::SuperAdmin->value))->toBeTrue();
});
