<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Clusters\SeoCluster;
use Awcodes\Botly\Models\Botly;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

class BotlyPage extends \Awcodes\Botly\Filament\Pages\BotlyPage
{
    protected static ?string $cluster = SeoCluster::class;

    protected static ?string $navigationLabel = 'Správa robots.txt';

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): string|\BackedEnum|Htmlable|null
    {
        return 'fal-user-robot';
    }

    public static function canAccess(): bool
    {
        return auth()->user() !== null && Gate::allows('viewAny', Botly::class);
    }
}
