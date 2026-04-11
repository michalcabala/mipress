<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\ResourceLockResource;
use MiPress\Core\Filament\Resources\ResourceLockResource\Pages\ManageResourceLocks;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($admin);
});

it('uses php-based czech labels in the resource lock manager table', function () {
    $this->get(ResourceLockResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('Správa zámků');

    $table = Livewire::test(ManageResourceLocks::class)
        ->instance()
        ->getTable();

    expect($table->getColumn('id')?->getLabel())->toBe('ID zámku')
        ->and($table->getColumn('user.id')?->getLabel())->toBe('ID uživatele')
        ->and($table->getColumn('lockable.id')?->getLabel())->toBe('ID záznamu')
        ->and($table->getColumn('lockable_type')?->getLabel())->toBe('Typ záznamu')
        ->and($table->getColumn('created_at')?->getLabel())->toBe('Vytvořeno')
        ->and($table->getColumn('updated_at')?->getLabel())->toBe('Aktualizováno')
        ->and($table->getColumn('lock_status')?->getLabel())->toBe('Stav');
});
