<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\LiveMapPage;
use App\Filament\Pages\ReportsPage;
use App\Filament\Widgets\CustomerSatisfactionWidget;
use App\Filament\Widgets\FilterableStatsWidget;
use App\Filament\Widgets\PaymentSummaryWidget;
use App\Filament\Widgets\RecentInstallationRequestsWidget;
use App\Filament\Widgets\RecentServiceRequestsWidget;
use App\Filament\Widgets\StatusDonutWidget;
use App\Filament\Widgets\TechnicianPerformanceWidget;
use App\Filament\Widgets\UsersDataWidget;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->brandName('Sindbad Admin')
            ->brandLogo(fn() => view('filament.partials.brand'))
            ->brandLogoHeight('auto')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(true)
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                FilterableStatsWidget::class,
                StatusDonutWidget::class,
                UsersDataWidget::class,
                PaymentSummaryWidget::class,
                TechnicianPerformanceWidget::class,
                CustomerSatisfactionWidget::class,
                RecentInstallationRequestsWidget::class,
                RecentServiceRequestsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make(__('Requests')),
                NavigationGroup::make(__('Management')),
                NavigationGroup::make(__('Analytics')),
                NavigationGroup::make(__('System')),
            ])
            ->navigationItems([])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => app()->getLocale() === 'ar'
                    ? new \Illuminate\Support\HtmlString('<script>document.documentElement.dir="rtl"</script>')
                    : new \Illuminate\Support\HtmlString(''),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn() => view('filament.partials.topbar-end'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn() => view('filament.partials.sidebar-language-switcher'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->editable()
                    ->selectable()
                    ->timezone('Asia/Muscat')
                    ->locale('ar')
                    ->config([
                        'headerToolbar' => [
                            'left' => 'prev,next today',
                            'center' => 'title',
                            'right' => 'timeGridDay,timeGridWeek,dayGridMonth',
                        ],
                    ]),
            ])
            ->broadcasting()
            ->authGuard('web');
    }
}
