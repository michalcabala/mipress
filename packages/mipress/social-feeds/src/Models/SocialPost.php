<?php

namespace MiPress\SocialFeeds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SocialPost extends Model
{
    protected $fillable = [
        'social_feed_id',
        'platform_post_id',
        'post_type',
        'content',
        'media',
        'engagement',
        'author_name',
        'author_avatar_url',
        'permalink',
        'posted_at',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'media' => 'array',
            'engagement' => 'array',
            'posted_at' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    /**
     * Hromadný upsert příspěvků z API response.
     */
    public static function upsertFromApi(SocialFeed $feed, Collection $posts): void
    {
        $records = $posts->map(fn (array $post) => [
            'social_feed_id' => $feed->id,
            'platform_post_id' => $post['platform_post_id'],
            'post_type' => $post['post_type'] ?? null,
            'content' => $post['content'] ?? null,
            'media' => json_encode($post['media'] ?? []),
            'engagement' => json_encode($post['engagement'] ?? []),
            'author_name' => $post['author_name'] ?? null,
            'author_avatar_url' => $post['author_avatar_url'] ?? null,
            'permalink' => $post['permalink'] ?? null,
            'posted_at' => $post['posted_at'] ?? null,
            'raw_data' => json_encode($post['raw_data'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if (empty($records)) {
            return;
        }

        self::upsert(
            $records,
            ['social_feed_id', 'platform_post_id'],
            ['post_type', 'content', 'media', 'engagement', 'author_name',
                'author_avatar_url', 'permalink', 'posted_at', 'raw_data', 'updated_at']
        );

        $currentIds = $posts->pluck('platform_post_id')->all();
        if (! empty($currentIds)) {
            self::where('social_feed_id', $feed->id)
                ->whereNotIn('platform_post_id', $currentIds)
                ->where('updated_at', '<', now()->subDays(7))
                ->delete();
        }
    }

    // ── Relationships ──

    public function feed(): BelongsTo
    {
        return $this->belongsTo(SocialFeed::class, 'social_feed_id');
    }
}
