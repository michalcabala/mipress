<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages\CreateMedia;
use App\Filament\Resources\MediaResource\Pages\EditMedia;
use App\Filament\Resources\MediaResource\Pages\ListMedia;
use App\Filament\Resources\MediaResource\Schemas\MediaForm;
use App\Filament\Resources\MediaResource\Tables\MediaTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use MiPress\Core\Models\Media;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|\BackedEnum|null $navigationIcon = 'fal-photo-film';

    protected static ?string $slug = 'media';

    protected static string|\UnitEnum|null $navigationGroup = 'Obsah';

    protected static ?string $modelLabel = 'Médium';

    protected static ?string $pluralModelLabel = 'Média';

    protected static ?int $navigationSort = 35;

    public static function form(Schema $schema): Schema
    {
        return MediaForm::form($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::table($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->libraryItems()
            ->with('uploader');

        $user = auth()->user();

        if ($user?->isContributor()) {
            $query->where('uploaded_by', $user->getKey());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
