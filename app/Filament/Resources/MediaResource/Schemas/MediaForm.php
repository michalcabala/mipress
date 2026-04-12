<?php

declare(strict_types=1);

namespace App\Filament\Resources\MediaResource\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Models\Media;

class MediaForm
{
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make([
                'default' => 1,
                'xl' => 3,
            ])->schema([
                Grid::make(1)
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 2,
                    ])
                    ->schema([
                        Section::make('Soubor')
                            ->schema([
                                FileUpload::make('upload')
                                    ->label('Nahrát soubor')
                                    ->disk(MediaConfig::disk())
                                    ->directory('tmp/resource')
                                    ->visibility('public')
                                    ->preserveFilenames()
                                    ->imageEditor()
                                    ->imageEditorMode(2)
                                    ->imageEditorAspectRatioOptions([
                                        null,
                                        '1:1',
                                        '4:3',
                                        '16:9',
                                        '1200:630',
                                    ])
                                    ->acceptedFileTypes(MediaConfig::allowedMimeTypes())
                                    ->maxSize((int) floor(MediaConfig::maxUploadSize() / 1024))
                                    ->helperText('U obrázků můžete použít editor pro ořez a rotaci.')
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->visible(fn (string $operation): bool => $operation === 'create'),
                                Hidden::make('focal_point_x')
                                    ->default(50),
                                Hidden::make('focal_point_y')
                                    ->default(50),
                            ]),

                        Section::make('Metadata')
                            ->schema([
                                TextInput::make('alt')
                                    ->label('Alternativní text')
                                    ->maxLength(255),
                                TextInput::make('title')
                                    ->label('Titulek')
                                    ->maxLength(255),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 1,
                    ])
                    ->schema([
                        Section::make('Informace')
                            ->schema([
                                Placeholder::make('mime_type_info')
                                    ->label('Typ')
                                    ->content(fn (?Media $record): string => $record?->mime_type ?? '—'),
                                Placeholder::make('size_info')
                                    ->label('Velikost')
                                    ->content(fn (?Media $record): string => $record?->human_readable_size ?? '—'),
                                Placeholder::make('dimensions_info')
                                    ->label('Rozměry')
                                    ->content(function (?Media $record): string {
                                        if (! $record instanceof Media || ! $record->isImage()) {
                                            return '—';
                                        }

                                        $width = $record->getCustomProperty('width');
                                        $height = $record->getCustomProperty('height');

                                        return (is_numeric($width) && is_numeric($height))
                                            ? "{$width} × {$height} px"
                                            : '—';
                                    }),
                                Placeholder::make('uploaded_at_info')
                                    ->label('Nahráno')
                                    ->content(fn (?Media $record): string => $record?->created_at?->format('j. n. Y H:i') ?? '—'),
                                Placeholder::make('uploader_info')
                                    ->label('Nahrál')
                                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin())
                                    ->content(fn (?Media $record): string => $record?->uploader?->name ?? '—'),
                            ]),
                    ]),
            ]),

            Section::make('Focal point a přehled konverzí')
                ->columnSpanFull()
                ->visible(fn (?Media $record): bool => $record?->isImage() ?? false)
                ->schema([
                    ViewField::make('focal_point_preview')
                        ->hiddenLabel()
                        ->dehydrated(false)
                        ->view('mipress::filament.forms.components.media-focal-point-field'),
                ]),
        ]);
    }
}
