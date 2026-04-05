<?php

namespace MiPress\SocialFeeds\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Models\SocialAccount;

interface SocialProvider
{
    public function platform(): SocialPlatform;

    public function authDriver(): string;

    /**
     * @return array<int, string>
     */
    public function requiredScopes(): array;

    /**
     * @return Collection<int, array{
     *     platform_post_id: string,
     *     post_type: ?string,
     *     content: ?string,
     *     media: ?array,
     *     engagement: ?array,
     *     author_name: ?string,
     *     author_avatar_url: ?string,
     *     permalink: ?string,
     *     posted_at: ?Carbon,
     *     raw_data: ?array,
     * }>
     */
    public function fetchPosts(SocialAccount $account, array $options = []): Collection;

    /**
     * @return array<string, mixed>
     */
    public function fetchProfile(SocialAccount $account): array;

    public function validateToken(SocialAccount $account): bool;
}
