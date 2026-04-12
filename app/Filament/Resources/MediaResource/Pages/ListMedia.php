<?php

declare(strict_types=1);

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use MiPress\Core\Jobs\RegenerateMediaConversionsJob;
use MiPress\Core\Models\Media;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateAllConversions')
                ->label('Regenerovat všechny konverze')
                ->icon('fal-arrows-rotate')
                ->color('gray')
                ->authorize(fn (): bool => auth()->user()?->can('regenerateAllConversions', Media::class) ?? false)
                ->requiresConfirmation()
                ->modalHeading('Regenerovat všechny konverze?')
                ->modalDescription('Konverze všech obrázků se zařadí do fronty na pozadí.')
                ->action(function (): void {
                    $ids = Media::query()
                        ->where('mime_type', 'like', 'image/%')
                        ->pluck('id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();

                    RegenerateMediaConversionsJob::dispatch($ids);
                }),
            CreateAction::make()
                ->label('Nahrát médium')
                ->icon('fal-upload'),
        ];
    }
}
