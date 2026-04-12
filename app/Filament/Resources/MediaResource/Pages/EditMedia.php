<?php

declare(strict_types=1);

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use MiPress\Core\Jobs\RegenerateMediaConversionsJob;
use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Models\Media;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Throwable;

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
        return Action::make('editWithCropper')
            ->label('Upravit obrázek (Cropper)')
            ->icon('fal-crop-simple')
            ->color('primary')
            ->visible(fn (): bool => $this->getRecord()->isImage())
            ->modalWidth('3xl')
            ->modalHeading('Upravit obrázek')
            ->modalDescription('Pracujete s aktuálním originálem. V editoru můžete upravit výřez a přepínat poměry stran podle aktivních crop konverzí.')
            ->modalSubmitActionLabel('Uložit upravený obrázek')
            ->fillForm(function (): array {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return [];
                }

                $temporaryPath = $this->createTemporaryEditorCopy($record, 'original');

                if ($temporaryPath === null) {
                    return [];
                }

                return ['upload' => [$temporaryPath]];
            })
            ->schema([
                FileUpload::make('upload')
                    ->label('Originální obrázek — upravte výřez v editoru')
                    ->disk(MediaConfig::disk())
                    ->directory('tmp/resource')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->image()
                    ->imageEditor()
                    ->imageEditorMode(2)
                    ->imageEditorAspectRatioOptions($this->cropperAspectRatioOptions())
                    ->acceptedFileTypes(MediaConfig::allowedMimeTypesForGroup('images'))
                    ->maxSize((int) floor(MediaConfig::maxUploadSize() / 1024))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return;
                }

                $temporaryPath = $this->extractTemporaryUploadPath(data_get($data, 'upload'));

                if ($temporaryPath === null) {
                    return;
                }

                if (! $this->replaceImageFromTemporaryUpload($record, $temporaryPath)) {
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
            ->modalWidth('3xl')
            ->modalHeading('Upravit konverzi: '.$conversionLabel)
            ->modalDescription($this->conversionCropperDescription($ratio, $width, $height, $editorHelpText, $usageContext, $manualCropRequired))
            ->modalSubmitActionLabel('Uložit konverzi')
            ->fillForm(function () use ($conversionName): array {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return [];
                }

                $temporaryPath = $this->createTemporaryEditorCopy($record, 'crop_'.$conversionName);

                if ($temporaryPath === null) {
                    return [];
                }

                return ['upload' => [$temporaryPath]];
            })
            ->schema([
                FileUpload::make('upload')
                    ->label('Zdrojový obrázek — upravte ořez v editoru')
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
            ->action(function (array $data) use ($conversionName, $conversionLabel): void {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return;
                }

                $temporaryPath = $this->extractTemporaryUploadPath(data_get($data, 'upload'));

                if ($temporaryPath === null) {
                    return;
                }

                if (! $this->replaceConversionFromTemporaryUpload($record, $conversionName, $temporaryPath)) {
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

    private function createTemporaryEditorCopy(Media $record, string $prefix): ?string
    {
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(MediaConfig::disk());
        $originalPath = ltrim((string) $record->getPathRelativeToRoot(), '/');

        if (! $storage->exists($originalPath)) {
            return null;
        }

        $tmpName = $prefix.'_'.uniqid().'_'.basename($record->file_name);
        $tmpPath = 'tmp/resource/'.$tmpName;

        if (! $storage->copy($originalPath, $tmpPath)) {
            return null;
        }

        return $tmpPath;
    }

    private function extractTemporaryUploadPath(mixed $state): ?string
    {
        if (is_string($state)) {
            $state = trim($state);

            return $state !== '' ? $state : null;
        }

        if (is_array($state)) {
            $first = reset($state);

            return $this->extractTemporaryUploadPath($first === false ? null : $first);
        }

        return null;
    }

    /**
     * @return array<int, string|null>
     */
    private function cropperAspectRatioOptions(): array
    {
        $options = [null]; // volný ořez

        foreach (MediaConfig::editorCropConversions() as $conversion) {
            $w = (int) ($conversion['w'] ?? 0);
            $h = (int) ($conversion['h'] ?? 0);

            if ($w > 0 && $h > 0) {
                $options[] = $w.':'.$h;
            }
        }

        return array_values(array_unique($options));
    }

    protected function afterSave(): void
    {
        /** @var Media $record */
        $record = $this->getRecord();

        if ($record->isImage()) {
            RegenerateMediaConversionsJob::dispatch([(int) $record->getKey()]);
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Médium bylo uloženo. Konverze se regenerují na pozadí.';
    }

    private function replaceConversionFromTemporaryUpload(Media $record, string $conversionName, string $temporaryPath): bool
    {
        $conversion = $this->resolveConversionConfig($conversionName);

        if ($conversion === null) {
            return false;
        }

        $targetWidth = (int) ($conversion['w'] ?? 0);
        $targetHeight = (int) ($conversion['h'] ?? 0);

        if ($targetWidth <= 0 || $targetHeight <= 0) {
            return false;
        }

        $disk = MediaConfig::disk();
        $storage = Storage::disk($disk);

        if (! $storage->exists($temporaryPath)) {
            return false;
        }

        $targetRelativePath = ltrim((string) $record->getPathRelativeToRoot($conversionName), '/');

        if ($targetRelativePath === '') {
            return false;
        }

        $targetDirectory = trim((string) dirname($targetRelativePath), '/');

        if ($targetDirectory !== '' && ! $storage->exists($targetDirectory)) {
            $storage->makeDirectory($targetDirectory);
        }

        try {
            Image::load($storage->path($temporaryPath))
                ->fit(Fit::Crop, $targetWidth, $targetHeight)
                ->format('webp')
                ->quality(MediaConfig::conversionQuality())
                ->save($storage->path($targetRelativePath));
        } catch (Throwable) {
            return false;
        } finally {
            if ($storage->exists($temporaryPath)) {
                $storage->delete($temporaryPath);
            }
        }

        $record->markAsConversionGenerated($conversionName);
        $record->markManualConversionOverride($conversionName);

        return true;
    }

    /**
     * @return array{name: string, label: string, w: int, h: int|null, mode: 'crop'|'resize'}|null
     */
    private function resolveConversionConfig(string $conversionName): ?array
    {
        return MediaConfig::findConversion($conversionName);
    }

    private function replaceImageFromTemporaryUpload(Media $record, string $temporaryPath): bool
    {
        $disk = MediaConfig::disk();
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);

        if (! $storage->exists($temporaryPath)) {
            return false;
        }

        $currentRelativePath = ltrim((string) $record->getPathRelativeToRoot(), '/');
        $targetDirectory = trim((string) dirname($currentRelativePath), '/');
        $newFileName = basename($temporaryPath);

        if ($newFileName === '' || $newFileName === '.') {
            return false;
        }

        $targetRelativePath = $targetDirectory !== ''
            ? $targetDirectory.'/'.$newFileName
            : $newFileName;

        if ($targetDirectory !== '' && ! $storage->exists($targetDirectory)) {
            $storage->makeDirectory($targetDirectory);
        }

        if ($storage->exists($targetRelativePath)) {
            $storage->delete($targetRelativePath);
        }

        if (! $storage->move($temporaryPath, $targetRelativePath)) {
            return false;
        }

        if ($currentRelativePath !== $targetRelativePath && $storage->exists($currentRelativePath)) {
            $storage->delete($currentRelativePath);
        }

        $record->forceFill([
            'name' => pathinfo($newFileName, PATHINFO_FILENAME),
            'file_name' => $newFileName,
            'mime_type' => (string) ($storage->mimeType($targetRelativePath) ?: $record->mime_type),
            'size' => (int) ($storage->size($targetRelativePath) ?: $record->size),
        ])->saveQuietly();

        $dimensions = @getimagesize($record->getPath());

        if (is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
            $record->setCustomProperty('width', (int) $dimensions[0]);
            $record->setCustomProperty('height', (int) $dimensions[1]);
            $record->saveQuietly();
        }

        $this->invalidateManualConversionOverrides($record);

        RegenerateMediaConversionsJob::dispatch([(int) $record->getKey()]);

        return true;
    }

    private function invalidateManualConversionOverrides(Media $record): void
    {
        $overrides = $record->manualConversionOverrides();

        if ($overrides === []) {
            return;
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(MediaConfig::disk());
        $generatedConversions = is_array($record->generated_conversions) ? $record->generated_conversions : [];

        foreach (array_keys($overrides) as $conversionName) {
            $conversionPath = ltrim((string) $record->getPathRelativeToRoot($conversionName), '/');

            if ($conversionPath !== '' && $storage->exists($conversionPath)) {
                $storage->delete($conversionPath);
            }

            $generatedConversions[$conversionName] = false;
        }

        $record->generated_conversions = $generatedConversions;
        $record->setCustomProperty('manual_conversion_overrides', []);
        $record->saveQuietly();
    }
}
