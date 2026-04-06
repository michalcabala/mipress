<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetAdminLocale;
use Awcodes\Curator\CuratorPlugin;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MiPress\Core\Filament\MiPressPlugin;
use MiPress\Core\Services\SettingsManager;
use MiPress\Forms\Filament\FormsPlugin;
use MiPress\SocialFeeds\SocialFeedsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn (): string => view('mipress::filament.site-menu', [
                'siteName' => app(SettingsManager::class)->get('general', 'site_name', config('app.name')),
            ])->render(),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('mpcp')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->passwordReset()
            ->profile()
            ->emailVerification()
            ->multiFactorAuthentication([
                EmailAuthentication::make(),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandLogo(asset('assets/images/mipress-logo.svg'))
            ->darkModeBrandLogo(asset('assets/images/mipress-logo-white.svg'))
            ->favicon(asset('assets/images/favicon.svg'))
            ->maxContentWidth(Width::Full)
            ->spa()
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->databaseTransactions()
            ->sidebarWidth('16rem')
            ->sidebarCollapsibleOnDesktop()
            ->breadcrumbs(false)
            ->plugin(MiPressPlugin::make())
            ->plugin(FormsPlugin::make())
            ->plugin(SocialFeedsPlugin::make())
            ->plugin(
                CuratorPlugin::make()
                    ->label('Médium')
                    ->pluralLabel('Média')
                    ->navigationGroup('Obsah')
                    ->navigationSort(99)
                    ->navigationIcon('fal-photo-film-music')
            )
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                SetAdminLocale::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
