<?php

namespace MiPress\SocialFeeds\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use MiPress\SocialFeeds\Models\SocialFeed;

class RefreshAllFeedsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        SocialFeed::active()
            ->with('account')
            ->each(function (SocialFeed $feed) {
                RefreshFeedJob::dispatch($feed->id)
                    ->delay(now()->addSeconds(rand(0, 30)));
            });
    }
}
