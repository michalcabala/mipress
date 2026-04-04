<?php

declare(strict_types=1);

namespace App\Filament\Plugins;

use App\Filament\Pages\BotlyPage;
use Filament\Panel;

class BotlyPlugin extends \Awcodes\Botly\BotlyPlugin
{
    public function register(Panel $panel): void
    {
        $panel->pages([
            BotlyPage::class,
        ]);
    }
}
