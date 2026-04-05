<?php

namespace MiPress\SocialFeeds\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use MiPress\SocialFeeds\Models\SocialFeed;
use MiPress\SocialFeeds\Services\SocialFeedManager;

class RefreshFeedJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $feedId,
    ) {
        $this->onQueue(config('social-feeds.refresh.queue', 'default'));
    }

    public function handle(SocialFeedManager $manager): void
    {
        $feed = SocialFeed::with('account')->find($this->feedId);

        if (! $feed || ! $feed->is_active) {
            return;
        }

        $manager->refreshFeed($feed);
    }
}
