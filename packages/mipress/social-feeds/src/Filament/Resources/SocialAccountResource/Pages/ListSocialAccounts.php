<?php

namespace MiPress\SocialFeeds\Filament\Resources\SocialAccountResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Filament\Resources\SocialAccountResource;

class ListSocialAccounts extends ListRecords
{
    protected static string $resource = SocialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return collect(SocialPlatform::enabled())->map(
            fn (SocialPlatform $p) => Action::make("connect_{$p->value}")
                ->label("Připojit {$p->label()}")
                ->icon('heroicon-o-plus-circle')
                ->url(route('social.auth.redirect', $p->value))
        )->all();
    }
}
