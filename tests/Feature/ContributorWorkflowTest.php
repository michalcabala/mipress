<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

/**
 * @return array{blueprint: Blueprint, collection: Collection, contributor: User}
 */
function contributorWorkflowContext(): array
{
    seed(PermissionSeeder::class);

    $blueprint = Blueprint::factory()->create([
        'handle' => 'page',
        'fields' => [],
    ]);

    $collection = Collection::factory()->create([
        'name' => 'Blog',
        'handle' => 'blog',
        'route' => '/blog/{slug}',
        'slugs' => true,
        'blueprint_id' => $blueprint->id,
    ]);

    $contributor = User::factory()->create();
    $contributor->assignRole(UserRole::Contributor->value);

    actingAs($contributor);

    return [
        'blueprint' => $blueprint,
        'collection' => $collection,
        'contributor' => $contributor,
    ];
}

it('allows contributor to edit own published entry and submit changes for review', function () {
    [
        'blueprint' => $blueprint,
        'collection' => $collection,
        'contributor' => $contributor,
    ] = contributorWorkflowContext();

    $entry = Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $blueprint->id,
        'author_id' => $contributor->id,
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $this->get(EntryResource::getUrl('edit', ['record' => $entry, 'collection' => $collection->handle]))
        ->assertSuccessful()
        ->assertSee('Odeslat změny ke schválení')
        ->assertDontSee('Aktualizovat');

    Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
        ->callAction('submitForReview');

    $entry->refresh();

    expect($entry->status)->toBe(EntryStatus::InReview);
});

it('blocks contributor from editing another authors published entry', function () {
    [
        'blueprint' => $blueprint,
        'collection' => $collection,
    ] = contributorWorkflowContext();

    $otherAuthor = User::factory()->create();

    $entry = Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $blueprint->id,
        'author_id' => $otherAuthor->id,
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $this->get(EntryResource::getUrl('edit', ['record' => $entry, 'collection' => $collection->handle]))
        ->assertForbidden();
});

it('allows contributor to edit own published page and submit changes for review', function () {
    ['contributor' => $contributor] = contributorWorkflowContext();

    $page = Page::factory()->create([
        'author_id' => $contributor->id,
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
    [
        'blueprint' => $blueprint,
        'collection' => $collection,
        'contributor' => $contributor,
    ] = contributorWorkflowContext();

    $entry = Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $blueprint->id,
        'author_id' => $contributor->id,
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

    Auth::logout();

    $this->get('/blog/schvaleny-clanek')
        ->assertSuccessful()
        ->assertSee('Schválený článek')
        ->assertDontSee('Rozpracovaná změna');
});

it('renders the last approved page version on frontend when contributor changes are in review', function () {
    ['contributor' => $contributor] = contributorWorkflowContext();

    $page = Page::factory()->create([
        'author_id' => $contributor->id,
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

    Auth::logout();

    $this->get('/schvalena-stranka')
        ->assertSuccessful()
        ->assertSee('Schválená stránka')
        ->assertDontSee('Rozpracovaná stránka');
});
