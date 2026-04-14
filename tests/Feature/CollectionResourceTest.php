<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\CollectionResource;
use MiPress\Core\Filament\Resources\CollectionResource\Pages\CreateCollection;
use MiPress\Core\Filament\Resources\CollectionResource\Pages\EditCollection;
use MiPress\Core\Filament\Resources\CollectionResource\Pages\ListCollections;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($this->admin);
});

describe('list page', function () {
    it('can render', function () {
        $this->get(CollectionResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list collections', function () {
        $collections = Collection::factory()->count(3)->create();

        Livewire::test(ListCollections::class)
            ->assertCanSeeTableRecords($collections);
    });

    it('can search by name', function () {
        $target = Collection::factory()->create(['name' => 'Stránky']);
        $other = Collection::factory()->create(['name' => 'Články']);

        Livewire::test(ListCollections::class)
            ->searchTable('Stránky')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$other]);
    });
});

describe('create page', function () {
    it('can render', function () {
        $this->get(CollectionResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a collection', function () {
        $blueprint = Blueprint::factory()->create();

        Livewire::test(CreateCollection::class)
            ->fillForm([
                'name' => 'Produkty',
                'handle' => 'products',
                'blueprint_id' => $blueprint->id,
                'icon' => 'fal-box',
                'dated' => false,
                'slugs' => true,
                'hierarchical' => true,
                'route' => '/products/{slug}',
                'sort_direction' => 'asc',
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $collection = Collection::where('handle', 'products')->first();

        expect($collection)->not->toBeNull()
            ->and($collection->name)->toBe('Produkty')
            ->and($collection->blueprint_id)->toBe($blueprint->id)
            ->and($collection->route)->toBe('/products/{slug}')
            ->and($collection->dated)->toBeFalse()
            ->and($collection->slugs)->toBeTrue()
            ->and($collection->hierarchical)->toBeTrue();
    });

    it('auto-fills the handle from the name while keeping custom handles intact', function () {
        Livewire::test(CreateCollection::class)
            ->fillForm([
                'name' => 'Tiskové zprávy',
            ])
            ->assertFormSet([
                'handle' => 'tiskove-zpravy',
            ])
            ->fillForm([
                'handle' => 'custom-handle',
                'name' => 'Nový název',
            ])
            ->assertFormSet([
                'handle' => 'custom-handle',
            ]);
    });

    it('validates required fields', function () {
        Livewire::test(CreateCollection::class)
            ->fillForm([
                'name' => null,
                'handle' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'handle' => 'required',
            ]);
    });

    it('validates unique handle', function () {
        Collection::factory()->create(['handle' => 'existing']);

        Livewire::test(CreateCollection::class)
            ->fillForm([
                'name' => 'Duplicate',
                'handle' => 'existing',
            ])
            ->call('create')
            ->assertHasFormErrors(['handle' => 'unique']);
    });

    it('rejects the reserved pages handle for new collections', function () {
        Livewire::test(CreateCollection::class)
            ->fillForm([
                'name' => 'Stránky',
                'handle' => 'pages',
            ])
            ->call('create')
            ->assertHasFormErrors(['handle']);
    });

    it('rejects the reserved root slug route for new collections', function () {
        Livewire::test(CreateCollection::class)
            ->fillForm([
                'name' => 'Landing pages',
                'handle' => 'landing-pages',
                'slugs' => true,
                'route' => '/{slug}',
            ])
            ->call('create')
            ->assertHasFormErrors(['route']);
    });
});

describe('edit page', function () {
    it('can render', function () {
        $collection = Collection::factory()->create();

        $this->get(CollectionResource::getUrl('edit', ['record' => $collection]))
            ->assertSuccessful();
    });

    it('can fill form with existing data', function () {
        $collection = Collection::factory()->create([
            'name' => 'Stránky',
            'handle' => 'pages',
            'dated' => true,
            'slugs' => true,
            'hierarchical' => true,
            'route' => '/{slug}',
        ]);

        Livewire::test(EditCollection::class, ['record' => $collection->getRouteKey()])
            ->assertFormSet([
                'name' => 'Stránky',
                'handle' => 'pages',
                'dated' => true,
                'slugs' => true,
                'hierarchical' => true,
                'route' => '/{slug}',
            ]);
    });

    it('can update a collection', function () {
        $collection = Collection::factory()->create([
            'name' => 'Original',
        ]);

        Livewire::test(EditCollection::class, ['record' => $collection->getRouteKey()])
            ->fillForm([
                'name' => 'Upravená sekce',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($collection->fresh()->name)->toBe('Upravená sekce');
    });

    it('allows saving a legacy pages collection during the transition', function () {
        $collection = Collection::factory()->create([
            'name' => 'Stránky',
            'handle' => 'pages',
            'route' => '/{slug}',
            'slugs' => true,
            'hierarchical' => true,
        ]);

        Livewire::test(EditCollection::class, ['record' => $collection->getRouteKey()])
            ->fillForm([
                'name' => 'Legacy stránky',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($collection->fresh()->name)->toBe('Legacy stránky');
    });

    it('can toggle dated flag', function () {
        $collection = Collection::factory()->create(['dated' => false]);

        Livewire::test(EditCollection::class, ['record' => $collection->getRouteKey()])
            ->fillForm([
                'dated' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($collection->fresh()->dated)->toBeTrue();
    });
});

describe('delete', function () {
    it('can delete a collection', function () {
        $collection = Collection::factory()->create();

        Livewire::test(EditCollection::class, ['record' => $collection->getRouteKey()])
            ->callAction('delete');

        expect(Collection::find($collection->id))->toBeNull();
    });
});

describe('authorization', function () {
    it('denies access to contributor role', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $this->get(CollectionResource::getUrl('index'))
            ->assertForbidden();
    });
});
