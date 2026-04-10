<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Pages\BotlyPage;
use MiPress\Core\Filament\Pages\GlobalSeoSettings;
use MiPress\Core\Filament\Pages\SitemapSettings;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(PermissionSeeder::class);
});

describe('seo tools authorization', function () {
    it('forbids contributor from robots and sitemap pages', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);

        actingAs($contributor)
            ->get(GlobalSeoSettings::getUrl())
            ->assertForbidden();

        actingAs($contributor)
            ->get(BotlyPage::getUrl())
            ->assertForbidden();

        actingAs($contributor)
            ->get(SitemapSettings::getUrl())
            ->assertForbidden();
    });

    it('forbids editor from robots and sitemap pages', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);

        actingAs($editor)
            ->get(GlobalSeoSettings::getUrl())
            ->assertForbidden();

        actingAs($editor)
            ->get(BotlyPage::getUrl())
            ->assertForbidden();

        actingAs($editor)
            ->get(SitemapSettings::getUrl())
            ->assertForbidden();
    });

    it('allows admin to access robots and sitemap pages', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        actingAs($admin);

        expect(GlobalSeoSettings::canAccess())->toBeTrue()
            ->and(BotlyPage::canAccess())->toBeTrue()
            ->and(SitemapSettings::canAccess())->toBeTrue();
    });
});
