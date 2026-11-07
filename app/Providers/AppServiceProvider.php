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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        \Illuminate\Support\Facades\Gate::policy(\App\Models\GiftCardOrder::class, \App\Policies\GiftCardOrderPolicy::class);

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

        // Auto-ensure dashboard_sliders table exists without manual CLI migration
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('dashboard_sliders')) {
                \Illuminate\Support\Facades\Schema::create('dashboard_sliders', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('image_path');
                    $table->string('target_url')->nullable();
                    $table->integer('sort_order')->default(0);
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        // Auto-ensure AI Marketplace tables exist without manual CLI migration
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('ai_products')) {
                \Illuminate\Support\Facades\Schema::create('ai_products', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('provider_product_id')->unique();
                    $table->string('name');
                    $table->string('category')->default('Google AI')->index();
                    $table->text('description')->nullable();
                    $table->string('icon')->nullable();
                    $table->unsignedBigInteger('wholesale_price_kobo')->default(0);
                    $table->decimal('wholesale_price', 12, 2)->default(0.00);
                    $table->decimal('retail_price', 12, 2)->default(0.00);
                    $table->boolean('in_stock')->default(true);
                    $table->integer('stock_count')->default(999);
                    $table->string('delivery_type')->default('automatic');
                    $table->boolean('is_active')->default(true)->index();
                    $table->boolean('is_hot')->default(false);
                    $table->json('metadata')->nullable();
                    $table->timestamps();
                });
            }

            if (!\Illuminate\Support\Facades\Schema::hasTable('ai_orders')) {
                \Illuminate\Support\Facades\Schema::create('ai_orders', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->unsignedBigInteger('ai_product_id')->nullable()->index();
                    $table->string('provider_order_id')->nullable()->index();
                    $table->string('idempotency_key')->unique();
                    $table->string('product_name');
                    $table->integer('quantity')->default(1);
                    $table->decimal('unit_price', 12, 2);
                    $table->decimal('total_price', 12, 2);
                    $table->decimal('provider_cost', 12, 2)->default(0.00);
                    $table->string('status')->default('pending')->index();
                    $table->string('delivery_mode')->default('portal_only');
                    $table->longText('delivery_payload')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamps();
                });
            }

            if (!\Illuminate\Support\Facades\Schema::hasTable('ai_webhook_events')) {
                \Illuminate\Support\Facades\Schema::create('ai_webhook_events', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('event_id')->unique();
                    $table->string('event_type')->index();
                    $table->json('payload');
                    $table->timestamp('processed_at')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        // Set 'Remember Me' cookie duration to 5 days (in minutes)
        config(['auth.guards.web.remember' => 7200]);

        // Custom Verification Email
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify Your Linkocial Account 🚀')
                ->view('emails.auth.verify', ['url' => $url, 'name' => $notifiable->first_name]);
        });

        // Send Welcome Email ONLY after account email is verified, delayed by 1 minute
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Verified::class, function ($event) {
            try {
                $user = $event->user;
                if ($user && !empty($user->email)) {
                    $cacheKey = 'welcome_email_queued_' . $user->id;
                    if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(7));

                        \Illuminate\Support\Facades\Mail::to($user->email)
                            ->later(now()->addMinutes(1), (new \App\Mail\WelcomeEmail($user->first_name))->delay(now()->addMinutes(1)));

                        \Illuminate\Support\Facades\Log::info("Welcome email scheduled with 1-minute delay for verified user #{$user->id} ({$user->email})");
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Welcome email queuing failed on verification: " . $e->getMessage());
            }
        });

        // Custom Password Reset Email
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $baseUrl = request()->root();
            if (empty($baseUrl) || str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '127.0.0.1')) {
                $baseUrl = config('app.url');
            }

            $relativePath = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false);

            $url = rtrim($baseUrl, '/') . $relativePath;

            return (new MailMessage)
                ->subject('Reset Your Linkocial Password 🔒')
                ->view('emails.auth.reset', ['url' => $url, 'name' => $notifiable->first_name]);
        });

        // Global Site Data Sharing
        view()->composer('*', function ($view) {
            $viewName = $view->getName();
            $isErrorView = str_starts_with($viewName, 'errors.') || str_starts_with($viewName, 'errors::');

            $siteName = 'Linkocial';
            $siteLogo = null;
            $siteLogoLight = null;
            $siteLogoDark = null;
            $siteFavicon = null;
            $stats = [
                'total_orders' => 12500,
                'active_users' => 850,
                'total_services' => 0,
            ];
            $channels = collect();

            if (!$isErrorView) {
                try {
                    $siteName = \App\Models\Setting::get('site_name', 'Linkocial');
                    $siteLogo = \App\Models\Setting::get('site_logo');
                    $siteLogoLight = \App\Models\Setting::get('site_logo_light', $siteLogo);
                    $siteLogoDark = \App\Models\Setting::get('site_logo_dark', $siteLogo);
                    $siteFavicon = \App\Models\Setting::get('site_favicon');

                    $stats = [
                        'total_orders' => \App\Models\Order::count() + 12500,
                        'active_users' => \App\Models\User::count() + 850,
                        'total_services' => \App\Models\Service::count(),
                    ];

                    $channels = \App\Models\Service::getChannels();
                } catch (\Throwable $e) {
                    // Safe fallback prevents database/connection errors from turning any view into a 500 error
                }
            }

            $view->with(compact('siteName', 'siteLogo', 'siteLogoLight', 'siteLogoDark', 'siteFavicon', 'stats', 'channels'));
        });
    }
}
