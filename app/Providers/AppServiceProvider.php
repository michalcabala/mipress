<?php

namespace App\Providers;

use Awcodes\Botly\Models\Botly;
use Awcodes\Curator\Curations\CurationPreset;
use Awcodes\Curator\Facades\Curation;
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

        Curation::presets([
            CurationPreset::make('Miniatura')
                ->width(200)
                ->height(200)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Open Graph')
                ->width(1200)
                ->height(630)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Čtverec')
                ->width(600)
                ->height(600)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Krajina 16:9')
                ->width(1200)
                ->height(675)
                ->format('webp')
                ->quality(85),
            CurationPreset::make('Krajina 4:3')
                ->width(1200)
                ->height(900)
                ->format('webp')
                ->quality(85),
        ]);
    }
}
