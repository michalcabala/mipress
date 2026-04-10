<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\EntryResource;
use MiPress\Core\Filament\Resources\EntryResource\Pages\EditEntry;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Filament\Resources\PageResource\Pages\EditPage;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Page;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->blueprint = Blueprint::factory()->create([
        'handle' => 'page',
        'fields' => [],
    ]);

    $this->collection = Collection::factory()->create([
        'name' => 'Blog',
        'handle' => 'blog',
        'route' => '/blog/{slug}',
        'slugs' => true,
        'blueprint_id' => $this->blueprint->id,
    ]);

    $this->contributor = User::factory()->create();
    $this->contributor->assignRole(UserRole::Contributor->value);

    $this->actingAs($this->contributor);
});

it('allows force unlock only to users with publish permission', function () {
    $editor = User::factory()->create();
    $editor->assignRole(UserRole::Editor->value);

    expect(Gate::forUser($this->contributor)->allows('forceUnlockResourceLock'))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('forceUnlockResourceLock'))->toBeTrue();
});

it('allows contributor to edit own published entry and submit changes for review', function () {
    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
        'author_id' => $this->contributor->id,
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $this->get(EntryResource::getUrl('edit', ['record' => $entry, 'collection' => $this->collection->handle]))
        ->assertSuccessful()
        ->assertSee('Odeslat změny ke schválení')
        ->assertDontSee('Aktualizovat');

    Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
        ->callAction('submitForReview');

    $entry->refresh();

    expect($entry->status)->toBe(EntryStatus::InReview);
});

it('blocks contributor from editing another authors published entry', function () {
    $otherAuthor = User::factory()->create();

    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
        'author_id' => $otherAuthor->id,
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $this->get(EntryResource::getUrl('edit', ['record' => $entry, 'collection' => $this->collection->handle]))
        ->assertForbidden();
});

it('allows contributor to edit own published page and submit changes for review', function () {
    $page = Page::factory()->create([
        'author_id' => $this->contributor->id,
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $this->get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertSuccessful()
        ->assertSee('Odeslat změny ke schválení')
        ->assertDontSee('Aktualizovat');

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('submitForReview');

    $page->refresh();

    expect($page->status)->toBe(EntryStatus::InReview);
});

it('renders the last approved entry version on frontend when contributor changes are in review', function () {
    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
        'author_id' => $this->contributor->id,
        'title' => 'Schválený článek',
        'slug' => 'schvaleny-clanek',
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
        ->fillForm([
            'title' => 'Rozpracovaná změna',
        ])
        ->callAction('submitForReview');

    $entry->refresh();

    expect($entry->revisions()->count())->toBeGreaterThan(0)
        ->and($entry->latestPublishedRevisionSnapshot())->not->toBeNull()
        ->and($entry->resolvePublicVersion()->status)->toBe(EntryStatus::Published)
        ->and($entry->resolvePublicVersion()->title)->toBe('Schválený článek');

    expect(Entry::query()->publiclyVisible()->whereKey($entry->getKey())->exists())->toBeTrue();

    auth()->logout();

    $this->get('/blog/schvaleny-clanek')
        ->assertSuccessful()
        ->assertSee('Schválený článek')
        ->assertDontSee('Rozpracovaná změna');
});

it('renders the last approved page version on frontend when contributor changes are in review', function () {
    $page = Page::factory()->create([
        'author_id' => $this->contributor->id,
        'title' => 'Schválená stránka',
        'slug' => 'schvalena-stranka',
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'title' => 'Rozpracovaná stránka',
        ])
        ->callAction('submitForReview');

    $page->refresh();

    expect($page->revisions()->count())->toBeGreaterThan(0)
        ->and($page->latestPublishedRevisionSnapshot())->not->toBeNull()
        ->and($page->resolvePublicVersion()->status)->toBe(EntryStatus::Published)
        ->and($page->resolvePublicVersion()->title)->toBe('Schválená stránka');

    expect(Page::query()->publiclyVisible()->whereKey($page->getKey())->exists())->toBeTrue();

    auth()->logout();

    $this->get('/schvalena-stranka')
        ->assertSuccessful()
        ->assertSee('Schválená stránka')
        ->assertDontSee('Rozpracovaná stránka');
});
