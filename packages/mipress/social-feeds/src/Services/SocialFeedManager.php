<?php

namespace MiPress\SocialFeeds\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use MiPress\SocialFeeds\Contracts\SocialProvider;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Models\SocialFeed;
use MiPress\SocialFeeds\Models\SocialPost;
use MiPress\SocialFeeds\Providers\FacebookProvider;

class SocialFeedManager
{
    public function resolve(SocialPlatform $platform): SocialProvider
    {
        return match ($platform) {
            SocialPlatform::Facebook => app(FacebookProvider::class),
            default => throw new \InvalidArgumentException(
                "Provider pro platformu [{$platform->value}] není implementován."
            ),
        };
    }

    public function getFeedData(SocialFeed $feed): Collection
    {
        if (! $feed->is_active) {
            return collect();
        }

        $store = config('social-feeds.cache.store');
        $cacheStore = Cache::store($store);

        $cached = $cacheStore->get($feed->cacheKey());

        if ($cached instanceof Collection) {
            return $cached;
        }

        // Cache miss or corrupted entry (e.g. __PHP_Incomplete_Class)
        $cacheStore->forget($feed->cacheKey());

        $data = $this->fetchAndPersist($feed);

        $cacheStore->put($feed->cacheKey(), $data, $feed->cache_ttl);

        return $data;
    }

    public function refreshFeed(SocialFeed $feed): Collection
    {
        $store = config('social-feeds.cache.store');
        Cache::store($store)->forget($feed->cacheKey());

        return $this->getFeedData($feed);
    }

    public function clearCache(SocialFeed $feed): void
    {
        $store = config('social-feeds.cache.store');
        Cache::store($store)->forget($feed->cacheKey());
    }

    private function fetchAndPersist(SocialFeed $feed): Collection
    {
        $feed->loadMissing('account');
        $account = $feed->account;

        if (! $account || $account->isTokenExpired()) {
            return $this->getBackupPosts($feed);
        }

        $provider = $this->resolve($account->platform);

        $posts = $provider->fetchPosts($account, [
            'posts_count' => $feed->posts_count,
            'feed_type' => $feed->feed_type,
            ...(array) $feed->settings,
        ]);

        if ($posts->isEmpty()) {
            return $this->getBackupPosts($feed);
        }

        SocialPost::upsertFromApi($feed, $posts);

        return $posts;
    }

    private function getBackupPosts(SocialFeed $feed): Collection
    {
        return $feed->posts()
            ->orderByDesc('posted_at')
            ->limit($feed->posts_count)
            ->get()
            ->map(fn (SocialPost $post) => $post->only([
                'platform_post_id', 'post_type', 'content', 'media',
                'engagement', 'author_name', 'author_avatar_url',
                'permalink', 'posted_at', 'raw_data',
            ]));
    }
}
