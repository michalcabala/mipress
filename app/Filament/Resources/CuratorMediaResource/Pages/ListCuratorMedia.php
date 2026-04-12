<?php

declare(strict_types=1);

namespace App\Filament\Resources\CuratorMediaResource\Pages;

use App\Filament\Resources\CuratorMediaResource;
use App\Models\CuratorMedia;
use App\Services\FocalPointCropper;
use Awcodes\Curator\Resources\Media\Pages\ListMedia;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ListCuratorMedia extends ListMedia
{
    protected static string $resource = CuratorMediaResource::class;

    public function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_all_curations')
                ->label('Přegenerovat všechny ořezy')
                ->icon('far-arrows-rotate')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Přegenerovat všechny ořezy')
                ->modalDescription('Ořezy všech obrázků budou přegenerovány podle jejich ohniskových bodů. Tato operace může chvíli trvat.')
                ->action(function (): void {
                    $cropper = app(FocalPointCropper::class);
                    $count = 0;

                    CuratorMedia::query()
                        ->whereNotNull('ext')
                        ->each(function (CuratorMedia $media) use ($cropper, &$count): void {
                            if (! is_media_resizable($media->ext)) {
                                return;
                            }

                            $curations = $cropper->generateAll($media);
                            $media->update(['curations' => $curations]);
                            $count++;
                        });

                    Notification::make()
                        ->title("Ořezy přegenerovány pro {$count} médií")
                        ->success()
                        ->send();
                }),
            ...parent::getHeaderActions(),
        ];
    }
}
