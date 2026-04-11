<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Schemas\Components\Section;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Filament\Resources\PageResource\Pages\CreatePage;
use MiPress\Core\Filament\Resources\PageResource\Pages\EditPage;
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

it('auto-switches page status to scheduled when a future publish date is selected on create', function () {
    $futurePublishAt = now()->addDay()->startOfHour();

    Livewire::test(CreatePage::class)
        ->fillForm([
            'published_at' => $futurePublishAt->format('Y-m-d H:i:s'),
        ])
        ->assertFormSet([
            'status' => EntryStatus::Scheduled->value,
        ]);
});

it('auto-fills the slug from the title while preserving a custom slug', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Úvodní stránka',
        ])
        ->assertFormSet([
            'slug' => 'uvodni-stranka',
        ])
        ->fillForm([
            'slug' => 'custom-slug',
            'title' => 'Nový titulek',
        ])
        ->assertFormSet([
            'slug' => 'custom-slug',
        ]);
});

it('can cancel page creation and return to the pages index', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Rozpracovaná stránka',
        ])
        ->callAction('cancel')
        ->assertRedirect(PageResource::getUrl('index'));
});

it('locks page when the edit page is mounted', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->dispatch('resourceLockObserver::init');

    $lockedPage = $page->fresh()->load('resourceLock');

    expect($lockedPage->resourceLock)->not->toBeNull()
        ->and((int) $lockedPage->resourceLock->user_id)->toBe((int) $this->admin->getKey());
});

it('renews page lock without crashing on polling event', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $component = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->dispatch('resourceLockObserver::init')
        ->dispatch('resourceLockObserver::renewLock');

    $lockedPage = $page->fresh()->load('resourceLock');

    expect($lockedPage->resourceLock)->not->toBeNull()
        ->and((int) $lockedPage->resourceLock->user_id)->toBe((int) $this->admin->getKey())
        ->and($component->instance()->isReadOnly)->toBeFalse();
});

it('releases page lock when the edit page unload event is fired', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->dispatch('resourceLockObserver::init')
        ->fillForm([
            'title' => 'Rozpracovaná změna',
        ])
        ->dispatch('resourceLockObserver::unload');

    expect($page->fresh()->resourceLock)->toBeNull();
});

it('switches scheduled page status to published when the publish date is moved into the past', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Scheduled,
        'published_at' => now()->addHour(),
        'scheduled_at' => now()->addHour(),
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])
        ->assertFormSet([
            'status' => EntryStatus::Published->value,
        ]);
});

it('publishes scheduled page immediately when the status is switched to published in the form', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Scheduled,
        'published_at' => now()->addHour(),
        'scheduled_at' => now()->addHour(),
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'status' => EntryStatus::Published->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh();

    expect($page->status)->toBe(EntryStatus::Published)
        ->and($page->scheduled_at)->toBeNull()
        ->and($page->published_at)->not->toBeNull()
        ->and($page->published_at?->isFuture())->toBeFalse();
});

it('shows validation feedback and closes the publish modal when required fields are missing', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
    ]);

    $component = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'title' => null,
        ])
        ->callAction('publishPage')
        ->assertHasFormErrors(['title' => 'required'])
        ->assertNotified();

    expect($page->fresh()->status)->toBe(EntryStatus::Draft)
        ->and($component->instance()->mountedActions)->toBe([]);
});

it('releases the page lock and redirects after publishing immediately', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
        'published_at' => null,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->dispatch('resourceLockObserver::init')
        ->callAction('publishPage')
        ->assertRedirect(PageResource::getUrl('index'));

    $page->refresh();

    expect($page->status)->toBe(EntryStatus::Published)
        ->and($page->published_at)->not->toBeNull()
        ->and($page->resourceLock)->toBeNull();
});

it('releases the page lock and redirects after scheduling publication', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
        'published_at' => now()->addHour(),
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->dispatch('resourceLockObserver::init')
        ->callAction('publishPage')
        ->assertRedirect(PageResource::getUrl('index'));

    $page->refresh();

    expect($page->status)->toBe(EntryStatus::Scheduled)
        ->and($page->published_at)->not->toBeNull()
        ->and($page->resourceLock)->toBeNull();
});

it('returns page in review back to draft', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::InReview,
        'review_note' => 'Doplnit titulek.',
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('returnToDraft');

    $page->refresh();

    expect($page->status)->toBe(EntryStatus::Draft)
        ->and($page->review_note)->toBeNull();
});

it('clears rejection note when page is saved as draft', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Rejected,
        'review_note' => 'Upravit SEO metadata.',
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('saveDraft');

    $page->refresh();

    expect($page->status)->toBe(EntryStatus::Draft)
        ->and($page->review_note)->toBeNull();
});

it('loads pages in the default tab and exposes deferred badges only for current states', function () {
    $draftPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
    ]);

    $publishedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $scheduledPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Scheduled,
        'published_at' => now()->addHour(),
    ]);

    $reviewPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::InReview,
    ]);

    $rejectedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Rejected,
    ]);

    $component = Livewire::test(ListPages::class);

    $component->assertCanSeeTableRecords([$draftPage, $publishedPage, $scheduledPage, $reviewPage, $rejectedPage]);

    $tabs = $component->instance()->getCachedTabs();

    expect(array_keys($tabs))->toBe(['', 'draft', 'in_review', 'published', 'scheduled', 'rejected'])
        ->and($tabs['']->getBadge())->toBe(5)
        ->and($tabs['']->getIcon())->toBe('far-layer-group')
        ->and($tabs['']->isBadgeDeferred())->toBeTrue()
        ->and($tabs[EntryStatus::Published->value]->getBadge())->toBe(1)
        ->and($tabs[EntryStatus::Published->value]->getIcon())->toBe(EntryStatus::Published->getIcon())
        ->and($tabs[EntryStatus::Published->value]->getBadgeColor())->toBe('success')
        ->and($tabs[EntryStatus::Published->value]->isBadgeDeferred())->toBeTrue();

    $component
        ->set('activeTab', EntryStatus::Published->value)
        ->assertCanSeeTableRecords([$publishedPage])
        ->assertCanNotSeeTableRecords([$draftPage, $scheduledPage, $reviewPage, $rejectedPage]);
});

it('uses the title column for resource lock indicators in the pages list', function () {
    Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    Livewire::test(ListPages::class)
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('slug');
});

it('renders state tabs instead of the legacy record state links row', function () {
    Page::factory()->count(2)->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $trashedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Published,
    ]);

    $trashedPage->delete();

    $component = Livewire::test(ListPages::class)
        ->assertDontSeeHtml('fi-ta-record-state-links');

    $tabs = $component->instance()->getCachedTabs();

    expect(array_keys($tabs))->toBe(['', 'published', 'trashed'])
        ->and($tabs['']->getBadge())->toBe(2)
        ->and($tabs[EntryStatus::Published->value]->getBadge())->toBe(2)
        ->and($tabs['trashed']->getIcon())->toBe('far-trash-can')
        ->and($tabs['trashed']->getBadge())->toBe(1);
});

it('shows deleted pages only in trash tab and removes the trashed table filter', function () {
    $activePage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $trashedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $trashedPage->delete();

    $component = Livewire::test(ListPages::class);

    expect($component->instance()->getTable()->getFilter('trashed', true))->toBeNull();

    $component
        ->set('activeTab', 'trashed')
        ->assertCanSeeTableRecords([$trashedPage])
        ->assertCanNotSeeTableRecords([$activePage])
        ->set('activeTab', null)
        ->assertCanSeeTableRecords([$activePage])
        ->assertCanNotSeeTableRecords([$trashedPage]);
});

it('falls back to the all tab on first load when the requested tab is no longer available', function () {
    $draftPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
    ]);

    $component = Livewire::withQueryParams(['tab' => 'trashed'])
        ->test(ListPages::class);

    $component->assertCanSeeTableRecords([$draftPage]);
});

it('uses slideover modals globally for page filters and column visibility', function () {
    $table = Livewire::test(ListPages::class)
        ->instance()
        ->getTable();

    $schema = $table->getFiltersFormSchema();

    expect($table->getFiltersLayout())->toBe(FiltersLayout::Modal)
        ->and($table->getFiltersTriggerAction()->isModalSlideOver())->toBeTrue()
        ->and($table->getColumnManagerLayout())->toBe(ColumnManagerLayout::Modal)
        ->and($table->getColumnManagerTriggerAction()->isModalSlideOver())->toBeTrue()
        ->and($table->getFilter('created_at', true))->toBeNull()
        ->and($table->getFilter('status', true))->toBeNull()
        ->and($table->getFilter('trashed', true))->toBeNull()
        ->and(array_map(fn (Section $section): string|\Illuminate\Contracts\Support\Htmlable|null => $section->getHeading(), $schema))->toBe(['Základní']);
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
