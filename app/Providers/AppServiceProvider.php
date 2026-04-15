<?php

namespace App\Providers;

use Awcodes\Botly\Models\Botly;
use BezhanSalleh\LanguageSwitch\Events\LocaleChanged;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Event;
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

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch): void {
            $switch
                ->locales(array_keys(config('mipress.locales', [])))
                ->labels(config('mipress.locales', []))
                ->userPreferredLocale(fn (): ?string => auth()->user()?->preferred_locale)
                ->visible(outsidePanels: true);
        });

        Event::listen(LocaleChanged::class, function (LocaleChanged $event): void {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            if ($user->preferred_locale === $event->locale) {
                return;
            }

            $user->update([
                'preferred_locale' => $event->locale,
            ]);
        });
    }
}
