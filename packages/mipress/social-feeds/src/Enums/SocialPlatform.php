<?php

namespace MiPress\SocialFeeds\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case X = 'x';
    case YouTube = 'youtube';
    case TikTok = 'tiktok';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::X => 'X (Twitter)',
            self::YouTube => 'YouTube',
            self::TikTok => 'TikTok',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Facebook => 'fab-facebook',
            self::Instagram => 'fab-instagram',
            self::X => 'fab-x-twitter',
            self::YouTube => 'fab-youtube',
            self::TikTok => 'fab-tiktok',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Facebook => '#1877F2',
            self::Instagram => '#E4405F',
            self::X => '#000000',
            self::YouTube => '#FF0000',
            self::TikTok => '#000000',
        };
    }

    /**
     * Vrátí pouze platformy, které mají enabled=true v konfiguraci.
     *
     * @return array<int, self>
     */
    public static function enabled(): array
    {
        return collect(self::cases())
            ->filter(fn (self $p) => config("social-feeds.providers.{$p->value}.enabled", false))
            ->values()
            ->all();
    }
}
