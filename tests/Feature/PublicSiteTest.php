<?php

declare(strict_types=1);

use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Page;
use MiPress\Core\Models\Setting;

test('the homepage falls back to the default theme landing page when no homepage entry is configured', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Prezentační web pro moderní obsahové projekty.');
});

test('the homepage renders the configured published entry', function () {
    $page = Page::factory()->create([
        'title' => 'Domovská stránka',
        'slug' => 'domovska-stranka',
        'status' => EntryStatus::Published,
        'published_at' => now(),
    ]);

    Setting::putValue('site.homepage_page_id', (string) $page->getKey());

    $this->get('/')
        ->assertOk()
        ->assertSee('Domovská stránka');
});

test('theme assets are served without requiring a published public symlink', function () {
    $this->get('/theme-files/default/assets/css/theme.css')
        ->assertOk()
        ->assertHeader('content-type', 'text/css; charset=UTF-8');
});

test('theme asset links are rendered as relative urls', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="/theme-files/default/assets/css/theme.css"', false)
        ->assertSee('src="/theme-files/default/assets/js/theme.js"', false);
});

test('public collection routes support multiple placeholders and resolve the slug parameter', function () {
    $collection = Collection::factory()->create([
        'route' => '/blog/{year}/{slug}',
        'slugs' => true,
    ]);

    Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $collection->blueprint_id,
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'status' => EntryStatus::Published,
        'published_at' => now(),
    ]);

    $this->get('/blog/2026/hello-world')
        ->assertOk()
        ->assertSee('Hello World');
});

test('simple page routes using a root slug are publicly reachable', function () {
    $collection = Collection::factory()->create([
        'route' => '/{slug}',
        'slugs' => true,
    ]);

    Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $collection->blueprint_id,
        'title' => 'About MiPress',
        'slug' => 'about-mipress',
        'status' => EntryStatus::Published,
        'published_at' => now(),
        'data' => [
            'perex' => 'Stránka o projektu MiPress.',
        ],
    ]);

    $this->get('/about-mipress')
        ->assertOk()
        ->assertSee('About MiPress')
        ->assertSee('Stránka o projektu MiPress.');
});

test('archive routes render published entries for public collections', function () {
    $collection = Collection::factory()->create([
        'name' => 'Journal',
        'route' => '/journal/{slug}',
        'slugs' => true,
        'dated' => true,
        'sort_direction' => 'desc',
    ]);

    Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $collection->blueprint_id,
        'title' => 'First Story',
        'slug' => 'first-story',
        'status' => EntryStatus::Published,
        'published_at' => now(),
        'data' => [
            'excerpt' => 'Preview text for the archive card.',
        ],
    ]);

    $this->get('/journal')
        ->assertOk()
        ->assertSee('Journal')
        ->assertSee('First Story')
        ->assertSee('Preview text for the archive card.');
});
