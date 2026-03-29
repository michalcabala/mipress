<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Panel;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;

test('cms roles can access the admin panel', function (UserRole $role) {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole($role->value);

    expect($user->canAccessPanel(Mockery::mock(Panel::class)))->toBeTrue();
})->with([
    UserRole::SuperAdmin,
    UserRole::Admin,
    UserRole::Editor,
    UserRole::Contributor,
]);

test('users without a cms role cannot access the admin panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Mockery::mock(Panel::class)))->toBeFalse();
});
