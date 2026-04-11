<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\EntryResource;
use MiPress\Core\Filament\Resources\EntryResource\Pages\EditEntry;
use MiPress\Core\Filament\Resources\EntryResource\Pages\EntryHistory;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Filament\Resources\PageResource\Pages\EditPage;
use MiPress\Core\Filament\Resources\PageResource\Pages\PageHistory;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Page;

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
        'name' => 'Blog',
        'handle' => 'blog',
        'blueprint_id' => $this->blueprint->id,
        'route' => '/blog/{slug}',
        'slugs' => true,
    ]);
});

it('shows a richer revision table for entry history', function () {
    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
        'title' => 'Původní titulek',
        'slug' => 'puvodni-titulek',
    ]);

    $entry->update([
        'title' => 'Upravený titulek',
        'status' => EntryStatus::InReview,
    ]);

    $revision = $entry->revisions()->firstOrFail();

    Livewire::test(EntryHistory::class, ['record' => $entry->getRouteKey()])
        ->assertCanSeeTableRecords([$revision])
        ->assertTableColumnExists('status_snapshot')
        ->assertTableColumnExists('snapshot_summary')
        ->assertTableActionExists('diff', record: $revision)
        ->assertTableActionExists('restore', record: $revision)
        ->assertSee('Porovnat dvě revize');
});

it('shows a richer revision table for page history', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
        'title' => 'Původní stránka',
        'slug' => 'puvodni-stranka',
    ]);

    $page->update([
        'title' => 'Upravená stránka',
        'status' => EntryStatus::Published,
    ]);

    $revision = $page->revisions()->firstOrFail();

    Livewire::test(PageHistory::class, ['record' => $page->getRouteKey()])
        ->assertCanSeeTableRecords([$revision])
        ->assertTableColumnExists('status_snapshot')
        ->assertTableColumnExists('snapshot_summary')
        ->assertTableActionExists('diff', record: $revision)
        ->assertTableActionExists('restore', record: $revision)
        ->assertSee('Revize');
});

it('uses subnavigation instead of a duplicate history action on the entry edit page', function () {
    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
    ]);

    $this->get(EntryResource::getUrl('edit', [
        'record' => $entry,
        'collection' => $this->collection->handle,
    ]))
        ->assertSuccessful()
        ->assertSee('Revize')
        ->assertSee('Editace');

    Livewire::test(EditEntry::class, ['record' => $entry->getRouteKey()])
        ->assertActionDoesNotExist('revisionHistory');
});

it('uses subnavigation instead of a duplicate history action on the page edit page', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $this->get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertSuccessful()
        ->assertSee('Revize')
        ->assertSee('Editace');

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertActionDoesNotExist('revisionHistory');
});
