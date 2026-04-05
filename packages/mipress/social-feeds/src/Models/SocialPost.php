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
        $records = $posts->map(fn (array $post) => array_merge($post, [
            'social_feed_id' => $feed->id,
            'updated_at' => now(),
        ]))->all();

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
