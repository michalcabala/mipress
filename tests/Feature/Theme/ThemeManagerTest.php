<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use MiPress\Core\Models\Setting;
use MiPress\Core\Theme\ThemeManager;
use MiPress\Core\Theme\ThemeManifest;

uses(RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function createTestTheme(string $basePath, string $slug, array $override = []): void
{
    $path = $basePath.'/'.$slug;
    mkdir($path, 0777, true);
    file_put_contents($path.'/theme.json', json_encode(array_merge([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'version' => '1.0.0',
        'author' => 'Test',
    ], $override)));
}

// ─── Discovery ───────────────────────────────────────────────────────────────

describe('ThemeManager discovery', function () {
    beforeEach(function () {
        $this->tempPath = sys_get_temp_dir().'/mipress-themes-'.uniqid();
        mkdir($this->tempPath, 0777, true);
    });

    afterEach(function () {
        (new Filesystem)->deleteDirectory($this->tempPath);
    });

    it('returns empty collection when themes directory does not exist', function () {
        $manager = new ThemeManager('/nonexistent/path/themes');

        expect($manager->discover())->toHaveCount(0);
    });

    it('discovers themes with valid manifests', function () {
        createTestTheme($this->tempPath, 'alpha');
        createTestTheme($this->tempPath, 'beta');

        $manager = new ThemeManager($this->tempPath);
        $themes = $manager->discover();

        expect($themes)->toHaveCount(2)
            ->and($themes->pluck('slug')->sort()->values()->all())->toBe(['alpha', 'beta']);
    });

    it('ignores directories without theme.json', function () {
        mkdir($this->tempPath.'/no-manifest', 0777, true);
        createTestTheme($this->tempPath, 'valid');

        $manager = new ThemeManager($this->tempPath);

        expect($manager->discover())->toHaveCount(1)
            ->and($manager->discover()->first()->slug)->toBe('valid');
    });

    it('ignores themes with invalid JSON', function () {
        mkdir($this->tempPath.'/broken', 0777, true);
        file_put_contents($this->tempPath.'/broken/theme.json', 'not { valid json');
        createTestTheme($this->tempPath, 'valid');

        $manager = new ThemeManager($this->tempPath);

        expect($manager->discover())->toHaveCount(1);
    });

    it('ignores themes with missing required manifest fields', function () {
        mkdir($this->tempPath.'/incomplete', 0777, true);
        file_put_contents($this->tempPath.'/incomplete/theme.json', json_encode([
            'name' => 'Incomplete Theme',
            // missing slug and version
        ]));

        $manager = new ThemeManager($this->tempPath);

        expect($manager->discover())->toHaveCount(0);
    });

    it('ignores themes where manifest slug does not match directory name', function () {
        createTestTheme($this->tempPath, 'my-theme', ['slug' => 'different-slug']);

        $manager = new ThemeManager($this->tempPath);

        expect($manager->discover())->toHaveCount(0);
    });

    it('returns ThemeManifest DTOs with correct data', function () {
        createTestTheme($this->tempPath, 'test-theme', [
            'name' => 'Test Theme',
            'version' => '2.1.0',
            'author' => 'Author Name',
        ]);

        $theme = (new ThemeManager($this->tempPath))->discover()->first();

        expect($theme)->toBeInstanceOf(ThemeManifest::class)
            ->and($theme->name)->toBe('Test Theme')
            ->and($theme->slug)->toBe('test-theme')
            ->and($theme->version)->toBe('2.1.0')
            ->and($theme->author)->toBe('Author Name')
            ->and($theme->path)->toBe($this->tempPath.'/test-theme');
    });
});

// ─── Activation ──────────────────────────────────────────────────────────────

describe('ThemeManager activation', function () {
    beforeEach(function () {
        $this->tempPath = sys_get_temp_dir().'/mipress-themes-'.uniqid();
        mkdir($this->tempPath, 0777, true);
        Cache::forget('mipress.theme.active');
    });

    afterEach(function () {
        (new Filesystem)->deleteDirectory($this->tempPath);
        Cache::forget('mipress.theme.active');
    });

    it('returns default theme when no setting exists', function () {
        expect((new ThemeManager($this->tempPath))->getActive())->toBe('default');
    });

    it('returns active theme slug from database', function () {
        Setting::create(['key' => 'theme.active', 'value' => 'custom-theme']);

        expect((new ThemeManager($this->tempPath))->getActive())->toBe('custom-theme');
    });

    it('activates an existing theme and writes to the database', function () {
        createTestTheme($this->tempPath, 'my-theme');

        (new ThemeManager($this->tempPath))->activate('my-theme');

        expect(Setting::find('theme.active')?->value)->toBe('my-theme');
    });

    it('throws InvalidArgumentException when activating a nonexistent theme', function () {
        expect(fn () => (new ThemeManager($this->tempPath))->activate('nonexistent'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('clears the stale cache value after activation', function () {
        Cache::put('mipress.theme.active', 'old-theme', 3600);
        createTestTheme($this->tempPath, 'new-theme');

        (new ThemeManager($this->tempPath))->activate('new-theme');

        // Cache is re-populated with the new slug immediately by registerViews()->getActive()
        expect(Cache::get('mipress.theme.active'))->toBe('new-theme');
    });

    it('caches the active theme value', function () {
        Setting::create(['key' => 'theme.active', 'value' => 'cached-theme']);

        $manager = new ThemeManager($this->tempPath);
        $manager->getActive(); // prime cache
        // delete from DB — cache should still return the value
        Setting::where('key', 'theme.active')->delete();

        expect($manager->getActive())->toBe('cached-theme');
    });

    it('can check whether a theme exists', function () {
        createTestTheme($this->tempPath, 'exists');

        $manager = new ThemeManager($this->tempPath);

        expect($manager->exists('exists'))->toBeTrue()
            ->and($manager->exists('not-there'))->toBeFalse();
    });
});

// ─── View resolution ─────────────────────────────────────────────────────────

describe('ThemeManager view resolution', function () {
    beforeEach(function () {
        $this->tempPath = sys_get_temp_dir().'/mipress-themes-'.uniqid();
        mkdir($this->tempPath, 0777, true);
        Cache::forget('mipress.theme.active');
    });

    afterEach(function () {
        (new Filesystem)->deleteDirectory($this->tempPath);
        Cache::forget('mipress.theme.active');
    });

    it('registers the default theme views path in the view finder', function () {
        $defaultViewPath = $this->tempPath.'/default/views';
        mkdir($defaultViewPath, 0777, true);

        (new ThemeManager($this->tempPath))->registerViews();

        $resolvedPath = realpath($defaultViewPath);
        expect(View::getFinder()->getPaths())->toContain($resolvedPath);
    });

    it('prepends active theme path before default path', function () {
        mkdir($this->tempPath.'/default/views', 0777, true);
        mkdir($this->tempPath.'/custom/views', 0777, true);
        Setting::create(['key' => 'theme.active', 'value' => 'custom']);

        (new ThemeManager($this->tempPath))->registerViews();

        $paths = View::getFinder()->getPaths();
        $customResolved = realpath($this->tempPath.'/custom/views');
        $defaultResolved = realpath($this->tempPath.'/default/views');
        $customIndex = array_search($customResolved, $paths);
        $defaultIndex = array_search($defaultResolved, $paths);

        expect($customIndex)->toBeLessThan($defaultIndex);
    });

    it('does not add active path when it equals default', function () {
        $defaultViewPath = $this->tempPath.'/default/views';
        mkdir($defaultViewPath, 0777, true);

        (new ThemeManager($this->tempPath))->registerViews();

        $resolvedPath = realpath($defaultViewPath);
        $occurrences = array_filter(
            View::getFinder()->getPaths(),
            fn ($p) => $p === $resolvedPath
        );

        expect(count($occurrences))->toBe(1);
    });
});

// ─── theme_asset() helper ────────────────────────────────────────────────────

describe('theme_asset helper', function () {
    beforeEach(function () {
        Cache::forget('mipress.theme.active');
    });

    afterEach(function () {
        Cache::forget('mipress.theme.active');
    });

    it('returns correct asset URL for the default theme', function () {
        expect(theme_asset('css/theme.css'))
            ->toBe(asset('themes/default/css/theme.css'));
    });

    it('includes the active theme slug in the asset URL', function () {
        Setting::create(['key' => 'theme.active', 'value' => 'my-theme']);

        expect(theme_asset('css/theme.css'))
            ->toContain('themes/my-theme/css/theme.css');
    });
});
