<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Validation\ValidationException;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Page;
use MiPress\Core\Services\HierarchyParentResolver;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($admin);

    $this->blueprint = Blueprint::factory()->create([
        'handle' => 'page',
        'fields' => [],
    ]);

    $this->collection = Collection::factory()->create([
        'name' => 'Blog',
        'handle' => 'blog',
        'blueprint_id' => $this->blueprint->id,
        'hierarchical' => true,
    ]);

    $this->resolver = app(HierarchyParentResolver::class);
});

it('resolves a valid page parent on create', function () {
    $parent = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    expect($this->resolver->resolvePageParentForCreate($parent->id))
        ->toBe($parent->id);
});

it('prevents selecting a descendant page as the parent during edit', function () {
    $parent = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $child = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'parent_id' => $parent->id,
    ]);

    expect(fn () => $this->resolver->resolvePageParentForEdit($parent, $child->id))
        ->toThrow(ValidationException::class);
});

it('resolves a valid entry parent inside the same collection', function () {
    $parent = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
    ]);

    expect($this->resolver->resolveEntryParentForCreate($this->collection, $parent->id))
        ->toBe($parent->id);
});

it('prevents selecting a descendant entry as the parent during edit', function () {
    $parent = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
    ]);

    $child = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
        'parent_id' => $parent->id,
    ]);

    expect(fn () => $this->resolver->resolveEntryParentForEdit($parent, $child->id))
        ->toThrow(ValidationException::class);
});
