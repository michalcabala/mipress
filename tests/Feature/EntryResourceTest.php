<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\EntryResource;
use MiPress\Core\Filament\Resources\EntryResource\Pages\CreateEntry;
use MiPress\Core\Filament\Resources\EntryResource\Pages\EditEntry;
use MiPress\Core\Filament\Resources\EntryResource\Pages\ListEntries;
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

    $this->collection = Collection::factory()->create([
        'name' => 'Stránky',
        'handle' => 'pages',
        'blueprint_id' => $this->blueprint->id,
        'route' => '/{slug}',
        'slugs' => true,
        'dated' => false,
    ]);
});

// --- List Page ---

describe('list page', function () {
    it('can render', function () {
        $this->get(EntryResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can render with collection filter', function () {
        $this->get(EntryResource::getUrl('index', ['collection' => 'pages']))
            ->assertSuccessful();
    });

    it('can list entries for a collection', function () {
        $entries = Entry::factory()->count(3)->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->assertCanSeeTableRecords($entries);
    });

    it('can search entries by title', function () {
        $target = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'title' => 'Hledaná stránka',
        ]);
        $other = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'title' => 'Jiná stránka',
        ]);

        Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->searchTable('Hledaná')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$other]);
    });

    it('filters entries by collection', function () {
        $otherCollection = Collection::factory()->create(['handle' => 'articles']);

        $pagesEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'title' => 'Page Entry',
        ]);
        $articlesEntry = Entry::factory()->create([
            'collection_id' => $otherCollection->id,
            'title' => 'Article Entry',
        ]);

        Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->assertCanSeeTableRecords([$pagesEntry])
            ->assertCanNotSeeTableRecords([$articlesEntry]);
    });
});

// --- Create Page ---

describe('create page', function () {
    it('can render', function () {
        $this->get(EntryResource::getUrl('create', ['collection' => 'pages']))
            ->assertSuccessful();
    });

    it('can create an entry', function () {
        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => 'Nová stránka',
                'slug' => 'nova-stranka',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = Entry::where('slug', 'nova-stranka')->first();

        expect($entry)->not->toBeNull()
            ->and($entry->title)->toBe('Nová stránka')
            ->and($entry->collection_id)->toBe($this->collection->id)
            ->and($entry->blueprint_id)->toBe($this->blueprint->id);
    });

    it('validates required title', function () {
        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    });

    it('auto-generates unique slug when duplicate exists', function () {
        Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'slug' => 'existing-slug',
        ]);

        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => 'Duplicate',
                'slug' => 'existing-slug',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = Entry::where('title', 'Duplicate')->first();

        expect($entry)->not->toBeNull()
            ->and($entry->slug)->not->toBe('existing-slug');
    });

    it('auto-assigns collection and blueprint from URL', function () {
        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => 'Auto Assign Test',
                'slug' => 'auto-assign-test',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = Entry::where('slug', 'auto-assign-test')->first();

        expect($entry->collection_id)->toBe($this->collection->id)
            ->and($entry->blueprint_id)->toBe($this->blueprint->id);
    });

    it('creates entry with draft status by default', function () {
        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => 'Draft Test',
                'slug' => 'draft-test',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = Entry::where('slug', 'draft-test')->first();

        expect($entry->status)->toBe(EntryStatus::Draft);
    });

    it('keeps collection context during live form updates on create page', function () {
        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->assertSet('collectionHandle', 'pages')
            ->fillForm([
                'title' => 'Live Update Test',
            ])
            ->assertSet('collectionHandle', 'pages');
    });

    it('keeps blueprint fields visible after live slug generation update', function () {
        $this->blueprint->update([
            'fields' => [
                [
                    'section' => 'Obsah',
                    'fields' => [
                        [
                            'type' => 'text',
                            'handle' => 'perex',
                            'label' => 'Perex',
                        ],
                    ],
                ],
            ],
        ]);

        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->assertSee('Perex')
            ->fillForm([
                'title' => 'Perzistence blueprintu',
            ])
            ->assertSee('Perex');
    });
});

// --- Edit Page ---

describe('edit page', function () {
    it('can render', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
        ]);

        $this->get(EntryResource::getUrl('edit', ['record' => $entry]))
            ->assertSuccessful();
    });

    it('can fill form with existing entry data', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'title' => 'Existující stránka',
            'slug' => 'existujici-stranka',
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->assertFormSet([
                'title' => 'Existující stránka',
                'slug' => 'existujici-stranka',
            ]);
    });

    it('can update entry title', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'title' => 'Original',
            'slug' => 'original',
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm([
                'title' => 'Upravená stránka',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($entry->fresh()->title)->toBe('Upravená stránka');
    });
});

// --- Soft Delete ---

describe('soft delete', function () {
    it('can soft-delete an entry', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
        ]);

        $entry->delete();

        expect($entry->fresh()->trashed())->toBeTrue();
    });

    it('can restore a soft-deleted entry', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
        ]);

        $entry->delete();
        $entry->restore();

        expect($entry->fresh()->trashed())->toBeFalse();
    });
});

// --- Status Workflow ---

describe('status workflow', function () {
    it('creates entries as draft by default', function () {
        $entry = Entry::factory()->create();

        expect($entry->status)->toBe(EntryStatus::Draft);
    });

    it('can have published status with published_at date', function () {
        $entry = Entry::factory()->published()->create();

        expect($entry->status)->toBe(EntryStatus::Published)
            ->and($entry->published_at)->not->toBeNull();
    });

    it('scopes published entries correctly', function () {
        Entry::factory()->published()->count(2)->create([
            'collection_id' => $this->collection->id,
        ]);
        Entry::factory()->draft()->count(3)->create([
            'collection_id' => $this->collection->id,
        ]);

        expect(Entry::published()->count())->toBe(2);
    });

    it('scopes draft entries correctly', function () {
        Entry::factory()->published()->count(2)->create([
            'collection_id' => $this->collection->id,
        ]);
        Entry::factory()->draft()->count(3)->create([
            'collection_id' => $this->collection->id,
        ]);

        expect(Entry::draft()->count())->toBe(3);
    });
});

// --- Authorization ---

describe('authorization', function () {
    it('contributor can see own and other users entries in list', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);

        $ownEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $contributor->id,
            'title' => 'Můj článek',
        ]);

        $otherEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $this->admin->id,
            'title' => 'Cizí článek',
        ]);

        $this->actingAs($contributor);

        Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->assertCanSeeTableRecords([$ownEntry, $otherEntry]);
    });

    it('contributor cannot edit another users entry', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $otherEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $this->admin->id,
            'title' => 'Cizí článek',
        ]);

        $this->get(EntryResource::getUrl('edit', ['record' => $otherEntry]))
            ->assertForbidden();
    });

    it('contributor can edit own entry', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $ownEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $contributor->id,
            'title' => 'Můj článek',
        ]);

        $this->get(EntryResource::getUrl('edit', ['record' => $ownEntry]))
            ->assertSuccessful();
    });

    it('admin sees all entries', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);

        $contributorEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $contributor->id,
        ]);

        $adminEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $this->admin->id,
        ]);

        Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->assertCanSeeTableRecords([$contributorEntry, $adminEntry]);
    });
});
