<?php

declare(strict_types=1);

use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Page;

it('keeps the approved entry route public while an updated slug is in review', function () {
    $collection = Collection::factory()->create([
        'route' => '/blog/{slug}',
        'slugs' => true,
    ]);

    $entry = Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $collection->blueprint_id,
        'title' => 'Původní článek',
        'slug' => 'puvodni-clanek',
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $entry->update([
        'title' => 'Rozpracovaný článek',
        'slug' => 'rozpracovany-clanek',
        'status' => EntryStatus::InReview,
    ]);

    $this->get('/blog/puvodni-clanek')
        ->assertSuccessful()
        ->assertSee('Původní článek')
        ->assertDontSee('Rozpracovaný článek');

    $this->get('/blog/rozpracovany-clanek')
        ->assertSuccessful()
        ->assertSee('Původní článek')
        ->assertDontSee('Rozpracovaný článek');
});

it('keeps the approved page route public while an updated slug is in review', function () {
    $page = Page::factory()->create([
        'title' => 'Původní stránka',
        'slug' => 'puvodni-stranka',
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $page->update([
        'title' => 'Rozpracovaná stránka',
        'slug' => 'rozpracovana-stranka',
        'status' => EntryStatus::InReview,
    ]);

    $this->get('/puvodni-stranka')
        ->assertSuccessful()
        ->assertSee('Původní stránka')
        ->assertDontSee('Rozpracovaná stránka');

    $this->get('/rozpracovana-stranka')
        ->assertSuccessful()
        ->assertSee('Původní stránka')
        ->assertDontSee('Rozpracovaná stránka');
});
