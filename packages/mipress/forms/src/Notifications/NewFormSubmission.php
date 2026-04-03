<?php

declare(strict_types=1);

namespace MiPress\Forms\Notifications;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use MiPress\Forms\Filament\Resources\FormSubmissionResource;
use MiPress\Forms\Models\FormSubmission;

class NewFormSubmission extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FormSubmission $submission) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Nove odeslani formulare')
            ->body('Formular: '.$this->submission->form?->title)
            ->actions([
                Action::make('open')
                    ->label('Otevrit')
                    ->url(FormSubmissionResource::getUrl('view', ['record' => $this->submission]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
