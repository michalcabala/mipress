<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Page;

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

it('releases the page lock after publishing through the browser', function () {
    if (! function_exists('visit')) {
        $this->markTestSkipped('Pest browser testing requires pestphp/pest-plugin-browser and Playwright.');
    }

    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
        'published_at' => null,
    ]);

    $editUrl = PageResource::getUrl('edit', ['record' => $page->getRouteKey()]);
    $indexUrl = PageResource::getUrl('index');

    $browser = visit($editUrl)
        ->assertSee('Publikovat')
        ->assertNoJavaScriptErrors()
        ->wait(1);

    expect($page->fresh()->resourceLock)->not->toBeNull();

    $browser->script(<<<'JS'
const publishButton = [...document.querySelectorAll('button')]
    .find((element) => element.getAttribute('wire:click')?.includes("mountAction('publishPage'"));

publishButton?.click();
JS);

    $browser->wait(1);

    $browser->script(<<<'JS'
const mountedActionForm = [...document.querySelectorAll('form')]
    .find((element) => element.getAttribute('wire:submit.prevent') === 'callMountedAction');

mountedActionForm?.querySelector('button[type="submit"]')?.click();
JS);

    $browser->wait(1)
        ->assertNoJavaScriptErrors();

    expect(parse_url($browser->url(), PHP_URL_PATH))->toBe(parse_url($indexUrl, PHP_URL_PATH));

    $page->refresh();

    expect($page->status)->toBe(EntryStatus::Published)
        ->and($page->published_at)->not->toBeNull()
        ->and($page->resourceLock)->toBeNull();
});
