<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Livewire\OptimizedDatabaseNotifications;
use App\Filament\Pages\EditProfile as UserEditProfile;
use Filament\Actions\Action;
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
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
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
        Table::configureUsing(function (Table $table): void {
            $table
                ->filtersLayout(FiltersLayout::Modal)
                ->filtersTriggerAction(fn (Action $action): Action => $action->slideOver())
                ->columnManagerLayout(ColumnManagerLayout::Modal)
                ->columnManagerTriggerAction(fn (Action $action): Action => $action->slideOver());
        });

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
            ->path((string) config('mipress.admin_path', 'mpcp'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->passwordReset()
            ->profile(UserEditProfile::class)
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
            ->databaseNotifications(livewireComponent: OptimizedDatabaseNotifications::class)
            ->databaseTransactions()
            ->sidebarWidth('16rem')
            ->sidebarCollapsibleOnDesktop()
            ->plugin(MiPressPlugin::make())
            ->plugin(FormsPlugin::make())
            ->plugin(SocialFeedsPlugin::make())
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
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
