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

        // Dynamic SMTP overrides from Database Settings
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $host = \App\Models\Setting::get('mail_host');
                if (!empty($host)) {
                    $encryption = \App\Models\Setting::get('mail_encryption');
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.host' => $host,
                        'mail.mailers.smtp.port' => \App\Models\Setting::get('mail_port', config('mail.mailers.smtp.port')),
                        'mail.mailers.smtp.username' => \App\Models\Setting::get('mail_username', config('mail.mailers.smtp.username')),
                        'mail.mailers.smtp.password' => \App\Models\Setting::get('mail_password', config('mail.mailers.smtp.password')),
                        'mail.mailers.smtp.encryption' => ($encryption === 'none' ? null : $encryption) ?? config('mail.mailers.smtp.encryption'),
                        'mail.from.address' => \App\Models\Setting::get('mail_from_address', config('mail.from.address')),
                        'mail.from.name' => \App\Models\Setting::get('mail_from_name', config('mail.from.name')),
                    ]);
                    \Illuminate\Support\Facades\Mail::purge('smtp');
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback to prevent app crashing during installations/migrations
        }

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
