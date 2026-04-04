<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Illuminate\Support\Facades\Gate;
use MuhammadNawlo\FilamentSitemapGenerator\Models\SitemapSetting;

class SitemapSettingsPage extends \MuhammadNawlo\FilamentSitemapGenerator\Pages\SitemapSettingsPage
{
    public static function canAccess(): bool
    {
        return auth()->user() !== null && Gate::allows('viewAny', SitemapSetting::class);
    }
}
