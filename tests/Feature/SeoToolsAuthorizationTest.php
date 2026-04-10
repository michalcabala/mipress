<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Pages\BotlyPage;
use MiPress\Core\Filament\Pages\GlobalSeoSettings;
use MiPress\Core\Filament\Pages\SitemapSettings;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

describe('seo tools authorization', function () {
    it('forbids contributor from robots and sitemap pages', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);

        $this->actingAs($contributor)
            ->get(GlobalSeoSettings::getUrl())
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get(BotlyPage::getUrl())
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get(SitemapSettings::getUrl())
            ->assertForbidden();
    });

    it('forbids editor from robots and sitemap pages', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);

        $this->actingAs($editor)
            ->get(GlobalSeoSettings::getUrl())
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(BotlyPage::getUrl())
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(SitemapSettings::getUrl())
            ->assertForbidden();
    });

    it('allows admin to access robots and sitemap pages', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        $this->actingAs($admin);

        expect(GlobalSeoSettings::canAccess())->toBeTrue()
            ->and(BotlyPage::canAccess())->toBeTrue()
            ->and(SitemapSettings::canAccess())->toBeTrue();
    });
});
