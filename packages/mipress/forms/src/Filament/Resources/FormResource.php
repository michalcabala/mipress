<?php

declare(strict_types=1);

namespace MiPress\Forms\Filament\Resources;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use MiPress\Forms\Filament\Resources\FormResource\Pages\CreateForm;
use MiPress\Forms\Filament\Resources\FormResource\Pages\EditForm;
use MiPress\Forms\Filament\Resources\FormResource\Pages\ListForms;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormField;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Obsah';

    protected static ?string $modelLabel = 'Formular';

    protected static ?string $pluralModelLabel = 'Formulare';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Nazev')
                ->required()
                ->maxLength(255),
            TextInput::make('handle')
                ->label('Handle')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Toggle::make('is_active')
                ->label('Aktivni')
                ->default(true),
            Repeater::make('fields')
                ->label('Pole')
                ->schema([
                    TextInput::make('handle')->required(),
                    TextInput::make('label')->required(),
                    Select::make('type')
                        ->options(FormField::supportedTypes())
                        ->required(),
                    Toggle::make('required')->default(false),
                ])
                ->reorderable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Nazev')->searchable(),
                TextColumn::make('handle')->label('Handle')->searchable(),
                ToggleColumn::make('is_active')->label('Aktivni'),
                TextColumn::make('updated_at')->label('Upraveno')->since(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}
