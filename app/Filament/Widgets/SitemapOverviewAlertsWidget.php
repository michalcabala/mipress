<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\SitemapSettingsPage;

class SitemapOverviewAlertsWidget extends \MuhammadNawlo\FilamentSitemapGenerator\Widgets\SitemapOverviewAlertsWidget
{
    public function getViewData(): array
    {
        $data = parent::getViewData();
        $data['settingsUrl'] = SitemapSettingsPage::getUrl();

        return $data;
    }
}
