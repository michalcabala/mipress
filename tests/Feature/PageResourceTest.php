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

it('filters pages by status and exposes all status options', function () {
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

    expect($component->instance()->getTable()->getFilter('status')?->getOptions())
        ->toBe(collect(EntryStatus::cases())
            ->mapWithKeys(fn (EntryStatus $status): array => [$status->value => $status->getLabel()])
            ->all());

    $component
        ->assertTableFilterExists('status')
        ->filterTable('status', EntryStatus::Published)
        ->assertCanSeeTableRecords([$publishedPage])
        ->assertCanNotSeeTableRecords([$draftPage, $scheduledPage, $reviewPage, $rejectedPage]);
});

it('uses the title column for resource lock indicators in the pages list', function () {
    Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    Livewire::test(ListPages::class)
        ->assertTableColumnExists('title');
});

it('shows state links above the pages table and hides empty statuses', function () {
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
        ->assertSeeHtml('fi-ta-record-state-links');

    $links = collect($component->instance()->getRecordStateLinks());

    expect($links->pluck('label')->all())->toBe(['Celkem', 'Publikováno', 'Koš'])
        ->and($links->pluck('count', 'label')->all())->toBe([
            'Celkem' => 2,
            'Publikováno' => 2,
            'Koš' => 1,
        ])
        ->and($links->firstWhere('label', 'Celkem')['active'])->toBeTrue()
        ->and($links->firstWhere('label', 'Publikováno')['url'])->toBe(PageResource::getUrl('index', [
            'filters' => ['status' => ['value' => EntryStatus::Published->value]],
        ]))
        ->and($links->firstWhere('label', 'Koš')['url'])->toBe(PageResource::getUrl('index', [
            'filters' => ['trashed' => ['value' => 0]],
        ]));
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

    $component->assertTableFilterExists('trashed');

    $component
        ->assertCanSeeTableRecords([$activePage])
        ->assertCanNotSeeTableRecords([$trashedPage])
        ->filterTable('trashed', false)
        ->assertCanSeeTableRecords([$trashedPage])
        ->assertCanNotSeeTableRecords([$activePage]);
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
        ->and($table->getFilter('status', true))->not->toBeNull()
        ->and(array_map(fn (Section $section): string|\Illuminate\Contracts\Support\Htmlable|null => $section->getHeading(), $schema))->toBe(['Základní', 'Stav']);
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
