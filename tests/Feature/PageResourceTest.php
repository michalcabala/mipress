<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Filament\Resources\PageResource\Pages\CreatePage;
use MiPress\Core\Filament\Resources\PageResource\Pages\ListPages;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Page;
use MiPress\Core\Models\Setting;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($this->admin);

    $this->blueprint = Blueprint::factory()->create([
        'handle' => 'page',
        'fields' => [],
    ]);
});

it('renders the dedicated page resource index', function () {
    $this->get(PageResource::getUrl('index'))
        ->assertSuccessful();
});

it('creates a standalone page in the pages table', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Nová stránka',
            'slug' => 'nova-stranka',
        ])
        ->call('createAsDraft')
        ->assertHasNoFormErrors();

    $page = Page::query()->where('slug', 'nova-stranka')->first();

    expect($page)->not->toBeNull()
        ->and($page->title)->toBe('Nová stránka')
        ->and($page->blueprint_id)->toBe($this->blueprint->id);
});

it('stores homepage selection in general settings and clears legacy site settings', function () {
    $page = Page::factory()->create([
        'title' => 'Domovská stránka',
        'slug' => 'domovska-stranka',
        'status' => EntryStatus::Published,
        'published_at' => now(),
        'blueprint_id' => $this->blueprint->id,
    ]);

    Setting::putValue('site.homepage_page_id', '999');
    Setting::putValue('site.homepage_entry_id', '555');

    Livewire::test(ListPages::class)
        ->callAction(TestAction::make('toggleHomepage')->table($page));

    expect(Setting::getValue('general.homepage_page_id'))->toBe((string) $page->getKey())
        ->and(Setting::getValue('site.homepage_page_id'))->toBeNull()
        ->and(Setting::getValue('site.homepage_entry_id'))->toBeNull();
});
