<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

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
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        // Set 'Remember Me' cookie duration to 5 days (in minutes)
        config(['auth.guards.web.remember' => 7200]);

        // Custom Verification Email
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify Your Linkocial Account 🚀')
                ->view('emails.auth.verify', ['url' => $url, 'name' => $notifiable->first_name]);
        });

        // Custom Password Reset Email
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Your Linkocial Password 🔒')
                ->view('emails.auth.reset', ['url' => $url, 'name' => $notifiable->first_name]);
        });

        // Global Site Data Sharing
        view()->composer('*', function ($view) {
            $siteName = \App\Models\Setting::get('site_name', 'Linkocial');
            $siteLogo = \App\Models\Setting::get('site_logo');
            $siteLogoLight = \App\Models\Setting::get('site_logo_light', $siteLogo);
            $siteLogoDark = \App\Models\Setting::get('site_logo_dark', $siteLogo);
            $siteFavicon = \App\Models\Setting::get('site_favicon');
            
            // Shared Stats for Footer/Trust
            $stats = [
                'total_orders' => \App\Models\Order::count() + 12500, // Hardening with baseline
                'active_users' => \App\Models\User::count() + 850,
                'total_services' => \App\Models\Service::count(),
            ];

            // Platform Channels for Navigation
            $channels = \App\Models\Service::getChannels();

            $view->with(compact('siteName', 'siteLogo', 'siteLogoLight', 'siteLogoDark', 'siteFavicon', 'stats', 'channels'));
        });
    }
}
