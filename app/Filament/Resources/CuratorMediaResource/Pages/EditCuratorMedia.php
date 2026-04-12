<?php

declare(strict_types=1);

namespace App\Filament\Resources\CuratorMediaResource\Pages;

use App\Filament\Resources\CuratorMediaResource;
use Awcodes\Curator\Resources\Media\Pages\EditMedia;

class EditCuratorMedia extends EditMedia
{
    protected static string $resource = CuratorMediaResource::class;
}
