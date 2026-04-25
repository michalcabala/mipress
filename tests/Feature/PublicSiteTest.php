<?php

declare(strict_types=1);

use MiPress\Core\Enums\ContentStatus;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Page;
use MiPress\Core\Models\Setting;

test('the homepage falls back to the default theme landing page when no homepage entry is configured', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('A SaaS presentation layer for your CMS, not just another blog skin.');
});

test('the default theme uses general site settings for public branding', function () {
    Setting::putValue('general.site_name', 'Studio Atlas');
    Setting::putValue('general.site_description', 'Moderní publishing platforma pro firemní weby.');

    $this->get('/')
        ->assertOk()
        ->assertSeeText('Studio Atlas')
        ->assertSee('<title>Studio Atlas | SaaS CMS frontend</title>', false)
        ->assertSee('<meta name="description" content="Moderní publishing platforma pro firemní weby.">', false);
});

test('the homepage renders the configured published entry', function () {
    $page = Page::factory()->create([
        'title' => 'Home Page',
        'slug' => 'home-page',
        'status' => ContentStatus::Published,
        'published_at' => now(),
    ]);

    Setting::putValue('general.homepage_page_id', (string) $page->getKey());

    $this->get('/')
        ->assertOk()
        ->assertSee('Home Page');
});

test('the homepage still resolves the legacy site homepage setting during the transition', function () {
    $page = Page::factory()->create([
        'title' => 'Legacy homepage',
        'slug' => 'legacy-homepage',
        'status' => ContentStatus::Published,
        'published_at' => now(),
    ]);

    Setting::putValue('site.homepage_page_id', (string) $page->getKey());

    $this->get('/')
        ->assertOk()
        ->assertSee('Legacy homepage');
});

test('the homepage still resolves the legacy homepage entry setting during the transition', function () {
    $collection = Collection::factory()->create([
        'route' => '/blog/{slug}',
        'slugs' => true,
    ]);

    $entry = Entry::factory()->create([
        'collection_id' => $collection->id,
        'blueprint_id' => $collection->blueprint_id,
        'title' => 'Legacy homepage entry',
        'slug' => 'legacy-homepage-entry',
        'status' => ContentStatus::Published,
        'published_at' => now(),
    ]);

    Setting::putValue('site.homepage_entry_id', (string) $entry->getKey());

    $this->get('/')
        ->assertOk()
        ->assertSee('Legacy homepage entry');
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

test('default theme admin links follow the configured admin path', function () {
    config()->set('mipress.admin_path', 'backoffice');

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.url('/backoffice').'"', false)
        ->assertDontSee('href="'.url('/admin').'"', false);
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
        'status' => ContentStatus::Published,
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
        'status' => ContentStatus::Published,
        'published_at' => now(),
        'data' => [
            'perex' => 'About page for the MiPress project.',
        ],
    ]);

    $this->get('/about-mipress')
        ->assertOk()
        ->assertSee('About MiPress')
        ->assertSee('About page for the MiPress project.');
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
        'status' => ContentStatus::Published,
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
