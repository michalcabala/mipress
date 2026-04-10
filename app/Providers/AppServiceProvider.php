<?php

namespace App\Providers;

use App\Models\User;
use Awcodes\Botly\Models\Botly;
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

        Gate::define('forceUnlockResourceLock', static fn (User $user): bool => $user->hasPermissionTo('entry.publish'));
    }
}
