<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Awcodes\Botly\Models\Botly;
use Illuminate\Support\Facades\Gate;

class BotlyPage extends \Awcodes\Botly\Filament\Pages\BotlyPage
{
    public static function canAccess(): bool
    {
        return auth()->user() !== null && Gate::allows('viewAny', Botly::class);
    }
}
