<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
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
use MiPress\Core\Models\Taxonomy;
use MiPress\Core\Models\Term;
use MiPress\Core\Policies\EntryPolicy;

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
        'hierarchical' => true,
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

    it('uses path-based collection URL on index', function () {
        $url = EntryResource::getUrl('index', ['collection' => 'pages']);

        expect($url)->toEndWith('/mpcp/entries/pages');
    });

    it('can list entries for a collection', function () {
        $entries = Entry::factory()->count(3)->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->assertCanSeeTableRecords($entries);
    });

    it('shows a resource lock indicator column in the entries list', function () {
        Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->assertTableColumnExists('resource_lock_state');
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

    it('filters entries by status and exposes all status options', function () {
        $draftEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::Draft,
        ]);

        $publishedEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $scheduledEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::Scheduled,
            'published_at' => now()->addHour(),
        ]);

        $reviewEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::InReview,
        ]);

        $rejectedEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::Rejected,
        ]);

        $component = Livewire::test(ListEntries::class, ['collectionHandle' => 'pages']);

        expect($component->instance()->getTable()->getFilter('status')?->getOptions())
            ->toBe(collect(EntryStatus::cases())
                ->mapWithKeys(fn (EntryStatus $status): array => [$status->value => $status->getLabel()])
                ->all());

        $component
            ->assertTableFilterExists('status')
            ->filterTable('status', EntryStatus::Published)
            ->assertCanSeeTableRecords([$publishedEntry])
            ->assertCanNotSeeTableRecords([$draftEntry, $scheduledEntry, $reviewEntry, $rejectedEntry]);
    });

    it('shows state links above the table and hides empty statuses', function () {
        Entry::factory()->count(2)->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $trashedEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::Published,
        ]);

        $trashedEntry->delete();

        $component = Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->assertSeeHtml('fi-ta-record-state-links');

        $links = collect($component->instance()->getRecordStateLinks());

        expect($links->pluck('label')->all())->toBe(['Celkem', 'Publikováno', 'Koš'])
            ->and($links->pluck('count', 'label')->all())->toBe([
                'Celkem' => 2,
                'Publikováno' => 2,
                'Koš' => 1,
            ])
            ->and($links->firstWhere('label', 'Celkem')['active'])->toBeTrue()
            ->and($links->firstWhere('label', 'Publikováno')['url'])->toBe(EntryResource::getUrl('index', [
                'collection' => 'pages',
                'filters' => ['status' => ['value' => EntryStatus::Published->value]],
            ]))
            ->and($links->firstWhere('label', 'Koš')['url'])->toBe(EntryResource::getUrl('index', [
                'collection' => 'pages',
                'filters' => ['trashed' => ['value' => 0]],
            ]));
    });

    it('shows deleted entries only in trash tab and removes the trashed table filter', function () {
        $activeEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        $trashedEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        $trashedEntry->delete();

        $component = Livewire::test(ListEntries::class, ['collectionHandle' => 'pages']);

        $component->assertTableFilterExists('trashed');

        $component
            ->assertCanSeeTableRecords([$activeEntry])
            ->assertCanNotSeeTableRecords([$trashedEntry])
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$trashedEntry])
            ->assertCanNotSeeTableRecords([$activeEntry]);
    });

    it('uses slideover modals globally for filters and column visibility', function () {
        $table = Livewire::test(ListEntries::class, ['collectionHandle' => 'pages'])
            ->instance()
            ->getTable();

        $schema = $table->getFiltersFormSchema();

        expect($table->getFiltersLayout())->toBe(FiltersLayout::Modal)
            ->and($table->getFiltersTriggerAction()->isModalSlideOver())->toBeTrue()
            ->and($table->getColumnManagerLayout())->toBe(ColumnManagerLayout::Modal)
            ->and($table->getColumnManagerTriggerAction()->isModalSlideOver())->toBeTrue()
            ->and($table->getFilter('created_at', true))->toBeNull()
            ->and($table->getFilter('status', true))->not->toBeNull()
            ->and(array_map(fn (Section $section): string|\Illuminate\Contracts\Support\Htmlable|null => $section->getHeading(), $schema))->toBe(['Základní', 'Stav']);
    });

    it('shows dynamic taxonomy columns and filters for selected collection', function () {
        $taxonomy = Taxonomy::create([
            'title' => 'Kategorie',
            'handle' => 'kategorie',
            'is_hierarchical' => false,
            'collection_id' => $this->collection->id,
        ]);

        $term = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'title' => 'Novinky',
            'slug' => 'novinky',
        ]);

        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        $entry->terms()->attach($term->id);

        $component = Livewire::test(ListEntries::class, ['collection' => 'pages']);

        $component
            ->assertTableColumnExists('taxonomy_'.$taxonomy->id)
            ->assertTableFilterExists('taxonomy_'.$taxonomy->id)
            ->assertCanSeeTableRecords([$entry]);

        expect(array_map(
            fn (Section $section): string|\Illuminate\Contracts\Support\Htmlable|null => $section->getHeading(),
            $component->instance()->getTable()->getFiltersFormSchema(),
        ))->toContain('Taxonomie');
    });

    it('can filter entries by dynamic taxonomy filter', function () {
        $taxonomy = Taxonomy::create([
            'title' => 'Štítky',
            'handle' => 'stitky',
            'is_hierarchical' => false,
            'collection_id' => $this->collection->id,
        ]);

        $devTerm = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'title' => 'Vývoj',
            'slug' => 'vyvoj',
        ]);

        $designTerm = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'title' => 'Design',
            'slug' => 'design',
        ]);

        $devEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'title' => 'Vývojový článek',
        ]);

        $designEntry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'title' => 'Design článek',
        ]);

        $devEntry->terms()->attach($devTerm->id);
        $designEntry->terms()->attach($designTerm->id);

        Livewire::test(ListEntries::class, ['collection' => 'pages'])
            ->assertCanSeeTableRecords([$devEntry, $designEntry])
            ->filterTable('taxonomy_'.$taxonomy->id, [
                'term_ids_'.$taxonomy->id => [$devTerm->id],
            ])
            ->assertCanSeeTableRecords([$devEntry])
            ->assertCanNotSeeTableRecords([$designEntry]);
    });

    it('shows in-review badge in navigation for users who can publish', function () {
        $blogCollection = Collection::factory()->create([
            'name' => 'Blog',
            'handle' => 'blog-badge-test',
        ]);

        Entry::factory()->create([
            'collection_id' => $blogCollection->id,
            'status' => EntryStatus::InReview,
        ]);

        $items = EntryResource::getNavigationItems();
        $blogItem = collect($items)->first(fn ($item) => $item->getLabel() === 'Blog');

        expect($blogItem)->not->toBeNull()
            ->and($blogItem->getBadge())->toBe('1');
    });

    it('does not show in-review badge in navigation for contributor', function () {
        $blogCollection = Collection::factory()->create([
            'name' => 'Blog',
            'handle' => 'blog-badge-test',
        ]);

        Entry::factory()->create([
            'collection_id' => $blogCollection->id,
            'status' => EntryStatus::InReview,
        ]);

        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $items = EntryResource::getNavigationItems();
        $blogItem = collect($items)->first(fn ($item) => $item->getLabel() === 'Blog');

        expect($blogItem)->not->toBeNull()
            ->and($blogItem->getBadge())->toBeNull();
    });
});

// --- Create Page ---

describe('create page', function () {
    it('can render', function () {
        $this->get(EntryResource::getUrl('create', ['collection' => 'pages']))
            ->assertSuccessful();
    });

    it('shows publish and draft create actions for superadmin', function () {
        $this->get(EntryResource::getUrl('create', ['collection' => 'pages']))
            ->assertSee('Publikovat')
            ->assertSee('Uložit koncept');
    });

    it('shows review and draft create actions for contributor', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $this->get(EntryResource::getUrl('create', ['collection' => 'pages']))
            ->assertSuccessful()
            ->assertSee('Odeslat ke schválení')
            ->assertSee('Uložit koncept')
            ->assertDontSee('Publikovat');
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

    it('can cancel entry creation and return to the collection index', function () {
        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => 'Rozpracovaná položka',
            ])
            ->callAction('cancel')
            ->assertRedirect(EntryResource::getUrl('index', ['collection' => 'pages']));
    });

    it('can create a child entry in hierarchical collection', function () {
        $parent = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'title' => 'Nadřazená stránka',
            'slug' => 'nadrzena-stranka',
        ]);

        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => 'Podstránka',
                'slug' => 'podstranka',
                'parent_id' => $parent->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = Entry::query()->where('slug', 'podstranka')->first();

        expect($entry)->not->toBeNull()
            ->and($entry->parent_id)->toBe($parent->id);
    });

    it('saves selected hierarchical taxonomy term on create', function () {
        $taxonomy = Taxonomy::create([
            'title' => 'Rubriky',
            'handle' => 'rubriky-test-create',
            'is_hierarchical' => true,
            'collection_id' => $this->collection->id,
        ]);

        $parentTerm = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'title' => 'Nadřazená rubrika',
            'slug' => 'nadrzena-rubrika',
            'parent_id' => null,
        ]);

        $childTerm = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'title' => 'Podrubrika',
            'slug' => 'podrubrika',
            'parent_id' => $parentTerm->id,
        ]);

        Livewire::withQueryParams(['collection' => 'pages'])
            ->test(CreateEntry::class)
            ->fillForm([
                'title' => 'Stránka s rubrikou',
                'slug' => 'stranka-s-rubrikou',
                'taxonomy__'.$taxonomy->id => [$childTerm->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = Entry::query()->where('slug', 'stranka-s-rubrikou')->first();

        expect($entry)->not->toBeNull();
        expect($entry->terms()->pluck('terms.id')->all())->toContain($childTerm->id);
    });

    it('hides parent field for non-hierarchical collection', function () {
        Collection::factory()->create([
            'name' => 'Příspěvky',
            'handle' => 'articles',
            'blueprint_id' => $this->blueprint->id,
            'hierarchical' => false,
        ]);

        $this->get(EntryResource::getUrl('create', ['collection' => 'articles']))
            ->assertSuccessful()
            ->assertDontSee('Nadřazená položka');
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

    it('locks entry when the edit page is mounted', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->dispatch('resourceLockObserver::init');

        $lockedEntry = $entry->fresh()->load('resourceLock');

        expect($lockedEntry->resourceLock)->not->toBeNull()
            ->and((int) $lockedEntry->resourceLock->user_id)->toBe((int) $this->admin->getKey());
    });

    it('can cancel entry editing, redirect back to the list and unlock the record', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->dispatch('resourceLockObserver::init')
            ->fillForm([
                'title' => 'Rozpracovaná změna',
            ])
            ->callAction('cancel')
            ->assertRedirect(EntryResource::getUrl('index', ['collection' => 'pages']));

        expect($entry->fresh()->resourceLock)->toBeNull();
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

    it('saves selected hierarchical taxonomy term on edit', function () {
        $taxonomy = Taxonomy::create([
            'title' => 'Rubriky',
            'handle' => 'rubriky-test-edit',
            'is_hierarchical' => true,
            'collection_id' => $this->collection->id,
        ]);

        $parentTerm = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'title' => 'Nadřazená rubrika 2',
            'slug' => 'nadrzena-rubrika-2',
            'parent_id' => null,
        ]);

        $childTerm = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'title' => 'Podrubrika 2',
            'slug' => 'podrubrika-2',
            'parent_id' => $parentTerm->id,
        ]);

        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'blueprint_id' => $this->blueprint->id,
            'status' => EntryStatus::Draft,
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getKey()])
            ->fillForm([
                'taxonomy__'.$taxonomy->id => [$childTerm->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($entry->fresh()->terms()->pluck('terms.id')->all())->toContain($childTerm->id);
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
    it('sends database notification to approvers when contributor submits for review', function () {
        Notification::fake();

        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);

        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $contributor->id,
            'status' => EntryStatus::Draft,
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->callAction('submitForReview');

        Notification::assertSentTo($editor, FilamentDatabaseNotification::class);
    });

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

    it('sets scheduled status when publishing with future publish date', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Draft,
            'published_at' => now()->addHour(),
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->callAction('publishEntry');

        $entry->refresh();

        expect($entry->status)->toBe(EntryStatus::Scheduled)
            ->and($entry->published_at)->not->toBeNull();
    });

    it('publishes scheduled entry via command when time is due', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Scheduled,
            'published_at' => now()->subMinute(),
        ]);

        $this->artisan('entries:publish-scheduled')
            ->assertSuccessful();

        $entry->refresh();

        expect($entry->status)->toBe(EntryStatus::Published);
    });

    it('publishes immediately when publish date is empty', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Draft,
            'published_at' => null,
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->callAction('publishEntry');

        $entry->refresh();

        expect($entry->status)->toBe(EntryStatus::Published)
            ->and($entry->published_at)->not->toBeNull();
    });

    it('keeps backdated publish date when publishing', function () {
        $pastDate = now()->subDays(2)->startOfHour();

        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Draft,
            'published_at' => $pastDate,
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->callAction('publishEntry');

        $entry->refresh();

        expect($entry->status)->toBe(EntryStatus::Published)
            ->and($entry->published_at?->format('Y-m-d H:i:s'))->toBe($pastDate->format('Y-m-d H:i:s'));
    });

    it('clears rejection note when saved as draft', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Rejected,
            'review_note' => 'Nutné doplnit zdroje.',
        ]);

        Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
            ->callAction('saveDraft');

        $entry->refresh();

        expect($entry->status)->toBe(EntryStatus::Draft)
            ->and($entry->review_note)->toBeNull();
    });
});

// --- Preview ---

describe('preview', function () {
    it('renders valid signed preview for unpublished entry', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Draft,
            'slug' => 'draft-preview',
            'title' => 'Náhled článku',
        ]);

        $url = URL::temporarySignedRoute('preview.entry', now()->addHour(), ['entry' => $entry->id]);

        $this->get($url)
            ->assertSuccessful()
            ->assertSee('Náhled obsahu (nepublikovaná verze)');
    });

    it('returns 403 for expired preview URL', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Draft,
            'slug' => 'expired-preview',
        ]);

        $url = URL::temporarySignedRoute('preview.entry', now()->subMinute(), ['entry' => $entry->id]);

        $this->get($url)
            ->assertForbidden();
    });

    it('redirects published entry preview to live URL', function () {
        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Published,
            'slug' => 'live-article',
            'published_at' => now()->subMinute(),
        ]);

        $url = URL::temporarySignedRoute('preview.entry', now()->addHour(), ['entry' => $entry->id]);

        $this->get($url)
            ->assertRedirect('/live-article');
    });
});

// --- Header Action Matrix ---

describe('header action matrix', function () {
    it('shows expected header actions by role and status', function (
        string $role,
        EntryStatus $status,
        bool $asAuthor,
        array $expectedVisible,
        array $expectedHidden,
    ) {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $asAuthor ? $user->id : $this->admin->id,
            'status' => $status,
            'slug' => 'workflow-matrix-'.str()->random(8),
            'published_at' => $status === EntryStatus::Scheduled
                ? now()->addHour()
                : ($status === EntryStatus::Published ? now()->subMinute() : null),
        ]);

        $response = $this->get(EntryResource::getUrl('edit', ['record' => $entry]));

        $response->assertSuccessful();

        foreach ($expectedVisible as $label) {
            $response->assertSee($label);
        }

        foreach ($expectedHidden as $label) {
            $response->assertDontSee($label);
        }
    })->with([
        'contributor draft own' => [
            UserRole::Contributor->value,
            EntryStatus::Draft,
            true,
            ['Náhled', 'Odeslat ke schválení', 'Uložit koncept'],
            ['Publikovat', 'Schválit a publikovat', 'Zamítnout', 'Zrušit publikaci'],
        ],
        'editor draft own' => [
            UserRole::Editor->value,
            EntryStatus::Draft,
            true,
            ['Náhled', 'Publikovat', 'Uložit koncept'],
            ['Odeslat ke schválení', 'Schválit a publikovat', 'Zamítnout'],
        ],
        'contributor in_review own' => [
            UserRole::Contributor->value,
            EntryStatus::InReview,
            true,
            ['Náhled'],
            ['Schválit a publikovat', 'Zamítnout', 'Uložit koncept', 'Publikovat'],
        ],
        'editor in_review own' => [
            UserRole::Editor->value,
            EntryStatus::InReview,
            true,
            ['Náhled', 'Schválit a publikovat', 'Zamítnout', 'Uložit koncept'],
            ['Odeslat ke schválení', 'Upravit a znovu odeslat'],
        ],
        'editor published own' => [
            UserRole::Editor->value,
            EntryStatus::Published,
            true,
            ['Zobrazit na webu', 'Aktualizovat', 'Zrušit publikaci'],
            ['Náhled', 'Publikovat ihned', 'Zrušit plánování'],
        ],
        'editor scheduled own' => [
            UserRole::Editor->value,
            EntryStatus::Scheduled,
            true,
            ['Náhled', 'Aktualizovat', 'Zrušit plánování', 'Publikovat ihned'],
            ['Zobrazit na webu', 'Zrušit publikaci'],
        ],
        'contributor rejected own' => [
            UserRole::Contributor->value,
            EntryStatus::Rejected,
            true,
            ['Náhled', 'Upravit a znovu odeslat', 'Uložit koncept'],
            ['Publikovat', 'Schválit a publikovat', 'Zamítnout'],
        ],
        'editor rejected own' => [
            UserRole::Editor->value,
            EntryStatus::Rejected,
            true,
            ['Náhled', 'Publikovat', 'Uložit koncept'],
            ['Upravit a znovu odeslat', 'Schválit a publikovat'],
        ],
    ]);
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

    it('contributor can edit own published entry (changes go through review)', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'author_id' => $contributor->id,
            'status' => EntryStatus::Published,
        ]);

        $this->get(EntryResource::getUrl('edit', ['record' => $entry]))
            ->assertSuccessful();
    });

    it('requires publish permission for unpublishing published entries', function () {
        $user = User::factory()->create();
        $user->givePermissionTo(['entry.view', 'entry.update', 'entry.delete']);

        $entry = Entry::factory()->create([
            'collection_id' => $this->collection->id,
            'status' => EntryStatus::Published,
        ]);

        $policy = app(EntryPolicy::class);

        expect($policy->delete($user, $entry))->toBeFalse();
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
