<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\ContentStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Models\Page;
use MiPress\Core\Policies\PagePolicy;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('allows contributor to update own published page', function () {
    $contributor = User::factory()->create();
    $contributor->assignRole(UserRole::Contributor->value);

    $page = Page::factory()->create([
        'author_id' => $contributor->id,
        'status' => ContentStatus::Published,
    ]);

    $policy = app(PagePolicy::class);

    expect($policy->update($contributor, $page))->toBeTrue();
});

it('prevents contributor from updating another users page', function () {
    $contributor = User::factory()->create();
    $contributor->assignRole(UserRole::Contributor->value);

    $page = Page::factory()->create([
        'author_id' => User::factory()->create()->id,
        'status' => ContentStatus::Draft,
    ]);

    $policy = app(PagePolicy::class);

    expect($policy->update($contributor, $page))->toBeFalse();
});

it('requires publish permission for deleting published pages', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['entry.view', 'entry.update', 'entry.delete']);

    $page = Page::factory()->create([
        'status' => ContentStatus::Published,
    ]);

    $policy = app(PagePolicy::class);

    expect($policy->delete($user, $page))->toBeFalse()
        ->and($policy->restore($user, $page))->toBeFalse();
});
