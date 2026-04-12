<?php

namespace App\Providers;

use Awcodes\Botly\Models\Botly;
use Awcodes\Curator\Curations\CurationPreset;
use Awcodes\Curator\Facades\Curation;
use Awcodes\Curator\Facades\Glide;
use Awcodes\Curator\Glide\SymfonyResponseFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use MiPress\Core\Policies\BotlyPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Botly::class, BotlyPolicy::class);

        Glide::configure()->serverConfig([
            'response' => new SymfonyResponseFactory(app('request')),
            'source' => storage_path('app'),
            'source_path_prefix' => 'public/uploads',
            'cache' => storage_path('app'),
            'cache_path_prefix' => '.cache',
            'max_image_size' => 2000 * 2000,
            'base_url' => 'curator',
        ]);

        Curation::presets([
            CurationPreset::make('Miniatura')
                ->width(400)
                ->height(400)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Open Graph')
                ->width(1200)
                ->height(630)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Čtverec')
                ->width(1200)
                ->height(1200)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Krajina 16:9')
                ->width(1600)
                ->height(900)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Krajina 4:3')
                ->width(1600)
                ->height(height: 1200)
                ->format('webp')
                ->quality(85),
        ]);
    }
}
