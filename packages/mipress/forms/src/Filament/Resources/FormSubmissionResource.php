<?php

declare(strict_types=1);

namespace MiPress\Forms\Filament\Resources;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use MiPress\Core\Enums\UserRole;
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['form', 'attachments']);
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return $query;
        }

        $userId = $user->getKey();

        return $query->whereHas('form', function (Builder $formQuery) use ($userId): void {
            $formQuery
                ->whereJsonContains('recipients', $userId)
                ->orWhereJsonContains('recipients', (string) $userId);
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('form_id')
                ->label('Formular')
                ->options(Form::query()->orderBy('title')->pluck('title', 'id')->all())
                ->disabled(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('form.title')
                ->label('Formular'),
            TextEntry::make('created_at')
                ->label('Odeslano')
                ->since(),
            TextEntry::make('is_read')
                ->label('Precitene')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Ano' : 'Ne'),
            TextEntry::make('ip_address')
                ->label('IP adresa')
                ->placeholder('-'),
            TextEntry::make('user_agent')
                ->label('User agent')
                ->placeholder('-')
                ->columnSpanFull(),
            TextEntry::make('submission_data')
                ->label('Data')
                ->state(fn (FormSubmission $record): string => collect($record->data ?? [])
                    ->map(fn (mixed $value, string $key): string => sprintf('%s: %s', $key, is_scalar($value) ? (string) $value : json_encode($value)))
                    ->implode("\n"))
                ->columnSpanFull(),
            TextEntry::make('attachments_links')
                ->label('Prilohy')
                ->state(fn (FormSubmission $record): string => $record->attachments
                    ->map(fn ($attachment): string => sprintf(
                        '<a href="%s" class="text-primary-600 underline">%s</a>',
                        route('mipress.form.attachments.download', ['submission' => $record, 'attachment' => $attachment]),
                        e($attachment->filename),
                    ))
                    ->implode('<br>'))
                ->html()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form.title')->label('Formular')->searchable(),
                TextColumn::make('created_at')->label('Odeslano')->since(),
                BadgeColumn::make('is_read')
                    ->label('Precitene')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ano' : 'Ne')
                    ->colors([
                        'success' => static fn (bool $state): bool => $state,
                        'warning' => static fn (bool $state): bool => ! $state,
                    ]),
                TextColumn::make('summary')
                    ->label('Shrnuti')
                    ->state(fn (FormSubmission $record): string => collect($record->data ?? [])
                        ->take(3)
                        ->map(fn (mixed $value, string $key): string => sprintf('%s: %s', $key, is_scalar($value) ? (string) $value : '[...]'))
                        ->implode(' | '))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('form_id')
                    ->label('Formular')
                    ->options(Form::query()->orderBy('title')->pluck('title', 'id')->all()),
            ])
            ->actions([
                Action::make('markRead')
                    ->label('Oznacit jako prectene')
                    ->visible(fn (FormSubmission $record): bool => ! $record->is_read)
                    ->action(function (FormSubmission $record): void {
                        $record->update([
                            'is_read' => true,
                            'read_by' => auth()->id(),
                            'read_at' => now(),
                        ]);
                    }),
                Action::make('markUnread')
                    ->label('Oznacit jako neprectene')
                    ->visible(fn (FormSubmission $record): bool => $record->is_read)
                    ->action(function (FormSubmission $record): void {
                        $record->update([
                            'is_read' => false,
                            'read_by' => null,
                            'read_at' => null,
                        ]);
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('markRead')
                        ->label('Oznacit jako prectene')
                        ->action(function ($records): void {
                            $records->each(function (FormSubmission $record): void {
                                $record->update([
                                    'is_read' => true,
                                    'read_by' => auth()->id(),
                                    'read_at' => now(),
                                ]);
                            });
                        }),
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
