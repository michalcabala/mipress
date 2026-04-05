<?php

namespace MiPress\SocialFeeds\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Filament\Resources\SocialAccountResource\Pages\EditSocialAccount;
use MiPress\SocialFeeds\Filament\Resources\SocialAccountResource\Pages\ListSocialAccounts;
use MiPress\SocialFeeds\Models\SocialAccount;
use MiPress\SocialFeeds\Services\SocialFeedManager;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Sociální sítě';

    protected static ?string $navigationLabel = 'Propojené účty';

    protected static ?string $modelLabel = 'Propojený účet';

    protected static ?string $pluralModelLabel = 'Propojené účty';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informace o účtu')
                ->schema([
                    Forms\Components\Select::make('platform')
                        ->label('Platforma')
                        ->options(
                            collect(SocialPlatform::enabled())
                                ->mapWithKeys(fn (SocialPlatform $p) => [$p->value => $p->label()])
                        )
                        ->required()
                        ->disabled(fn (?SocialAccount $record) => $record !== null),

                    Forms\Components\TextInput::make('name')
                        ->label('Název účtu / stránky')
                        ->required()
                        ->disabled(),

                    Forms\Components\TextInput::make('username')
                        ->label('Uživatelské jméno')
                        ->disabled(),

                    Forms\Components\TextInput::make('platform_account_id')
                        ->label('ID na platformě')
                        ->disabled(),
                ]),

            Section::make('Stav tokenu')
                ->schema([
                    Forms\Components\Placeholder::make('token_status')
                        ->label('Stav')
                        ->content(function (?SocialAccount $record) {
                            if (! $record) {
                                return '—';
                            }
                            if ($record->isTokenExpired()) {
                                return '❌ Token vypršel';
                            }
                            if ($record->isTokenExpiringSoon()) {
                                return '⚠️ Token brzy vyprší';
                            }

                            return '✅ Token platný';
                        }),

                    Forms\Components\Placeholder::make('token_expires_at')
                        ->label('Vyprší')
                        ->content(fn (?SocialAccount $record) => $record?->token_expires_at?->format('d.m.Y H:i') ?? 'Bez expirace'),

                    Forms\Components\Placeholder::make('last_verified_at')
                        ->label('Poslední ověření')
                        ->content(fn (?SocialAccount $record) => $record?->last_verified_at?->diffForHumans() ?? 'Neověřeno'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Platforma')
                    ->badge()
                    ->formatStateUsing(fn (SocialPlatform $state) => $state->label())
                    ->color(fn (SocialPlatform $state) => match ($state) {
                        SocialPlatform::Facebook => 'info',
                        SocialPlatform::Instagram => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('feeds_count')
                    ->label('Feedů')
                    ->counts('feeds')
                    ->sortable(),

                Tables\Columns\IconColumn::make('token_valid')
                    ->label('Token')
                    ->state(fn (SocialAccount $record) => ! $record->isTokenExpired())
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_verified_at')
                    ->label('Ověřeno')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Action::make('verify')
                    ->label('Ověřit token')
                    ->icon('heroicon-o-shield-check')
                    ->action(function (SocialAccount $record) {
                        $manager = app(SocialFeedManager::class);
                        $provider = $manager->resolve($record->platform);
                        $valid = $provider->validateToken($record);

                        if ($valid) {
                            Notification::make()
                                ->title('Token je platný')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Token je neplatný — připojte účet znovu')
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->label('Odpojit'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialAccounts::route('/'),
            'edit' => EditSocialAccount::route('/{record}/edit'),
        ];
    }
}
