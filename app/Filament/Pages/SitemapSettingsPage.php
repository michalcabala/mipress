<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Clusters\SeoCluster;
use Illuminate\Support\Facades\Gate;
use MuhammadNawlo\FilamentSitemapGenerator\Models\SitemapSetting;

class SitemapSettingsPage extends \MuhammadNawlo\FilamentSitemapGenerator\Pages\SitemapSettingsPage
{
    protected static ?string $cluster = SeoCluster::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user() !== null && Gate::allows('viewAny', SitemapSetting::class);
    }
}
