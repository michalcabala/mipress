<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\SocialFeeds\Filament\Resources\SocialAccountResource;
use MiPress\SocialFeeds\Filament\Resources\SocialFeedResource;

use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(PermissionSeeder::class);
});

it('prevents contributor from accessing social feeds in admin', function (): void {
    $contributor = User::factory()->create();
    $contributor->assignRole(UserRole::Contributor->value);

    $this->actingAs($contributor)
        ->get(SocialAccountResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($contributor)
        ->get(SocialFeedResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($contributor)
        ->get(route('social.auth.redirect', 'facebook'))
        ->assertForbidden();
});

it('allows editor to access social feeds resources', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole(UserRole::Editor->value);

    $this->actingAs($editor)
        ->get(SocialAccountResource::getUrl('index'))
        ->assertSuccessful();

    $this->actingAs($editor)
        ->get(SocialFeedResource::getUrl('index'))
        ->assertSuccessful();
});
