<?php

namespace MiPress\SocialFeeds\Enums;

enum FeedLayout: string
{
    case List = 'list';
    case Grid = 'grid';
    case Masonry = 'masonry';
    case Carousel = 'carousel';

    public function label(): string
    {
        return match ($this) {
            self::List => 'Seznam',
            self::Grid => 'Mřížka',
            self::Masonry => 'Masonry',
            self::Carousel => 'Karusel',
        };
    }
}
