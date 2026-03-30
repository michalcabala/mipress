<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Filament\Resources\PageResource\Pages\CreatePage;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($this->admin);

    $this->blueprint = Blueprint::factory()->create([
        'handle' => 'page',
        'fields' => [],
    ]);

    $this->pagesCollection = Collection::factory()->create([
        'name' => 'Stránky',
        'handle' => 'pages',
        'blueprint_id' => $this->blueprint->id,
        'route' => '/{slug}',
        'slugs' => true,
        'dated' => false,
        'hierarchical' => true,
    ]);
});

it('renders the dedicated page resource index', function () {
    $this->get(PageResource::getUrl('index'))
        ->assertSuccessful();
});

it('creates page in fixed pages collection', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Nová stránka',
            'slug' => 'nova-stranka',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $entry = Entry::query()->where('slug', 'nova-stranka')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->collection_id)->toBe($this->pagesCollection->id)
        ->and($entry->blueprint_id)->toBe($this->blueprint->id);
});
