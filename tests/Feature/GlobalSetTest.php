<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Pages\ThemeSettings;
use MiPress\Core\Filament\Resources\GlobalSetResource;
use MiPress\Core\Filament\Resources\GlobalSetResource\Pages\CreateGlobalSet;
use MiPress\Core\Filament\Resources\GlobalSetResource\Pages\EditGlobalSet;
use MiPress\Core\Models\GlobalSet;
use MiPress\Core\Services\GlobalSetManager;

// --- Model: get() / set() ---

it('reads a value from the data column', function () {
    $set = GlobalSet::factory()->create([
        'handle' => 'general',
        'data' => ['site_name' => 'MiPress'],
    ]);

    expect($set->get('site_name'))->toBe('MiPress');
});

it('returns the default when the key does not exist', function () {
    $set = GlobalSet::factory()->create([
        'handle' => 'general',
        'data' => [],
    ]);

    expect($set->get('missing', 'fallback'))->toBe('fallback');
});

it('sets a value on the data column and returns self', function () {
    $set = GlobalSet::factory()->create([
        'handle' => 'general',
        'data' => [],
    ]);

    $result = $set->set('site_name', 'Test');

    expect($result)->toBe($set)
        ->and($set->get('site_name'))->toBe('Test');
});

it('persists set() changes after save', function () {
    $set = GlobalSet::factory()->create([
        'handle' => 'general',
        'data' => ['old_key' => 'old_value'],
    ]);

    $set->set('new_key', 'new_value')->save();

    $fresh = $set->fresh();

    expect($fresh->get('old_key'))->toBe('old_value')
        ->and($fresh->get('new_key'))->toBe('new_value');
});

// --- Handle validation ---

it('enforces unique handles', function () {
    GlobalSet::factory()->create(['handle' => 'general']);

    expect(fn () => GlobalSet::factory()->create(['handle' => 'general']))
        ->toThrow(QueryException::class);
});

// --- GlobalSetManager ---

it('finds a global set by handle', function () {
    GlobalSet::factory()->create(['handle' => 'social', 'title' => 'Social']);

    $manager = app(GlobalSetManager::class);

    expect($manager->find('social'))
        ->toBeInstanceOf(GlobalSet::class)
        ->title->toBe('Social');
});

it('returns null for a nonexistent handle', function () {
    $manager = app(GlobalSetManager::class);

    expect($manager->find('nonexistent'))->toBeNull();
});

it('returns all global sets', function () {
    GlobalSet::factory()->count(3)->create();

    $manager = app(GlobalSetManager::class);

    expect($manager->all())->toHaveCount(3);
});

it('reads a single value via the manager shortcut', function () {
    GlobalSet::factory()->create([
        'handle' => 'seo',
        'data' => ['gtm_code' => 'GTM-ABC123'],
    ]);

    $manager = app(GlobalSetManager::class);

    expect($manager->get('seo', 'gtm_code'))->toBe('GTM-ABC123');
});

it('returns default from manager when set does not exist', function () {
    $manager = app(GlobalSetManager::class);

    expect($manager->get('missing', 'key', 'default'))->toBe('default');
});

// --- global_set() helper ---

it('reads a value using helper with handle.key format', function () {
    GlobalSet::factory()->create([
        'handle' => 'general',
        'data' => ['site_name' => 'My Site'],
    ]);

    expect(global_set('general.site_name'))->toBe('My Site');
});

it('returns default from helper for missing key', function () {
    GlobalSet::factory()->create([
        'handle' => 'general',
        'data' => [],
    ]);

    expect(global_set('general.missing', 'fallback'))->toBe('fallback');
});

it('returns null from helper for nonexistent set', function () {
    expect(global_set('nonexistent.key'))->toBeNull();
});

it('returns the GlobalSet model when called with handle only', function () {
    $set = GlobalSet::factory()->create(['handle' => 'general']);

    $result = global_set('general');

    expect($result)
        ->toBeInstanceOf(GlobalSet::class)
        ->handle->toBe('general');
});

// --- Filament Resource ---

it('can render the global sets list page', function () {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $this->get(GlobalSetResource::getUrl('index'))
        ->assertSuccessful();
});

it('can render the global set create page', function () {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $this->get(GlobalSetResource::getUrl('create'))
        ->assertSuccessful();
});

it('can render the global set edit page', function () {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $set = GlobalSet::factory()->create();

    $this->get(GlobalSetResource::getUrl('edit', ['record' => $set]))
        ->assertSuccessful();
});

it('prevents editor from accessing global sets pages', function () {
    $this->seed(PermissionSeeder::class);
    $editor = User::factory()->create();
    $editor->assignRole(UserRole::Editor->value);
    $this->actingAs($editor);

    $set = GlobalSet::factory()->create();

    $this->get(GlobalSetResource::getUrl('index'))
        ->assertForbidden();

    $this->get(GlobalSetResource::getUrl('create'))
        ->assertForbidden();

    $this->get(GlobalSetResource::getUrl('edit', ['record' => $set]))
        ->assertForbidden();
});

it('prevents contributor from accessing global sets pages', function () {
    $this->seed(PermissionSeeder::class);
    $contributor = User::factory()->create();
    $contributor->assignRole(UserRole::Contributor->value);
    $this->actingAs($contributor);

    $set = GlobalSet::factory()->create();

    $this->get(GlobalSetResource::getUrl('index'))
        ->assertForbidden();

    $this->get(GlobalSetResource::getUrl('create'))
        ->assertForbidden();

    $this->get(GlobalSetResource::getUrl('edit', ['record' => $set]))
        ->assertForbidden();
});

it('prevents editor from accessing theme settings page', function () {
    $this->seed(PermissionSeeder::class);
    $editor = User::factory()->create();
    $editor->assignRole(UserRole::Editor->value);
    $this->actingAs($editor);

    $this->get(ThemeSettings::getUrl())
        ->assertForbidden();
});

it('prevents contributor from accessing theme settings page', function () {
    $this->seed(PermissionSeeder::class);
    $contributor = User::factory()->create();
    $contributor->assignRole(UserRole::Contributor->value);
    $this->actingAs($contributor);

    $this->get(ThemeSettings::getUrl())
        ->assertForbidden();
});

it('can create a global set with key-value data', function () {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    Livewire::test(CreateGlobalSet::class)
        ->fillForm([
            'title' => 'Test Set',
            'handle' => 'test_set',
            'data' => [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $set = GlobalSet::where('handle', 'test_set')->first();

    expect($set)->not->toBeNull()
        ->and($set->data)->toBe(['key1' => 'value1', 'key2' => 'value2']);
});

it('saves edited key-value data back as flat JSON', function () {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $set = GlobalSet::factory()->create([
        'handle' => 'editable',
        'data' => ['old' => 'value'],
    ]);

    Livewire::test(EditGlobalSet::class, [
        'record' => $set->getRouteKey(),
    ])
        ->fillForm([
            'data' => [
                'new_key' => 'new_value',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($set->fresh()->data)->toBe(['new_key' => 'new_value']);
});
