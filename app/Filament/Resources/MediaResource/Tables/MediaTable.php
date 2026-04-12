<?php

declare(strict_types=1);

namespace App\Filament\Resources\MediaResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use MiPress\Core\Jobs\RegenerateMediaConversionsJob;
use MiPress\Core\Models\Media;

class MediaTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Náhled')
                    ->state(fn (Media $record): ?string => $record->isImage() ? mipress_media_url($record, 'thumbnail') : null)
                    ->square()
                    ->size(60),
                TextColumn::make('file_name')
                    ->label('Soubor')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('alt')
                    ->label('Alt')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->orWhere('custom_properties->alt', 'like', "%{$search}%")
                        ->orWhere('custom_properties->title', 'like', "%{$search}%")),
                TextColumn::make('mime_type')
                    ->label('MIME')
                    ->badge(),
                TextColumn::make('human_readable_size')
                    ->label('Velikost')
                    ->sortable(['size']),
                TextColumn::make('uploader.name')
                    ->label('Nahrál')
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin())
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Nahráno')
                    ->dateTime('j. n. Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('mime_group')
                    ->label('Skupina')
                    ->options([
                        'image' => 'Obrázky',
                        'video' => 'Video',
                        'document' => 'Dokumenty',
                        'other' => 'Ostatní',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => match ($data['value']) {
                            'image' => $query->where('mime_type', 'like', 'image/%'),
                            'video' => $query->where('mime_type', 'like', 'video/%'),
                            'document' => $query->where('mime_type', 'not like', 'image/%')->where('mime_type', 'not like', 'video/%'),
                            'other' => $query->whereNull('mime_type'),
                            default => $query,
                        },
                    )),
                SelectFilter::make('uploaded_by')
                    ->label('Nahrál')
                    ->relationship('uploader', 'name')
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin()),
            ])
            ->actions([
                EditAction::make(),
                Action::make('regenerateConversions')
                    ->label('Regenerovat konverze')
                    ->icon('fal-arrows-rotate')
                    ->color('gray')
                    ->visible(fn (Media $record): bool => $record->isImage())
                    ->authorize(fn (Media $record): bool => auth()->user()?->can('regenerateConversions', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Regenerovat konverze média?')
                    ->modalDescription('Konverze se zařadí do fronty na pozadí.')
                    ->action(fn (Media $record) => RegenerateMediaConversionsJob::dispatch([(int) $record->getKey()])),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('regenerateConversions')
                        ->label('Regenerovat konverze')
                        ->icon('fal-arrows-rotate')
                        ->authorize(fn (): bool => auth()->user()?->can('regenerateAllConversions', Media::class) ?? false)
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            RegenerateMediaConversionsJob::dispatch(
                                $records
                                    ->filter(fn (mixed $record): bool => $record instanceof Media && $record->isImage())
                                    ->map(fn (Media $record): int => (int) $record->getKey())
                                    ->values()
                                    ->all(),
                            );
                        }),
                ]),
            ]);
    }
}
