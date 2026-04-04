<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Clusters\SeoCluster;
use Awcodes\Botly\Models\Botly;
use Illuminate\Support\Facades\Gate;

class BotlyPage extends \Awcodes\Botly\Filament\Pages\BotlyPage
{
    protected static ?string $cluster = SeoCluster::class;

    protected static ?string $navigationLabel = 'Robots.txt';

    public static function canAccess(): bool
    {
        return auth()->user() !== null && Gate::allows('viewAny', Botly::class);
    }
}
