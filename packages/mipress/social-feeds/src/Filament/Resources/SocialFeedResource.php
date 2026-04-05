<?php

namespace MiPress\SocialFeeds\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use MiPress\SocialFeeds\Enums\FeedLayout;
use MiPress\SocialFeeds\Filament\Resources\SocialFeedResource\Pages\CreateSocialFeed;
use MiPress\SocialFeeds\Filament\Resources\SocialFeedResource\Pages\EditSocialFeed;
use MiPress\SocialFeeds\Filament\Resources\SocialFeedResource\Pages\ListSocialFeeds;
use MiPress\SocialFeeds\Jobs\RefreshFeedJob;
use MiPress\SocialFeeds\Models\SocialAccount;
use MiPress\SocialFeeds\Models\SocialFeed;

class SocialFeedResource extends Resource
{
    protected static ?string $model = SocialFeed::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rss';

    protected static string|\UnitEnum|null $navigationGroup = 'Sociální sítě';

    protected static ?string $navigationLabel = 'Feedy';

    protected static ?string $modelLabel = 'Feed';

    protected static ?string $pluralModelLabel = 'Feedy';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Základní nastavení')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Název feedu')
                        ->placeholder('např. FB feed v patičce')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->placeholder('automaticky z názvu')
                        ->unique(ignoreRecord: true)
                        ->helperText('Použije se v Blade komponentě: <x-social-feed slug="..." />'),

                    Forms\Components\Select::make('social_account_id')
                        ->label('Propojený účet')
                        ->options(
                            SocialAccount::all()->mapWithKeys(fn (SocialAccount $a) => [
                                $a->id => "{$a->platform->label()}: {$a->name}",
                            ])
                        )
                        ->required()
                        ->searchable(),

                    Forms\Components\Select::make('feed_type')
                        ->label('Typ feedu')
                        ->options([
                            'timeline' => 'Časová osa (výchozí)',
                            'feed' => 'Feed (vč. příspěvků ostatních)',
                            'visitor_posts' => 'Příspěvky návštěvníků',
                        ])
                        ->default('timeline'),
                ])->columns(2),

            Section::make('Zobrazení')
                ->schema([
                    Forms\Components\Select::make('layout')
                        ->label('Layout')
                        ->options(
                            collect(FeedLayout::cases())
                                ->mapWithKeys(fn (FeedLayout $l) => [$l->value => $l->label()])
                        )
                        ->default('list')
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('posts_count')
                        ->label('Počet příspěvků')
                        ->numeric()
                        ->default(5)
                        ->minValue(1)
                        ->maxValue(50),

                    Forms\Components\TextInput::make('cache_ttl')
                        ->label('Cache TTL (sekundy)')
                        ->numeric()
                        ->default(fn () => config('social-feeds.cache.default_ttl', 3600))
                        ->helperText('3600 = 1 hodina, 86400 = 1 den'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktivní')
                        ->default(true),
                ])->columns(2),

            Section::make('Rozšířená nastavení')
                ->schema([
                    Forms\Components\KeyValue::make('settings')
                        ->label('Nastavení layoutu')
                        ->helperText('Volitelné klíč-hodnota parametry pro šablonu (columns, gap, show_avatar…)'),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Účet')
                    ->description(fn (SocialFeed $record) => $record->account?->platform->label()),

                Tables\Columns\TextColumn::make('layout')
                    ->label('Layout')
                    ->formatStateUsing(fn (FeedLayout $state) => $state->label())
                    ->badge(),

                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Příspěvků')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktivní')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualizováno')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Action::make('refresh')
                    ->label('Obnovit')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (SocialFeed $record) {
                        RefreshFeedJob::dispatch($record->id);
                        Notification::make()
                            ->title('Refresh feedu zařazen do fronty')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                BulkAction::make('refresh_all')
                    ->label('Obnovit vybrané')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Collection $records) {
                        $records->each(fn (SocialFeed $feed) => RefreshFeedJob::dispatch($feed->id));
                        Notification::make()
                            ->title("Refresh {$records->count()} feedů zařazen do fronty")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialFeeds::route('/'),
            'create' => CreateSocialFeed::route('/create'),
            'edit' => EditSocialFeed::route('/{record}/edit'),
        ];
    }
}
