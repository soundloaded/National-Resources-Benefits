<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

use App\Models\User;
use App\Observers\UserObserver;

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
        Vite::prefetch(concurrency: 3);
        Transaction::observe(TransactionObserver::class);
        User::observe(UserObserver::class);


        try {
            if (Schema::hasTable('settings')) {
                // Apply Mail Settings
                $mailer = Setting::get('mail_mailer');
                if ($mailer) {
                    Config::set('mail.default', $mailer); // 'smtp' usually
                    
                    if ($mailer === 'smtp') {
                        $host = Setting::get('mail_host');
                        $port = Setting::get('mail_port');
                        $username = Setting::get('mail_username');
                        $password = Setting::get('mail_password');
                        $enc = Setting::get('mail_encryption');
                        
                        if ($host) Config::set('mail.mailers.smtp.host', $host);
                        if ($port) Config::set('mail.mailers.smtp.port', $port);
                        if ($username) Config::set('mail.mailers.smtp.username', $username);
                        if ($password) Config::set('mail.mailers.smtp.password', $password);
                        if ($enc) Config::set('mail.mailers.smtp.encryption', $enc);
                    }
                    
                    $fromAddress = Setting::get('mail_from_address');
                    $fromName = Setting::get('mail_from_name');
                    
                    if ($fromAddress) Config::set('mail.from.address', $fromAddress);
                    if ($fromName) Config::set('mail.from.name', $fromName);
                }
                
                // Apply General Settings
                $siteName = Setting::get('site_name');
                if ($siteName) Config::set('app.name', $siteName);
                
                $appUrl = Setting::get('app_url'); // If we add this later
                if ($appUrl) Config::set('app.url', $appUrl);
            }
        } catch (\Exception $e) {
            // Log::error('Failed to load settings: ' . $e->getMessage());
        }
    }
}
