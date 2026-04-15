<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Schemas\Components\Section;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\ContentStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Filament\Resources\PageResource\Pages\CreatePage;
use MiPress\Core\Filament\Resources\PageResource\Pages\EditPage;
use MiPress\Core\Filament\Resources\PageResource\Pages\ListPages;
use MiPress\Core\Filament\Resources\PageResource\Widgets\PagePublicationStatusOverview;
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
            'status' => ContentStatus::Scheduled->value,
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

it('switches scheduled page status to published when the publish date is moved into the past', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Scheduled,
        'published_at' => now()->addHour(),
        'scheduled_at' => now()->addHour(),
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])
        ->assertFormSet([
            'status' => ContentStatus::Published->value,
        ]);
});

it('publishes scheduled page immediately when the status is switched to published in the form', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Scheduled,
        'published_at' => now()->addHour(),
        'scheduled_at' => now()->addHour(),
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'status' => ContentStatus::Published->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(PageResource::getUrl('edit', ['record' => $page]));

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Published)
        ->and($page->scheduled_at)->toBeNull()
        ->and($page->published_at)->not->toBeNull()
        ->and($page->published_at?->isFuture())->toBeFalse();
});

it('shows validation feedback when required fields are missing on publish save', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Draft,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'title' => null,
            'status' => ContentStatus::Published->value,
        ])
        ->call('save')
        ->assertHasFormErrors(['title' => 'required']);

    expect($page->fresh()->status)->toBe(ContentStatus::Draft);
});

it('redirects after publishing immediately', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Draft,
        'published_at' => null,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'status' => ContentStatus::Published->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(PageResource::getUrl('edit', ['record' => $page]));

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Published)
        ->and($page->published_at)->not->toBeNull();
});

it('renders valid signed preview for unpublished page', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Draft,
        'slug' => 'draft-page-preview',
        'title' => 'Náhled stránky',
    ]);

    $url = URL::temporarySignedRoute('preview.page', now()->addHour(), ['page' => $page->id]);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee('Náhled stránky (nepublikovaná verze)');
});

it('redirects published page preview to live URL', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Published,
        'slug' => 'live-page-preview',
        'published_at' => now()->subMinute(),
    ]);

    $url = URL::temporarySignedRoute('preview.page', now()->addHour(), ['page' => $page->id]);

    $this->get($url)
        ->assertRedirect('/live-page-preview');
});

it('can render seo edit page for page resource', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $this->get(PageResource::getUrl('seo', ['record' => $page]))
        ->assertSuccessful()
        ->assertSee('SEO titulek')
        ->assertSee('SEO popis');
});

it('redirects after scheduling publication', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Draft,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'published_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'status' => ContentStatus::Scheduled->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(PageResource::getUrl('edit', ['record' => $page]));

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Scheduled)
        ->and($page->published_at)->not->toBeNull();
});

it('returns page in review back to draft', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::InReview,
        'review_note' => 'Doplnit titulek.',
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'status' => ContentStatus::Draft->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Draft)
        ->and($page->review_note)->toBeNull();
});

it('clears rejection note when page is saved as draft', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Rejected,
        'review_note' => 'Upravit SEO metadata.',
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'status' => ContentStatus::Draft->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Draft)
        ->and($page->review_note)->toBeNull();
});

it('loads pages by default and can filter them by status', function () {
    $draftPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Draft,
    ]);

    $publishedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $scheduledPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Scheduled,
        'published_at' => now()->addHour(),
    ]);

    $reviewPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::InReview,
    ]);

    $rejectedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Rejected,
    ]);

    Livewire::test(ListPages::class)
        ->assertCanSeeTableRecords([$draftPage, $publishedPage, $scheduledPage, $reviewPage, $rejectedPage])
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('trashed')
        ->filterTable('status', ContentStatus::Published)
        ->assertCanSeeTableRecords([$publishedPage])
        ->assertCanNotSeeTableRecords([$draftPage, $scheduledPage, $reviewPage, $rejectedPage]);
});

it('shows only author names in the multi author filter indicator', function () {
    $firstAuthor = User::factory()->create([
        'name' => 'Petr Novak',
    ]);

    $secondAuthor = User::factory()->create([
        'name' => 'Jana Svobodova',
    ]);

    $firstAuthoredPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'author_id' => $firstAuthor->getKey(),
    ]);

    $secondAuthoredPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'author_id' => $secondAuthor->getKey(),
    ]);

    $otherPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'author_id' => $this->admin->getKey(),
    ]);

    $component = Livewire::test(ListPages::class)
        ->assertTableFilterExists('author_id', fn ($filter): bool => $filter->isMultiple())
        ->filterTable('author_id', [$firstAuthor->getKey(), $secondAuthor->getKey()])
        ->assertCanSeeTableRecords([$firstAuthoredPage, $secondAuthoredPage])
        ->assertCanNotSeeTableRecords([$otherPage]);

    $indicators = $component->instance()->getTable()->getFilter('author_id')?->getIndicators();
    $indicatorLabel = (string) ($indicators[0]?->getLabel() ?? '');

    expect($indicators)->toHaveCount(1)
        ->and($indicatorLabel)->toBe('Autor: Petr Novak & Jana Svobodova')
        ->and($indicatorLabel)->not->toContain('<img')
        ->and($indicatorLabel)->not->toContain('<span');
});

it('can filter pages by created month', function () {
    $previousMonth = now()->subMonth()->startOfMonth()->addDays(4)->setTime(9, 0);
    $currentMonth = now()->startOfMonth()->addDays(1)->setTime(14, 0);

    $olderPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'title' => 'Starší stránka',
        'created_at' => $previousMonth,
        'updated_at' => $previousMonth,
    ]);

    $newerPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'title' => 'Novější stránka',
        'created_at' => $currentMonth,
        'updated_at' => $currentMonth,
    ]);

    Livewire::test(ListPages::class)
        ->assertTableFilterExists('created_month')
        ->filterTable('created_month', $currentMonth->format('Y-m'))
        ->assertCanSeeTableRecords([$newerPage])
        ->assertCanNotSeeTableRecords([$olderPage]);
});

it('uses the title column for resource lock indicators in the pages list', function () {
    Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    Livewire::test(ListPages::class)
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('slug');
});

it('renders the publication status overview buttons for pages', function () {
    Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Draft,
    ]);

    Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $trashedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $trashedPage->delete();

    Livewire::test(PagePublicationStatusOverview::class)
        ->assertSeeHtml('fi-btn')
        ->assertSee('Vše')
        ->assertSee(ContentStatus::Draft->getLabel())
        ->assertSee(ContentStatus::Published->getLabel())
        ->assertSee('Koš')
        ->assertDontSee(ContentStatus::Scheduled->getLabel());
});

it('does not render record state tabs or the legacy record state links row', function () {
    Page::factory()->count(2)->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    $trashedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Published,
    ]);

    $trashedPage->delete();

    $component = Livewire::test(ListPages::class)
        ->assertDontSeeHtml('fi-ta-record-state-links');

    expect($component->instance()->getCachedTabs())->toBe([]);
});

it('shows deleted pages through the trashed table filter', function () {
    $activePage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $trashedPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
    ]);

    $trashedPage->delete();

    Livewire::test(ListPages::class)
        ->assertTableFilterExists('trashed')
        ->filterTable('trashed', false)
        ->assertCanSeeTableRecords([$trashedPage])
        ->assertCanNotSeeTableRecords([$activePage])
        ->filterTable('trashed', true)
        ->assertCanSeeTableRecords([$activePage, $trashedPage])
        ->removeTableFilter('trashed')
        ->assertCanSeeTableRecords([$activePage])
        ->assertCanNotSeeTableRecords([$trashedPage]);
});

it('ignores an unrelated tab query string on first load', function () {
    $draftPage = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Draft,
    ]);

    $component = Livewire::withQueryParams(['tab' => 'unknown'])
        ->test(ListPages::class);

    $component->assertCanSeeTableRecords([$draftPage]);
});

it('ignores a stale generic page query string on first load', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Published,
        'published_at' => now(),
    ]);

    $component = Livewire::withQueryParams(['page' => 2])
        ->test(ListPages::class);

    expect($component->instance()->getTablePaginationPageName())->toBe('pagesPage');

    $component->assertCanSeeTableRecords([$page]);
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
        ->and($table->getFilter('trashed', true))->not->toBeNull()
        ->and(array_map(fn (Section $section): string|\Illuminate\Contracts\Support\Htmlable|null => $section->getHeading(), $schema))->toBe(['Publikace', 'Metadata']);
});

it('stores homepage selection in general settings and clears legacy site settings', function () {
    $page = Page::factory()->create([
        'title' => 'Domovská stránka',
        'slug' => 'domovska-stranka',
        'status' => ContentStatus::Published,
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

it('publishes scheduled page via command when time is due', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
        'published_at' => now()->subMinute(),
    ]);

    $this->artisan('pages:publish-scheduled')
        ->assertSuccessful();

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Published)
        ->and($page->scheduled_at)->toBeNull();
});

it('does not publish page scheduled in the future', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Scheduled,
        'scheduled_at' => now()->addHour(),
        'published_at' => now()->addHour(),
    ]);

    $this->artisan('pages:publish-scheduled')
        ->assertSuccessful();

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Scheduled);
});

it('publishes scheduled page with legacy published_at fallback', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => ContentStatus::Scheduled,
        'scheduled_at' => null,
        'published_at' => now()->subMinutes(5),
    ]);

    $this->artisan('pages:publish-scheduled')
        ->assertSuccessful();

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Published);
});
