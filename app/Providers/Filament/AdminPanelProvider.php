<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Filament\View\PanelsRenderHook;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => Schema::hasTable('settings') ? Setting::get('site_name', 'National Resource Benefits') : 'National Resource Benefits')
            ->brandLogo(fn () => $this->getBrandLogo())
            ->darkModeBrandLogo(fn () => $this->getDarkModeBrandLogo())
            ->favicon(fn () => (Schema::hasTable('settings') && $fav = Setting::get('site_favicon')) ? asset('storage/' . $fav) : null)
            ->profile(\App\Filament\Pages\Profile::class) // Uses custom Profile Page
            ->colors([
                'primary' => Color::Blue,
            ])
            ->userMenuItems([ // Adds custom items to dropdown
                'profile' => \Filament\Navigation\MenuItem::make()->label('Edit Profile'),
                // 'logout' is automatically handled by Filament
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected function getBrandLogo(): ?string
    {
        if (!Schema::hasTable('settings')) {
            return null;
        }

        $logo = Setting::get('site_logo');
        
        return $logo ? asset('storage/' . $logo) : null;
    }

    protected function getDarkModeBrandLogo(): ?string
    {
        if (!Schema::hasTable('settings')) {
            return null;
        }

        // Use dark logo if available, otherwise fall back to light logo
        $darkLogo = Setting::get('site_logo_dark');
        $lightLogo = Setting::get('site_logo');
        
        $logo = $darkLogo ?: $lightLogo;
        
        return $logo ? asset('storage/' . $logo) : null;
    }
}
