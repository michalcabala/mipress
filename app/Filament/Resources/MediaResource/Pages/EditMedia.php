<?php

declare(strict_types=1);

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
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
            ->modalDescription('Nahrajte novou verzi obrázku, upravte ořez v editoru a potvrďte. Konverze se následně přegenerují.')
            ->modalSubmitActionLabel('Uložit upravený obrázek')
            ->schema([
                FileUpload::make('upload')
                    ->label('Upravený obrázek')
                    ->disk(MediaConfig::disk())
                    ->directory('tmp/resource')
                    ->visibility('public')
                    ->preserveFilenames()
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

                $temporaryPath = data_get($data, 'upload');

                if (! is_string($temporaryPath) || trim($temporaryPath) === '') {
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

        foreach (MediaConfig::cropConversions() as $conversion) {
            $action = $this->makeConversionCropperAction($conversion);

            if ($action instanceof Action) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * @param  array{name: string, label: string, w: int, h: int|null, mode: 'crop'|'resize'}  $conversion
     */
    private function makeConversionCropperAction(array $conversion): ?Action
    {
        $conversionName = (string) ($conversion['name'] ?? '');
        $conversionLabel = (string) ($conversion['label'] ?? $conversionName);
        $width = (int) ($conversion['w'] ?? 0);
        $height = (int) ($conversion['h'] ?? 0);

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
            ->modalDescription("Poměr je uzamčen na {$ratio}. Výstup bude {$width} × {$height} px.")
            ->modalSubmitActionLabel('Uložit konverzi')
            ->fillForm(function (): array {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return [];
                }

                $disk = MediaConfig::disk();
                $storage = Storage::disk($disk);
                $originalPath = ltrim((string) $record->getPathRelativeToRoot(), '/');

                if (! $storage->exists($originalPath)) {
                    return [];
                }

                $tmpName = 'crop_'.uniqid().'_'.$record->file_name;
                $tmpPath = 'tmp/resource/'.$tmpName;
                $storage->copy($originalPath, $tmpPath);

                return ['upload' => [$tmpPath]];
            })
            ->schema([
                FileUpload::make('upload')
                    ->label('Zdrojový obrázek — upravte ořez v editoru')
                    ->disk(MediaConfig::disk())
                    ->directory('tmp/resource')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->imageEditor()
                    ->imageEditorMode(2)
                    ->imageAspectRatio($ratio)
                    ->imageEditorAspectRatioOptions([$ratio])
                    ->imageEditorEmptyFillColor('#ffffff')
                    ->acceptedFileTypes(MediaConfig::allowedMimeTypesForGroup('images'))
                    ->maxSize((int) floor(MediaConfig::maxUploadSize() / 1024))
                    ->required(),
            ])
            ->action(function (array $data) use ($conversionName, $conversionLabel): void {
                $record = $this->getRecord();

                if (! $record instanceof Media || ! $record->isImage()) {
                    return;
                }

                $temporaryPath = data_get($data, 'upload');

                if (! is_string($temporaryPath) || trim($temporaryPath) === '') {
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

    /**
     * @return array<int, string|null>
     */
    private function cropperAspectRatioOptions(): array
    {
        $options = [null]; // volný ořez

        foreach (MediaConfig::cropConversions() as $conversion) {
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

        return true;
    }

    /**
     * @return array{name: string, label: string, w: int, h: int|null, mode: 'crop'|'resize'}|null
     */
    private function resolveConversionConfig(string $conversionName): ?array
    {
        foreach (MediaConfig::conversions() as $conversion) {
            if (($conversion['name'] ?? null) === $conversionName) {
                return $conversion;
            }
        }

        return null;
    }

    private function replaceImageFromTemporaryUpload(Media $record, string $temporaryPath): bool
    {
        $disk = MediaConfig::disk();
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

        RegenerateMediaConversionsJob::dispatch([(int) $record->getKey()]);

        return true;
    }
}
