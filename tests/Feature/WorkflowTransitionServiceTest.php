<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Page;
use MiPress\Core\Services\WorkflowTransitionService;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($admin);

    $this->blueprint = Blueprint::factory()->create([
        'handle' => 'page',
        'fields' => [],
    ]);

    $this->collection = Collection::factory()->create([
        'name' => 'Blog',
        'handle' => 'blog',
        'blueprint_id' => $this->blueprint->id,
        'slugs' => true,
    ]);

    $this->service = app(WorkflowTransitionService::class);
});

it('prepares review create data with cleared review state', function () {
    $data = $this->service->prepareCreateDataForIntent([
        'title' => 'Rozpracovaný obsah',
        'scheduled_at' => now()->addHour(),
        'review_note' => 'Old note',
    ], 'review');

    expect($data['status'])->toBe(EntryStatus::InReview)
        ->and($data['scheduled_at'])->toBeNull()
        ->and($data['review_note'])->toBeNull();
});

it('prepares scheduled publish data when publish date is in the future', function () {
    $publishAt = now()->addDay()->startOfHour();

    $data = $this->service->prepareCreateDataForIntent([
        'title' => 'Naplánovaný obsah',
        'published_at' => $publishAt,
    ], 'publish');

    expect($data['status'])->toBe(EntryStatus::Scheduled)
        ->and($data['published_at']?->format('Y-m-d H:i:s'))->toBe($publishAt->format('Y-m-d H:i:s'))
        ->and($data['scheduled_at']?->format('Y-m-d H:i:s'))->toBe($publishAt->format('Y-m-d H:i:s'));
});

it('publishes immediately when no future schedule is present', function () {
    $page = Page::factory()->create([
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
        'published_at' => null,
        'review_note' => 'Needs cleanup',
    ]);

    $transition = $this->service->publish($page);

    $page->refresh();

    expect($transition->oldStatus)->toBe(EntryStatus::Draft)
        ->and($transition->newStatus)->toBe(EntryStatus::Published)
        ->and($page->status)->toBe(EntryStatus::Published)
        ->and($page->published_at)->not->toBeNull()
        ->and($page->scheduled_at)->toBeNull()
        ->and($page->review_note)->toBeNull();
});

it('schedules publication when future publish date is already set on the record', function () {
    $publishAt = now()->addHours(6)->startOfHour();

    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Draft,
        'published_at' => $publishAt,
    ]);

    $transition = $this->service->publish($entry);

    $entry->refresh();

    expect($transition->newStatus)->toBe(EntryStatus::Scheduled)
        ->and($transition->scheduledFor?->format('Y-m-d H:i:s'))->toBe($publishAt->format('Y-m-d H:i:s'))
        ->and($entry->status)->toBe(EntryStatus::Scheduled)
        ->and($entry->scheduled_at?->format('Y-m-d H:i:s'))->toBe($publishAt->format('Y-m-d H:i:s'));
});

it('returns rejected content to draft and clears the rejection note', function () {
    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
        'status' => EntryStatus::Rejected,
        'review_note' => 'Doplnit zdroje.',
    ]);

    $transition = $this->service->saveDraft($entry);

    $entry->refresh();

    expect($transition->oldStatus)->toBe(EntryStatus::Rejected)
        ->and($transition->newStatus)->toBe(EntryStatus::Draft)
        ->and($entry->status)->toBe(EntryStatus::Draft)
        ->and($entry->review_note)->toBeNull();
});
