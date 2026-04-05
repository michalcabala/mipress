<?php

namespace MiPress\SocialFeeds\Providers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Models\SocialAccount;

class FacebookProvider extends AbstractProvider
{
    public function platform(): SocialPlatform
    {
        return SocialPlatform::Facebook;
    }

    public function authDriver(): string
    {
        return 'facebook';
    }

    public function requiredScopes(): array
    {
        return array_filter(config('social-feeds.providers.facebook.scopes', [
            'public_profile',
        ]));
    }

    public function fetchPosts(SocialAccount $account, array $options = []): Collection
    {
        $version = config('social-feeds.providers.facebook.graph_version', 'v23.0');
        $limit = $options['posts_count'] ?? 5;
        $feedType = $options['feed_type'] ?? 'timeline';

        $endpoint = match ($feedType) {
            'feed' => 'feed',
            'visitor_posts' => 'visitor_posts',
            default => 'posts',
        };

        $fields = implode(',', [
            'id', 'message', 'story', 'created_time', 'updated_time',
            'full_picture', 'picture', 'status_type', 'permalink_url',
            'shares', 'from{id,name,picture{url}}',
            'attachments{title,description,media_type,unshimmed_url,media{image,source},subattachments}',
            'likes.summary(true)',
            'comments.summary(true)',
            'reactions.summary(true)',
        ]);

        $result = $this->safeApiCall($account, function () use ($account, $version, $endpoint, $fields, $limit) {
            $pageId = $account->platform_account_id;
            $response = $this->httpClient($account)
                ->get("https://graph.facebook.com/{$version}/{$pageId}/{$endpoint}", [
                    'fields' => $fields,
                    'limit' => $limit,
                ]);

            return $response->json('data', []);
        });

        if (! $result) {
            return collect();
        }

        return collect($result)->map(function (array $post) {
            return [
                'platform_post_id' => $post['id'],
                'post_type' => $post['status_type'] ?? $this->resolvePostType($post),
                'content' => $post['message'] ?? $post['story'] ?? null,
                'media' => $this->extractMedia($post),
                'engagement' => [
                    'likes' => $post['likes']['summary']['total_count'] ?? 0,
                    'comments' => $post['comments']['summary']['total_count'] ?? 0,
                    'shares' => $post['shares']['count'] ?? 0,
                    'reactions' => $post['reactions']['summary']['total_count'] ?? 0,
                ],
                'author_name' => $post['from']['name'] ?? null,
                'author_avatar_url' => $post['from']['picture']['data']['url'] ?? null,
                'permalink' => $post['permalink_url'] ?? null,
                'posted_at' => isset($post['created_time']) ? Carbon::parse($post['created_time']) : null,
                'raw_data' => $post,
            ];
        });
    }

    public function fetchProfile(SocialAccount $account): array
    {
        $version = config('social-feeds.providers.facebook.graph_version', 'v23.0');

        $result = $this->safeApiCall($account, function () use ($account, $version) {
            $pageId = $account->platform_account_id;
            $response = $this->httpClient($account)
                ->get("https://graph.facebook.com/{$version}/{$pageId}", [
                    'fields' => 'id,name,about,picture{url},cover{source},fan_count,link',
                ]);

            return $response->json();
        });

        return $result ?? [];
    }

    public function validateToken(SocialAccount $account): bool
    {
        $version = config('social-feeds.providers.facebook.graph_version', 'v23.0');

        $result = $this->safeApiCall($account, function () use ($account, $version) {
            $response = $this->httpClient($account)
                ->get("https://graph.facebook.com/{$version}/debug_token", [
                    'input_token' => $account->decrypted_token,
                ]);

            $data = $response->json('data', []);

            return ! empty($data['is_valid']);
        });

        return (bool) $result;
    }

    // ── Private helpers ──

    private function resolvePostType(array $post): string
    {
        $attachments = $post['attachments']['data'] ?? [];
        $firstAttachment = $attachments[0] ?? null;

        if (! $firstAttachment) {
            return 'text';
        }

        return match ($firstAttachment['media_type'] ?? null) {
            'photo' => 'photo',
            'video' => 'video',
            'link' => 'link',
            'album' => 'album',
            default => 'text',
        };
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function extractMedia(array $post): ?array
    {
        $media = [];

        if (! empty($post['full_picture'])) {
            $media[] = [
                'type' => 'image',
                'url' => $post['full_picture'],
                'thumbnail_url' => $post['picture'] ?? null,
            ];
        }

        $attachments = $post['attachments']['data'] ?? [];
        foreach ($attachments as $attachment) {
            $subs = $attachment['subattachments']['data'] ?? [];
            foreach ($subs as $sub) {
                if (isset($sub['media']['image']['src'])) {
                    $media[] = [
                        'type' => $sub['media_type'] ?? 'image',
                        'url' => $sub['media']['image']['src'],
                        'width' => $sub['media']['image']['width'] ?? null,
                        'height' => $sub['media']['image']['height'] ?? null,
                    ];
                }
            }

            if (isset($attachment['media']['source'])) {
                $media[] = [
                    'type' => 'video',
                    'url' => $attachment['media']['source'],
                    'thumbnail_url' => $attachment['media']['image']['src'] ?? null,
                ];
            }
        }

        return ! empty($media) ? $media : null;
    }
}
