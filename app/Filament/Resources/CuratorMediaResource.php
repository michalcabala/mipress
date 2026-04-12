<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CuratorMediaResource\Pages\CreateCuratorMedia;
use App\Filament\Resources\CuratorMediaResource\Pages\EditCuratorMedia;
use App\Filament\Resources\CuratorMediaResource\Pages\ListCuratorMedia;
use Awcodes\Curator\Resources\Media\MediaResource;

class CuratorMediaResource extends MediaResource
{
    protected static ?string $slug = 'curator-media';

    public static function getPages(): array
    {
        return [
            'index' => ListCuratorMedia::route('/'),
            'create' => CreateCuratorMedia::route('/create'),
            'edit' => EditCuratorMedia::route('/{record}/edit'),
        ];
    }
}
