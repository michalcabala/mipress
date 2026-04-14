<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\ContentStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Pages\GlobalSeoSettings;
use MiPress\Core\Models\Page;
use MiPress\Core\Models\Setting;

describe('global seo admin', function () {
    it('renders and saves the dedicated global seo page', function () {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);
        $this->actingAs($admin);

        Livewire::test(GlobalSeoSettings::class)
            ->assertSuccessful()
            ->set('data.metadata.default_title', 'MiPress Studio')
            ->set('data.metadata.title_suffix', ' | MiPress Studio')
            ->set('data.metadata.default_description', 'Výchozí popis pro celý web.')
            ->set('data.canonical.base_url', 'https://example.test')
            ->set('data.open_graph.site_name', 'MiPress Studio')
            ->set('data.twitter.site', 'mipress')
            ->set('data.analytics.google_analytics_id', 'G-TEST1234')
            ->call('save')
            ->assertHasNoErrors();

        $seo = Setting::query()->where('handle', 'seo')->first();

        expect($seo)->not->toBeNull()
            ->and(data_get($seo?->data, 'metadata.default_title'))->toBe('MiPress Studio')
            ->and(data_get($seo?->data, 'metadata.title_suffix'))->toBe(' | MiPress Studio')
            ->and(data_get($seo?->data, 'canonical.base_url'))->toBe('https://example.test')
            ->and(data_get($seo?->data, 'twitter.site'))->toBe('mipress')
            ->and(data_get($seo?->data, 'analytics.google_analytics_id'))->toBe('G-TEST1234');
    });
});

describe('frontend seo rendering', function () {
    it('renders global seo tags on the homepage', function () {
        Setting::factory()->create([
            'handle' => 'general',
            'data' => [
                'site_name' => 'MiPress Studio',
                'site_description' => 'Agenturní weby, obsahové portály a firemní prezentace.',
            ],
        ]);

        Setting::factory()->create([
            'handle' => 'seo',
            'data' => [
                'metadata' => [
                    'homepage_title' => 'MiPress Studio | Výkonné weby pro firmy',
                    'default_description' => 'Výchozí SEO vrstva pro celý web.',
                ],
                'canonical' => [
                    'base_url' => 'https://example.test',
                    'force_https' => true,
                ],
                'verification' => [
                    'google' => 'google-verification-token',
                ],
                'analytics' => [
                    'google_analytics_id' => 'G-TEST1234',
                ],
                'structured_data' => [
                    'enabled' => true,
                    'organization_name' => 'MiPress Studio',
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>MiPress Studio | Výkonné weby pro firmy</title>', false)
            ->assertSee('<link rel="canonical" href="https://example.test/">', false)
            ->assertSee('<meta property="og:title" content="MiPress Studio | Výkonné weby pro firmy">', false)
            ->assertSee('<meta name="google-site-verification" content="google-verification-token">', false)
            ->assertSee('gtag/js?id=G-TEST1234', false)
            ->assertSee('application/ld+json', false);
    });

    it('uses generated descriptions and title suffixes on public pages', function () {
        Setting::factory()->create([
            'handle' => 'general',
            'data' => [
                'site_name' => 'MiPress Studio',
            ],
        ]);

        Setting::factory()->create([
            'handle' => 'seo',
            'data' => [
                'metadata' => [
                    'title_suffix' => ' | MiPress Studio',
                    'default_description' => 'Výchozí SEO popis.',
                ],
                'canonical' => [
                    'base_url' => 'https://example.test',
                ],
            ],
        ]);

        Page::factory()->create([
            'title' => 'Kontakt',
            'slug' => 'kontakt',
            'status' => ContentStatus::Published,
            'published_at' => now(),
            'meta_description' => null,
            'data' => [
                'excerpt' => 'Ozvěte se nám a probereme nový web, redesign i obsahovou strategii.',
            ],
        ]);

        $this->get('/kontakt')
            ->assertOk()
            ->assertSee('<title>Kontakt | MiPress Studio</title>', false)
            ->assertSee('<meta name="description" content="Ozvěte se nám a probereme nový web, redesign i obsahovou strategii.">', false)
            ->assertSee('<link rel="canonical" href="https://example.test/kontakt">', false)
            ->assertSee('<meta property="og:type" content="website">', false);
    });

    it('marks preview pages as noindex and canonicalizes them to the intended public url', function () {
        Setting::factory()->create([
            'handle' => 'general',
            'data' => [
                'site_name' => 'MiPress Studio',
            ],
        ]);

        Setting::factory()->create([
            'handle' => 'seo',
            'data' => [
                'metadata' => [
                    'title_suffix' => ' | MiPress Studio',
                ],
                'canonical' => [
                    'base_url' => 'https://example.test',
                ],
            ],
        ]);

        $page = Page::factory()->create([
            'title' => 'Návrh stránky',
            'slug' => 'navrh-stranky',
            'status' => ContentStatus::Draft,
            'meta_description' => null,
            'data' => [
                'excerpt' => 'Krátký pracovní náhled připravované stránky.',
            ],
        ]);

        $previewUrl = URL::signedRoute('preview.page', ['page' => $page]);

        $this->get($previewUrl)
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<link rel="canonical" href="https://example.test/navrh-stranky">', false)
            ->assertSee('<title>Návrh stránky | MiPress Studio</title>', false)
            ->assertSee('<meta name="description" content="Krátký pracovní náhled připravované stránky.">', false);
    });
});
