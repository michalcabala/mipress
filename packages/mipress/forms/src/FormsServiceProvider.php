<?php

declare(strict_types=1);

namespace MiPress\Forms;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use MiPress\Forms\Filament\Resources\FormResource;
use MiPress\Forms\Filament\Resources\FormSubmissionResource;
use MiPress\Forms\Mason\Bricks\FormBrick;

class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Mason block registry for downstream integrations.
        $this->app->singleton('mipress.forms.mason.bricks', fn (): array => [FormBrick::class]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mipress-forms');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Filament::registerResources([
            FormResource::class,
            FormSubmissionResource::class,
        ]);
    }
}
