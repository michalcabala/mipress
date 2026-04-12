<?php

declare(strict_types=1);

namespace App\Filament\Resources\CuratorMediaResource\Pages;

use App\Filament\Resources\CuratorMediaResource;
use App\Models\CuratorMedia;
use App\Services\FocalPointCropper;
use Awcodes\Curator\Resources\Media\Pages\EditMedia;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

class EditCuratorMedia extends EditMedia
{
    protected static string $resource = CuratorMediaResource::class;

    /**
     * Track original focal point values to detect changes.
     *
     * @var array{x: int, y: int}
     */
    protected array $originalFocalPoint = ['x' => 50, 'y' => 50];

    protected function afterFill(): void
    {
        /** @var CuratorMedia $record */
        $record = $this->record;

        $this->originalFocalPoint = [
            'x' => $record->focal_point_x ?? 50,
            'y' => $record->focal_point_y ?? 50,
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->action('save')
                ->label(trans('curator::views.panel.edit_save')),
            Action::make('regenerate_curations')
                ->label('Přegenerovat curations')
                ->icon('far-arrows-rotate')
                ->color('warning')
                ->visible(fn (): bool => $this->record && is_media_resizable($this->record->ext))
                ->requiresConfirmation()
                ->modalHeading('Přegenerovat curations')
                ->modalDescription('Všechny stávající curations budou nahrazeny novými, vygenerovanými podle aktuálního focal pointu.')
                ->action(function (): void {
                    $this->regenerateCurations(redirect: true);
                }),
            Action::make('preview')
                ->color('gray')
                ->url($this->record->url, shouldOpenInNewTab: true)
                ->label(trans('curator::views.panel.view')),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        parent::afterSave();

        /** @var CuratorMedia $record */
        $record = $this->record->fresh();

        $newX = $record->focal_point_x ?? 50;
        $newY = $record->focal_point_y ?? 50;

        if ($newX !== $this->originalFocalPoint['x'] || $newY !== $this->originalFocalPoint['y']) {
            $this->regenerateCurations(notify: true, redirect: true);
        }
    }

    protected function regenerateCurations(bool $notify = true, bool $redirect = false): void
    {
        /** @var CuratorMedia $record */
        $record = $this->record->fresh();

        $cropper = app(FocalPointCropper::class);
        $curations = $cropper->generateAll($record);

        $record->update(['curations' => $curations]);

        if ($notify) {
            $count = count($curations);
            Notification::make()
                ->title("Vygenerováno {$count} curations")
                ->body('Curations byly přegenerovány podle focal pointu ('.$record->focal_point_x.'% / '.$record->focal_point_y.'%).')
                ->success()
                ->send();
        }

        if ($redirect) {
            $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
        }
    }
}
