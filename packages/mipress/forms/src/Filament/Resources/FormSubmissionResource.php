<?php

declare(strict_types=1);

namespace MiPress\Forms\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use MiPress\Forms\Filament\Resources\FormSubmissionResource\Pages\ListFormSubmissions;
use MiPress\Forms\Filament\Resources\FormSubmissionResource\Pages\ViewFormSubmission;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormSubmission;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Obsah';

    protected static ?string $modelLabel = 'Odeslani formulare';

    protected static ?string $pluralModelLabel = 'Odeslani formularu';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('form_id')
                ->label('Formular')
                ->options(Form::query()->orderBy('title')->pluck('title', 'id')->all())
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form.title')->label('Formular')->searchable(),
                TextColumn::make('created_at')->label('Odeslano')->since(),
                TextColumn::make('is_read')->label('Precitene')->badge(),
            ])
            ->filters([
                SelectFilter::make('form_id')
                    ->label('Formular')
                    ->options(Form::query()->orderBy('title')->pluck('title', 'id')->all()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'view' => ViewFormSubmission::route('/{record}'),
        ];
    }
}
