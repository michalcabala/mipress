<?php

namespace MiPress\SocialFeeds\Filament\Resources\SocialFeedResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use MiPress\SocialFeeds\Filament\Resources\SocialFeedResource;
use MiPress\SocialFeeds\Filament\Widgets\FeedPreviewWidget;
use MiPress\SocialFeeds\Jobs\RefreshFeedJob;

class EditSocialFeed extends EditRecord
{
    protected static string $resource = SocialFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Obnovit feed')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    RefreshFeedJob::dispatchSync($this->record->id);
                    Notification::make()
                        ->title('Feed byl obnoven')
                        ->success()
                        ->send();

                    $this->redirect(static::$resource::getUrl('edit', ['record' => $this->record]));
                }),
            DeleteAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            FeedPreviewWidget::class,
        ];
    }
}
