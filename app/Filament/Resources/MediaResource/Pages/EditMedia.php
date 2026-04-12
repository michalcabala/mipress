<?php

declare(strict_types=1);

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use MiPress\Core\Jobs\RegenerateMediaConversionsJob;
use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Models\Media;
use MiPress\Core\Services\MediaFileService;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Media $record */
        $record = $this->getRecord();

        $data['alt'] = $record->alt;
        $data['title'] = $record->title;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['replace_upload']);

        return $data;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->makeOriginalImageCropperAction(),
            ...$this->getConversionCropperActions(),
            Action::make('regenerateConversions')
                ->label('Regenerovat konverze')
                ->icon('fal-arrows-rotate')
                ->color('gray')
                ->visible(fn (): bool => $this->getRecord()->isImage())
                ->authorize(fn (): bool => auth()->user()?->can('regenerateConversions', $this->getRecord()) ?? false)
                ->requiresConfirmation()
                ->modalHeading('Regenerovat konverze média?')
                ->modalDescription('Konverze se zařadí do fronty na pozadí.')
                ->action(function (): void {
                    RegenerateMediaConversionsJob::dispatch([(int) $this->getRecord()->getKey()]);
                }),
            DeleteAction::make(),
        ];
    }

    private function makeOriginalImageCropperAction(): Action
    {
        $service = app(MediaFileService::class);

        return Action::make('editWithCropper')
            ->label('Upravit originál')
            ->icon('fal-crop-simple')
            ->color('primary')
            ->visible(fn (): bool => $this->getRecord()->isImage())
            ->modalWidth(Width::Screen)
            ->modalHeading('Upravit originální obrázek')
            ->modalDescription('Pracujete s aktuálním originálem. V editoru můžete upravit výřez a přepínat poměry stran podle aktivních crop konverzí.')
            ->modalSubmitActionLabel('Uložit upravený obrázek')
            ->fillForm(function () use ($service): array {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return [];
                }

                $temporaryPath = $service->createEditorCopy($record, 'original');

                if ($temporaryPath === null) {
                    return [];
                }

                return ['upload' => [$temporaryPath]];
            })
            ->schema([
                FileUpload::make('upload')
                    ->label('Originální obrázek')
                    ->disk(MediaConfig::disk())
                    ->directory('tmp/resource')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->image()
                    ->imageEditor()
                    ->imageEditorMode(2)
                    ->imageEditorAspectRatioOptions($service->cropperAspectRatioOptions())
                    ->acceptedFileTypes(MediaConfig::allowedMimeTypesForGroup('images'))
                    ->maxSize((int) floor(MediaConfig::maxUploadSize() / 1024))
                    ->required(),
            ])
            ->action(function (array $data) use ($service): void {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return;
                }

                $temporaryPath = $service->extractUploadPath(data_get($data, 'upload'));

                if ($temporaryPath === null) {
                    return;
                }

                if (! $service->replaceOriginal($record, $temporaryPath)) {
                    Notification::make()
                        ->title('Obrázek se nepodařilo aktualizovat')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Obrázek byl aktualizován')
                    ->body('Konverze se regenerují na pozadí.')
                    ->success()
                    ->send();

                $this->redirect(static::$resource::getUrl('edit', ['record' => $record]));
            });
    }

    /**
     * @return array<int, Action>
     */
    private function getConversionCropperActions(): array
    {
        $actions = [];

        foreach (MediaConfig::editorManualCropConversions() as $conversion) {
            $action = $this->makeConversionCropperAction($conversion);

            if ($action instanceof Action) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * @param  array<string, mixed>  $conversion
     */
    private function makeConversionCropperAction(array $conversion): ?Action
    {
        $service = app(MediaFileService::class);

        $conversionName = (string) ($conversion['name'] ?? '');
        $conversionLabel = (string) ($conversion['label'] ?? $conversionName);
        $width = (int) ($conversion['w'] ?? 0);
        $height = (int) ($conversion['h'] ?? 0);
        $editorHelpText = trim((string) ($conversion['editor_help_text'] ?? ''));
        $usageContext = trim((string) ($conversion['usage_context'] ?? ''));
        $manualCropRequired = (bool) ($conversion['manual_crop_required'] ?? false);

        if ($conversionName === '' || $width <= 0 || $height <= 0) {
            return null;
        }

        $ratio = $width.':'.$height;

        return Action::make($this->conversionActionName($conversionName))
            ->label('Upravit '.$conversionLabel)
            ->icon('fal-crop-simple')
            ->color('gray')
            ->visible(fn (): bool => $this->getRecord()->isImage())
            ->modalWidth(Width::Screen)
            ->modalHeading('Upravit konverzi: '.$conversionLabel)
            ->modalDescription($this->conversionCropperDescription($ratio, $width, $height, $editorHelpText, $usageContext, $manualCropRequired))
            ->modalSubmitActionLabel('Uložit konverzi')
            ->fillForm(function () use ($service, $conversionName): array {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return [];
                }

                $temporaryPath = $service->createEditorCopy($record, 'crop_'.$conversionName);

                if ($temporaryPath === null) {
                    return [];
                }

                return ['upload' => [$temporaryPath]];
            })
            ->schema([
                FileUpload::make('upload')
                    ->label('Zdrojový obrázek')
                    ->disk(MediaConfig::disk())
                    ->directory('tmp/resource')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->image()
                    ->imageEditor()
                    ->imageEditorMode(2)
                    ->imageAspectRatio($ratio)
                    ->imageEditorAspectRatioOptions([$ratio])
                    ->imageEditorEmptyFillColor('#ffffff')
                    ->helperText($editorHelpText !== '' ? $editorHelpText : null)
                    ->acceptedFileTypes(MediaConfig::allowedMimeTypesForGroup('images'))
                    ->maxSize((int) floor(MediaConfig::maxUploadSize() / 1024))
                    ->required(),
            ])
            ->action(function (array $data) use ($service, $conversionName, $conversionLabel): void {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return;
                }

                $temporaryPath = $service->extractUploadPath(data_get($data, 'upload'));

                if ($temporaryPath === null) {
                    return;
                }

                if (! $service->replaceConversion($record, $conversionName, $temporaryPath)) {
                    Notification::make()
                        ->title('Konverzi se nepodařilo aktualizovat')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Konverze byla aktualizována')
                    ->body("Konverze {$conversionLabel} byla upravena přes cropper.")
                    ->success()
                    ->send();

                $this->redirect(static::$resource::getUrl('edit', ['record' => $record]));
            });
    }

    private function conversionActionName(string $conversionName): string
    {
        return 'editConversion_'.$conversionName;
    }

    private function conversionCropperDescription(
        string $ratio,
        int $width,
        int $height,
        string $editorHelpText,
        string $usageContext,
        bool $manualCropRequired,
    ): string {
        $parts = [
            "Poměr je uzamčen na {$ratio}. Výstup bude {$width} × {$height} px.",
        ];

        if ($editorHelpText !== '') {
            $parts[] = $editorHelpText;
        }

        if ($usageContext !== '') {
            $parts[] = 'Použití: '.$usageContext;
        }

        if ($manualCropRequired) {
            $parts[] = 'Tato konverze vyžaduje ruční crop.';
        }

        return implode(' ', $parts);
    }

    protected function afterSave(): void
    {
        /** @var Media $record */
        $record = $this->getRecord();

        $this->handleReplaceUpload($record);

        if ($record->isImage()) {
            RegenerateMediaConversionsJob::dispatch([(int) $record->getKey()]);
        }
    }

    private function handleReplaceUpload(Media $record): void
    {
        $replaceUpload = data_get($this->data, 'replace_upload');

        if (empty($replaceUpload)) {
            return;
        }

        $service = app(MediaFileService::class);
        $temporaryPath = $service->extractUploadPath($replaceUpload);

        if ($temporaryPath === null) {
            return;
        }

        if ($service->replaceOriginal($record, $temporaryPath)) {
            Notification::make()
                ->title('Originální soubor byl nahrazen')
                ->body('Konverze se regenerují na pozadí.')
                ->success()
                ->send();
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Médium bylo uloženo. Konverze se regenerují na pozadí.';
    }
}
