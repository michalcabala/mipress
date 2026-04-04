<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Clusters\SeoCluster;
use Illuminate\Support\Facades\Gate;
use MuhammadNawlo\FilamentSitemapGenerator\Models\SitemapSetting;

class SitemapGeneratorPage extends \MuhammadNawlo\FilamentSitemapGenerator\Pages\SitemapGeneratorPage
{
    protected static ?string $cluster = SeoCluster::class;

    protected static ?string $navigationLabel = 'Sitemap';

    public static function canAccess(): bool
    {
        return auth()->user() !== null && Gate::allows('viewAny', SitemapSetting::class);
    }
}
