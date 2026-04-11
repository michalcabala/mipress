<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\TaxonomyResource;
use MiPress\Core\Filament\Resources\TaxonomyResource\Pages\CreateTaxonomy;
use MiPress\Core\Filament\Resources\TaxonomyResource\Pages\ListTaxonomies;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Taxonomy;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($this->admin);

    $this->blueprint = Blueprint::factory()->create([
        'handle' => 'term',
        'fields' => [],
    ]);

    $this->collection = Collection::factory()->create([
        'name' => 'Blog',
        'handle' => 'blog',
        'blueprint_id' => $this->blueprint->id,
    ]);
});

it('renders the taxonomy index', function () {
    $this->get(TaxonomyResource::getUrl('index'))
        ->assertSuccessful();
});

it('auto-fills the handle from the title while preserving a custom handle', function () {
    Livewire::test(CreateTaxonomy::class)
        ->fillForm([
            'title' => 'Kategorie clanku',
        ])
        ->assertFormSet([
            'handle' => 'kategorie-clanku',
        ])
        ->fillForm([
            'handle' => 'vlastni-handle',
            'title' => 'Upravena kategorie',
        ])
        ->assertFormSet([
            'handle' => 'vlastni-handle',
        ]);
});

it('shows the improved taxonomy table columns', function () {
    Taxonomy::query()->create([
        'title' => 'Kategorie',
        'handle' => 'kategorie',
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
    ]);

    Livewire::test(ListTaxonomies::class)
        ->assertTableColumnExists('handle')
        ->assertTableColumnExists('collection.name');
});
