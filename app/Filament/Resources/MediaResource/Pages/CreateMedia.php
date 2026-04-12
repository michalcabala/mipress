<?php

declare(strict_types=1);

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use MiPress\Core\Models\Media;
use MiPress\Core\Services\MediaLibraryService;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('edit', [
            'record' => $this->getRecord(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $createdIds = app(MediaLibraryService::class)->createFromTemporaryPaths(
            $data['upload'] ?? null,
            auth()->id(),
        );

        /** @var Media $media */
        $media = Media::query()->findOrFail($createdIds[0] ?? 0);
        $media->alt = $data['alt'] ?? null;
        $media->title = $data['title'] ?? null;
        $media->focal_point_x = (int) ($data['focal_point_x'] ?? 50);
        $media->focal_point_y = (int) ($data['focal_point_y'] ?? 50);
        $media->save();

        return $media;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Médium bylo nahráno.';
    }
}
