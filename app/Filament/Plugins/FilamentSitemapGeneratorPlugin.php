<?php

declare(strict_types=1);

namespace App\Filament\Plugins;

use App\Filament\Pages\SitemapGeneratorPage;
use App\Filament\Pages\SitemapSettingsPage;
use Filament\Panel;

class FilamentSitemapGeneratorPlugin extends \MuhammadNawlo\FilamentSitemapGenerator\FilamentSitemapGeneratorPlugin
{
    public function register(Panel $panel): void
    {
        $panel->pages([
            SitemapGeneratorPage::class,
            SitemapSettingsPage::class,
        ]);
    }
}
