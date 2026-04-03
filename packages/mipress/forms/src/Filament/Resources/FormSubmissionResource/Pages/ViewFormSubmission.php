<?php

declare(strict_types=1);

namespace MiPress\Forms\Filament\Resources\FormSubmissionResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use MiPress\Forms\Filament\Resources\FormSubmissionResource;

class ViewFormSubmission extends ViewRecord
{
    protected static string $resource = FormSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markRead')
                ->label('Oznacit jako prectene')
                ->visible(fn (): bool => ! $this->record->is_read)
                ->action(function (): void {
                    $this->record->update([
                        'is_read' => true,
                        'read_by' => auth()->id(),
                        'read_at' => now(),
                    ]);
                }),
            Action::make('markUnread')
                ->label('Oznacit jako neprectene')
                ->visible(fn (): bool => (bool) $this->record->is_read)
                ->action(function (): void {
                    $this->record->update([
                        'is_read' => false,
                        'read_by' => null,
                        'read_at' => null,
                    ]);
                }),
            DeleteAction::make(),
        ];
    }
}
