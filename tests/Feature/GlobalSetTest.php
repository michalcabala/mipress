<?php

declare(strict_types=1);

use App\Models\User;
use Filament\FilamentManager;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Clusters\SeoCluster;
use MiPress\Core\Filament\Clusters\WebCluster;
use MiPress\Core\Filament\Pages\BotlyPage;
use MiPress\Core\Filament\Pages\EditSettings;
use MiPress\Core\Filament\Pages\GlobalSeoSettings;
use MiPress\Core\Filament\Pages\SitemapSettings;
use MiPress\Core\Filament\Pages\ThemeSettings;
use MiPress\Core\Filament\Resources\GlobalSetResource;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Setting;
use MiPress\Core\Services\SettingsManager;

it('reads and writes values on setting data via get and set', function () {
    $setting = Setting::factory()->create([
        'handle' => 'contact',
        'data' => ['email' => 'hello@example.com'],
    ]);

    expect($setting->get('email'))->toBe('hello@example.com')
        ->and($setting->get('missing', 'fallback'))->toBe('fallback');

    $setting->set('phone', '+420123456789')->save();

    expect($setting->fresh()->get('phone'))->toBe('+420123456789');
});

it('validates handle format on save', function () {
    $setting = Setting::factory()->make([
        'handle' => 'Invalid-Handle',
    ]);

    expect(fn () => $setting->save())
        ->toThrow(ValidationException::class);
});

it('prevents handle changes after create', function () {
    $setting = Setting::factory()->create(['handle' => 'general']);

    expect(function () use ($setting): void {
        $setting->handle = 'changed';
        $setting->save();
    })->toThrow(ValidationException::class);
});

it('finds settings by handle using settings manager', function () {
    Setting::factory()->create(['handle' => 'social', 'name' => 'Sociální sítě']);

    $manager = app(SettingsManager::class);

    expect($manager->find('social'))
        ->not->toBeNull()
        ->and($manager->find('social')?->name)->toBe('Sociální sítě')
        ->and($manager->find('missing'))->toBeNull();
});

it('caches settings manager results per request until flush', function () {
    $setting = Setting::factory()->create(['handle' => 'contact', 'name' => 'Kontakt']);
    $manager = app(SettingsManager::class);

    expect($manager->find('contact')?->name)->toBe('Kontakt');

    Setting::query()->whereKey($setting->id)->update(['name' => 'Kontakt 2']);

    expect($manager->find('contact')?->name)->toBe('Kontakt');

    $manager->flush();

    expect($manager->find('contact')?->name)->toBe('Kontakt 2');
});

it('returns values using settings helper', function () {
    Setting::factory()->create([
        'handle' => 'contact',
        'data' => [
            'email' => 'info@mipress.test',
            'phone' => '+420111222333',
        ],
    ]);

    expect(settings('contact', 'email'))->toBe('info@mipress.test')
        ->and(settings('contact', 'missing', 'fallback'))->toBe('fallback')
        ->and(settings('contact'))->toBeArray();
});

it('renders edit settings page for existing handle', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $blueprint = Blueprint::factory()->create([
        'fields' => [
            [
                'section' => 'Kontakt',
                'fields' => [
                    ['handle' => 'email', 'label' => 'E-mail', 'type' => 'text'],
                ],
            ],
        ],
    ]);

    Setting::factory()->create([
        'handle' => 'contact',
        'name' => 'Kontakt',
        'blueprint_id' => $blueprint->id,
    ]);

    Livewire::test(EditSettings::class, ['handle' => 'contact'])
        ->assertSuccessful();
});

it('returns 404 for non existing settings handle', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $this->get(EditSettings::getUrl(['handle' => 'missing']))
        ->assertNotFound();
});

it('saves data from edit settings page', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $blueprint = Blueprint::factory()->create([
        'fields' => [
            [
                'section' => 'Kontakt',
                'fields' => [
                    ['handle' => 'email', 'label' => 'E-mail', 'type' => 'text'],
                ],
            ],
        ],
    ]);

    $setting = Setting::factory()->create([
        'handle' => 'contact',
        'name' => 'Kontakt',
        'blueprint_id' => $blueprint->id,
        'data' => [],
    ]);

    Livewire::test(EditSettings::class, ['handle' => 'contact'])
        ->set('data.email', 'new@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($setting->fresh()->get('email'))->toBe('new@example.com');
});

it('denies settings page access for editor role', function () {
    $this->seed(PermissionSeeder::class);

    $editor = User::factory()->create();
    $editor->assignRole(UserRole::Editor->value);
    $this->actingAs($editor);

    $setting = Setting::factory()->create(['handle' => 'contact']);

    expect(EditSettings::canAccess())->toBeFalse();
});

it('groups web settings into the web cluster and hides scripts from subnavigation', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    Setting::factory()->create([
        'handle' => 'general',
        'name' => 'Obecné',
        'sort_order' => 10,
    ]);

    Setting::factory()->create([
        'handle' => 'scripts',
        'name' => 'Skripty',
        'sort_order' => 20,
    ]);

    Setting::factory()->create([
        'handle' => 'site',
        'name' => 'Web',
        'sort_order' => 30,
    ]);

    $labels = collect(EditSettings::getNavigationItems())
        ->map(fn ($item) => $item->getLabel())
        ->all();

    expect(EditSettings::getCluster())->toBe(WebCluster::class)
        ->and(ThemeSettings::getCluster())->toBe(WebCluster::class)
        ->and($labels)->toContain('Obecné')
        ->and($labels)->not->toContain('Web')
        ->and($labels)->not->toContain('Skripty');

    $this->get(EditSettings::getUrl(['handle' => 'site']))
        ->assertNotFound();
});

it('keeps seo out of generic settings navigation and exposes the dedicated global seo page', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    Setting::factory()->create([
        'handle' => 'general',
        'name' => 'Obecné',
        'icon' => 'fal-globe',
        'sort_order' => 10,
    ]);

    Setting::factory()->create([
        'handle' => 'seo',
        'name' => 'SEO',
        'icon' => 'fal-search',
        'sort_order' => 20,
    ]);

    app(SettingsManager::class)->flush();

    $labels = collect(EditSettings::getNavigationItems())
        ->map(fn ($item) => $item->getLabel())
        ->all();

    $this->get(GlobalSeoSettings::getUrl())
        ->assertSuccessful();

    $this->get(EditSettings::getUrl(['handle' => 'seo']))
        ->assertNotFound();

    expect($labels)->not->toContain('SEO')
        ->and(GlobalSeoSettings::getCluster())->toBe(SeoCluster::class)
        ->and(BotlyPage::getCluster())->toBe(SeoCluster::class)
        ->and(SitemapSettings::getCluster())->toBe(SeoCluster::class);
});

it('orders seo cluster navigation as global seo, robots and sitemap', function () {
    $sorts = collect([
        GlobalSeoSettings::class,
        BotlyPage::class,
        SitemapSettings::class,
    ])->mapWithKeys(fn (string $page): array => [
        $page => (new ReflectionClass($page))->getDefaultProperties()['navigationSort'] ?? null,
    ]);

    expect($sorts->all())->toBe([
        GlobalSeoSettings::class => 10,
        BotlyPage::class => 20,
        SitemapSettings::class => 30,
    ]);
});

it('uses the dedicated global seo page instead of the legacy dynamic seo settings route', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    Setting::factory()->create([
        'handle' => 'seo',
        'name' => 'SEO',
        'data' => [
            'metadata' => [
                'default_title' => 'MiPress Studio',
            ],
        ],
    ]);

    Livewire::test(GlobalSeoSettings::class)
        ->assertSuccessful();

    $this->get(GlobalSeoSettings::getUrl())
        ->assertSuccessful()
        ->assertSee('Globální SEO')
        ->assertSee('Google site verification');
});

it('does not register legacy global set resource in the admin panel', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);

    $resources = collect(app(FilamentManager::class)->getDefaultPanel()->getResources());

    expect($resources)->not->toContain(GlobalSetResource::class);

    $this->get('/'.trim((string) config('mipress.admin_path', 'mpcp'), '/').'/global-sets')
        ->assertNotFound();
});
