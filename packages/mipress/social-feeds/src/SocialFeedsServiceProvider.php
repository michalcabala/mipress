<?php

namespace MiPress\SocialFeeds;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use MiPress\SocialFeeds\Jobs\RefreshAllFeedsJob;
use MiPress\SocialFeeds\Mason\Bricks\SocialFeedBrick;
use MiPress\SocialFeeds\Services\SocialFeedManager;
use MiPress\SocialFeeds\View\Components\SocialFeed;

class SocialFeedsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/social-feeds.php', 'social-feeds');

        $this->app->singleton(SocialFeedManager::class);

        $this->app->singleton('mipress.social-feeds.mason.bricks', fn (): array => [
            SocialFeedBrick::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'social-feeds');

        Blade::component('social-feed', SocialFeed::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/social-feeds.php' => config_path('social-feeds.php'),
            ], 'social-feeds-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/social-feeds'),
            ], 'social-feeds-views');
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $frequency = config('social-feeds.refresh.schedule', 'hourly');
            $schedule->job(new RefreshAllFeedsJob)->{$frequency}();
        });

        $this->configureSocialite();
    }

    private function configureSocialite(): void
    {
        $providers = config('social-feeds.providers', []);

        foreach ($providers as $driver => $providerConfig) {
            if (! ($providerConfig['enabled'] ?? false)) {
                continue;
            }

            $key = "services.{$driver}";

            if (! config($key)) {
                config([
                    "{$key}.client_id" => $providerConfig['client_id'] ?? null,
                    "{$key}.client_secret" => $providerConfig['client_secret'] ?? null,
                    "{$key}.redirect" => url("mpcp/social/callback/{$driver}"),
                ]);
            }
        }
    }
}
