<?php

declare(strict_types=1);

namespace MiPress\SocialFeeds\Mason\Bricks;

use Awcodes\Mason\Brick;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use MiPress\SocialFeeds\Enums\FeedLayout;
use MiPress\SocialFeeds\Models\SocialFeed;
use MiPress\SocialFeeds\Services\SocialFeedManager;

class SocialFeedBrick extends Brick
{
    public static function getId(): string
    {
        return 'social-feed';
    }

    public static function getLabel(): string
    {
        return 'Sociální feed';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-share';
    }

    public static function toHtml(array $config, ?array $data = null): ?string
    {
        $feed = null;

        if (! empty($config['feed_id'])) {
            $feed = SocialFeed::find($config['feed_id']);
        } elseif (! empty($config['feed_slug'])) {
            $feed = SocialFeed::where('slug', $config['feed_slug'])->first();
        }

        if (! $feed) {
            return null;
        }

        $posts = app(SocialFeedManager::class)->getFeedData($feed);
        $heading = $config['heading'] ?? null;
        $layoutOverride = $config['layout'] ?? null;

        if ($layoutOverride && $layout = FeedLayout::tryFrom($layoutOverride)) {
            $originalLayout = $feed->layout;
            $feed->layout = $layout;
        }

        $html = view('social-feeds::mason.bricks.social-feed.index', [
            'feed' => $feed,
            'posts' => $posts,
            'heading' => $heading,
        ])->render();

        if (isset($originalLayout)) {
            $feed->layout = $originalLayout;
        }

        return $html;
    }

    public static function configureBrickAction(Action $action): Action
    {
        return $action
            ->slideOver()
            ->schema([
                Select::make('feed_id')
                    ->label('Feed')
                    ->options(fn (): array => SocialFeed::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (SocialFeed $feed) => [
                            $feed->id => "{$feed->name} ({$feed->account?->name})",
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('heading')
                    ->label('Nadpis')
                    ->maxLength(140)
                    ->helperText('Volitelný nadpis nad feedem'),
                Select::make('layout')
                    ->label('Rozložení')
                    ->options(collect(FeedLayout::cases())->mapWithKeys(
                        fn (FeedLayout $l) => [$l->value => $l->label()]
                    )->all())
                    ->helperText('Ponechte prázdné pro výchozí rozložení feedu')
                    ->placeholder('Výchozí z feedu'),
            ]);
    }
}
