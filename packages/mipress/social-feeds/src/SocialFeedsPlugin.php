<?php

namespace MiPress\SocialFeeds;

use Filament\Contracts\Plugin;
use Filament\Panel;
use MiPress\SocialFeeds\Filament\Pages\SelectFacebookPages;
use MiPress\SocialFeeds\Filament\Resources\SocialAccountResource;
use MiPress\SocialFeeds\Filament\Resources\SocialFeedResource;

class SocialFeedsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'social-feeds';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SocialAccountResource::class,
            SocialFeedResource::class,
        ]);

        $panel->pages([
            SelectFacebookPages::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
