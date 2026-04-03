<?php

declare(strict_types=1);

namespace MiPress\Forms\Filament\Resources;

use App\Models\User;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use MiPress\Core\Enums\UserRole;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount([
            'submissions as unread_submissions_count' => fn (Builder $query): Builder => $query->where('is_read', false),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Zakladni nastaveni')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('template')
                                ->label('Zacit ze sablony')
                                ->visibleOn('create')
                                ->dehydrated(false)
                                ->default('none')
                                ->options([
                                    'none' => 'Prazdny formular',
                                    'contact' => 'Kontaktni formular',
                                ]),
                            Toggle::make('is_active')
                                ->label('Aktivni')
                                ->default(true),
                            TextInput::make('title')
                                ->label('Nazev')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, callable $set, string $operation): void {
                                    if ($operation !== 'create' || blank($state)) {
                                        return;
                                    }

                                    $set('handle', Str::slug($state));
                                }),
                            TextInput::make('handle')
                                ->label('Handle')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->disabledOn('edit')
                                ->dehydrated(static fn (string $operation): bool => $operation === 'create'),
                        ]),
                    Textarea::make('description')
                        ->label('Popis')
                        ->rows(3),
                ]),

            Section::make('Pole formulare')
                ->schema([
                    Repeater::make('fields')
                        ->label('Pole')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('handle')
                                        ->label('Handle')
                                        ->required(),
                                    TextInput::make('label')
                                        ->label('Label')
                                        ->required(),
                                    Select::make('type')
                                        ->label('Typ')
                                        ->options(FormField::supportedTypes())
                                        ->live()
                                        ->required(),
                                    Toggle::make('required')
                                        ->label('Povinne')
                                        ->default(false),
                                    TextInput::make('order')
                                        ->label('Poradi')
                                        ->numeric()
                                        ->default(0),
                                    TextInput::make('config.placeholder')
                                        ->label('Placeholder')
                                        ->visible(fn (Get $get): bool => in_array((string) $get('type'), [
                                            FormField::TYPE_TEXT,
                                            FormField::TYPE_EMAIL,
                                            FormField::TYPE_PHONE,
                                            FormField::TYPE_TEXTAREA,
                                            FormField::TYPE_SELECT,
                                        ], true)),
                                    TextInput::make('config.max_length')
                                        ->label('Max delka')
                                        ->numeric()
                                        ->visible(fn (Get $get): bool => (string) $get('type') === FormField::TYPE_TEXT),
                                    TextInput::make('config.rows')
                                        ->label('Pocet radku')
                                        ->numeric()
                                        ->visible(fn (Get $get): bool => (string) $get('type') === FormField::TYPE_TEXTAREA),
                                    TextInput::make('config.max_size_mb')
                                        ->label('Max velikost (MB)')
                                        ->numeric()
                                        ->visible(fn (Get $get): bool => (string) $get('type') === FormField::TYPE_FILE),
                                    TextInput::make('config.accepted')
                                        ->label('Povolene pripony')
                                        ->helperText('.pdf,.jpg,.png')
                                        ->visible(fn (Get $get): bool => (string) $get('type') === FormField::TYPE_FILE),
                                    TextInput::make('config.value')
                                        ->label('Skryta hodnota')
                                        ->visible(fn (Get $get): bool => (string) $get('type') === FormField::TYPE_HIDDEN),
                                    KeyValue::make('config.options')
                                        ->label('Moznosti')
                                        ->keyLabel('Klic')
                                        ->valueLabel('Label')
                                        ->visible(fn (Get $get): bool => in_array((string) $get('type'), [
                                            FormField::TYPE_SELECT,
                                            FormField::TYPE_RADIO,
                                        ], true))
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->reorderable()
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

            Section::make('Prijemci')
                ->schema([
                    Select::make('recipients')
                        ->label('Upozornit uzivatele')
                        ->multiple()
                        ->searchable()
                        ->options(fn (): array => User::query()
                            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', [
                                UserRole::SuperAdmin->value,
                                UserRole::Admin->value,
                                UserRole::Editor->value,
                            ]))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()),
                ]),

            Section::make('Ochrana proti spamu')
                ->schema([
                    Select::make('spam_protection')
                        ->label('Rezim')
                        ->options(FormField::spamModes())
                        ->default(FormField::SPAM_HONEYPOT)
                        ->live()
                        ->required(),
                    TextInput::make('recaptcha_site_key')
                        ->label('reCAPTCHA Site Key')
                        ->visible(fn (Get $get): bool => in_array((string) $get('spam_protection'), [
                            FormField::SPAM_RECAPTCHA,
                            FormField::SPAM_BOTH,
                        ], true)),
                    TextInput::make('recaptcha_secret_key')
                        ->label('reCAPTCHA Secret Key')
                        ->password()
                        ->revealable()
                        ->visible(fn (Get $get): bool => in_array((string) $get('spam_protection'), [
                            FormField::SPAM_RECAPTCHA,
                            FormField::SPAM_BOTH,
                        ], true)),
                ]),

            Section::make('Automaticka odpoved')
                ->schema([
                    Toggle::make('auto_reply_enabled')
                        ->label('Zapnout auto-reply')
                        ->live(),
                    TextInput::make('auto_reply_subject')
                        ->label('Predmet')
                        ->visible(fn (Get $get): bool => (bool) $get('auto_reply_enabled')),
                    Textarea::make('auto_reply_body')
                        ->label('Text zpravy')
                        ->rows(5)
                        ->visible(fn (Get $get): bool => (bool) $get('auto_reply_enabled')),
                ]),

            Section::make('Potvrzovaci zprava')
                ->schema([
                    Textarea::make('success_message')
                        ->label('Zprava po odeslani')
                        ->default('Dekujeme, formular byl odeslan.')
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Nazev')->searchable(),
                TextColumn::make('handle')->label('Handle')->searchable(),
                TextColumn::make('unread_submissions_count')
                    ->label('Neprectene')
                    ->badge()
                    ->color('warning'),
                BadgeColumn::make('is_active')
                    ->label('Aktivni')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ano' : 'Ne')
                    ->colors([
                        'success' => static fn (bool $state): bool => $state,
                        'gray' => static fn (bool $state): bool => ! $state,
                    ]),
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
