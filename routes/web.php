<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LandingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- MARKETING & PUBLIC ROUTES ---
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);

// Platform-specific landing pages (e.g., /instagram-promotion, /tiktok-promotion)
Route::get('/{platform}-promotion', [LandingController::class, 'platform'])
    ->where('platform', 'instagram|tiktok|youtube|facebook|x|telegram|spotify|audiomack')
    ->name('platform');



Route::get('/buy-virtual-numbers', [LandingController::class, 'virtualNumbers'])->name('services.vn-marketing');
Route::redirect('/services/sms', '/orders/virtual-numbers');
Route::redirect('/services/virtual-numbers', '/orders/virtual-numbers');
Route::redirect('/virtual-numbers', '/buy-virtual-numbers');


// Specialized Instagram SEO Pages
Route::get('/buy-instagram-followers', [LandingController::class, 'instagramFollowers'])->name('services.ig-followers');
Route::get('/buy-instagram-comments', [LandingController::class, 'instagramComments'])->name('services.ig-comments');
Route::get('/buy-instagram-likes', [LandingController::class, 'instagramLikes'])->name('services.ig-likes');

// Specialized TikTok SEO Pages
Route::get('/buy-tiktok-followers', [LandingController::class, 'tiktokFollowers'])->name('services.tk-followers');
Route::get('/buy-tiktok-likes', [LandingController::class, 'tiktokLikes'])->name('services.tk-likes');
Route::get('/buy-tiktok-views', [LandingController::class, 'tiktokViews'])->name('services.tk-views');

// Specialized YouTube SEO Pages
Route::get('/buy-youtube-subscribers', [LandingController::class, 'youtubeSubscribers'])->name('services.yt-subscribers');
Route::get('/buy-youtube-views', [LandingController::class, 'youtubeViews'])->name('services.yt-views');
Route::get('/buy-youtube-watch-time', [LandingController::class, 'youtubeWatchTime'])->name('services.yt-watchtime');

// Specialized X SEO Pages
Route::get('/buy-x-followers', [LandingController::class, 'xFollowers'])->name('services.x-followers');
Route::get('/buy-x-likes', [LandingController::class, 'xLikes'])->name('services.x-likes');
Route::get('/buy-x-reposts', [LandingController::class, 'xReposts'])->name('services.x-reposts');

// Specialized Facebook SEO Pages
Route::get('/buy-facebook-page-likes', [LandingController::class, 'facebookPageLikes'])->name('services.fb-page-likes');
Route::get('/buy-facebook-post-likes', [LandingController::class, 'facebookPostLikes'])->name('services.fb-post-likes');
Route::get('/buy-facebook-followers', [LandingController::class, 'facebookFollowers'])->name('services.fb-followers');

// Specialized Telegram SEO Pages
Route::get('/buy-telegram-members', [LandingController::class, 'telegramMembers'])->name('services.tg-members');
Route::get('/buy-telegram-views', [LandingController::class, 'telegramViews'])->name('services.tg-views');

// Specialized Spotify SEO Pages
Route::get('/buy-spotify-followers', [LandingController::class, 'spotifyFollowers'])->name('services.sp-followers');
Route::get('/buy-spotify-plays', [LandingController::class, 'spotifyPlays'])->name('services.sp-plays');
Route::get('/buy-spotify-monthly-listeners', [LandingController::class, 'spotifyListeners'])->name('services.sp-listeners');

// Specialized Audiomack SEO Pages
Route::get('/buy-audiomack-followers', [LandingController::class, 'audiomackFollowers'])->name('services.am-followers');
Route::get('/buy-audiomack-plays', [LandingController::class, 'audiomackPlays'])->name('services.am-plays');

// Legal & Info Pages
Route::get('/about', function() { return view('info.about'); })->name('info.about');
Route::get('/faq', function() { return view('info.faq'); })->name('info.faq');
Route::get('/contact', function() { return view('info.contact'); })->name('info.contact');
Route::redirect('/support', '/contact')->name('support');

// Blog Routes
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('info.blog');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('info.blog.show');

Route::get('/terms', function() { return view('legal.terms'); })->name('legal.terms');
Route::get('/privacy', function() { return view('legal.terms'); })->name('legal.privacy');

// Public API Documentation
Route::get('/api/docs', function() {
    return view('api.docs');
})->name('api.docs');
Route::get('/docs', function() {
    return redirect()->route('api.docs');
});


// --- AUTHENTICATION ROUTES ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email Verification
Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Password Reset
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware(['guest', 'throttle:3,1'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');

// 2FA
Route::get('/two-factor', [AuthController::class, 'showTwoFactor'])->name('two-factor.index');
Route::post('/two-factor', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:5,1')->name('two-factor.verify');
Route::get('/two-factor/resend', [AuthController::class, 'resendTwoFactor'])->middleware('throttle:3,1')->name('two-factor.resend');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/services', [App\Http\Controllers\DashboardController::class, 'services'])->name('dashboard.services');
    Route::get('/hq/users/leave-impersonation', [AdminController::class, 'leaveImpersonation'])->name('hq.users.leave-impersonation');

    // Order Routes
    Route::get('/orders/new', [App\Http\Controllers\OrderController::class, 'create'])->name('orders.create');
    Route::get('/orders/bulk', [App\Http\Controllers\OrderController::class, 'bulkCreate'])->name('orders.bulk');
    Route::post('/orders/bulk', [App\Http\Controllers\OrderController::class, 'bulkStore'])->name('orders.bulk.store');
    Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/history', [App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');

    // Wallet Routes
    Route::get('/wallet', [App\Http\Controllers\PaymentController::class, 'deposit'])->name('wallet.index');
    Route::get('/wallet/history', [App\Http\Controllers\TransactionController::class, 'index'])->name('wallet.history');
    Route::post('/api/payments/korapay', [App\Http\Controllers\PaymentController::class, 'initializeKorapay'])->name('wallet.korapay.initialize');
    Route::post('/wallet/virtual-account/generate', [App\Http\Controllers\PaymentController::class, 'generateVirtualAccount'])->name('wallet.virtual-account.generate');

    // Support Tickets
    Route::get('/tickets', [App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [App\Http\Controllers\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/reply', [App\Http\Controllers\TicketController::class, 'reply'])->name('tickets.reply');

    // Affiliate Routes
    Route::get('/affiliate', [App\Http\Controllers\AffiliateController::class, 'index'])->name('affiliate.index');
    Route::post('/affiliate/withdraw', [App\Http\Controllers\AffiliateController::class, 'requestWithdrawal'])->name('affiliate.withdraw');

    // New Coming Soon Services
    Route::get('/orders/virtual-numbers', [App\Http\Controllers\VirtualNumberController::class, 'index'])->name('services.virtual-numbers');
    Route::get('/virtual-numbers/portal', [App\Http\Controllers\VirtualNumberController::class, 'index'])->name('virtual-numbers.index');
    Route::post('/orders/virtual-numbers/purchase', [App\Http\Controllers\VirtualNumberController::class, 'purchase'])->name('virtual-numbers.purchase');
    Route::get('/orders/virtual-numbers/history', [App\Http\Controllers\VirtualNumberController::class, 'history'])->name('virtual-numbers.history');
    Route::get('/api/virtual-numbers/countries', [\App\Http\Controllers\VirtualNumberController::class, 'getCountries']);

    // VTU Services (Airtime, Data, Electricity, Cable)
    Route::get('/vtu', [App\Http\Controllers\VtuController::class, 'airtime'])->name('vtu.index');
    Route::get('/vtu/history', [App\Http\Controllers\VtuController::class, 'history'])->name('vtu.history');
    Route::get('/vtu/airtime', [App\Http\Controllers\VtuController::class, 'airtime'])->name('vtu.airtime');
    Route::post('/vtu/airtime/purchase', [App\Http\Controllers\VtuController::class, 'purchaseAirtime'])->name('vtu.airtime.purchase');
    
    Route::get('/vtu/data', [App\Http\Controllers\VtuController::class, 'data'])->name('vtu.data');
    Route::post('/vtu/data/purchase', [App\Http\Controllers\VtuController::class, 'purchaseData'])->name('vtu.data.purchase');
    
    Route::get('/vtu/electricity', [App\Http\Controllers\VtuController::class, 'electricity'])->name('vtu.electricity');
    Route::post('/vtu/electricity/validate', [App\Http\Controllers\VtuController::class, 'validateMeter'])->name('vtu.electricity.validate');
    Route::post('/vtu/electricity/purchase', [App\Http\Controllers\VtuController::class, 'purchaseElectricity'])->name('vtu.electricity.purchase');
    
    Route::get('/vtu/cable', [App\Http\Controllers\VtuController::class, 'cable'])->name('vtu.cable');
    Route::post('/vtu/cable/validate', [App\Http\Controllers\VtuController::class, 'validateIUC'])->name('vtu.cable.validate');
    Route::post('/vtu/cable/purchase', [App\Http\Controllers\VtuController::class, 'purchaseCable'])->name('vtu.cable.purchase');
    
    // Logs Marketplace
    Route::get('/logs', [\App\Http\Controllers\LogMarketplaceController::class, 'index'])->name('logs.index');
    Route::get('/logs/history', [\App\Http\Controllers\LogMarketplaceController::class, 'history'])->name('logs.history');
    Route::get('/logs/product/{id}', [\App\Http\Controllers\LogMarketplaceController::class, 'show'])->name('logs.show');
    Route::post('/logs/purchase', [\App\Http\Controllers\LogMarketplaceController::class, 'purchase'])->name('logs.purchase');
    Route::get('/logs/order/{id}', [\App\Http\Controllers\LogMarketplaceController::class, 'showOrder'])->name('logs.order');

    // AI TOOLS — Customer Routes
    Route::prefix('ai-tools')->name('ai-tools.')->group(function () {
        Route::get('/',             [\App\Http\Controllers\AiToolsController::class, 'index'])->name('index');
        Route::get('/product/{id}', [\App\Http\Controllers\AiToolsController::class, 'show'])->name('show');
        Route::post('/purchase',    [\App\Http\Controllers\AiToolsController::class, 'purchase'])->name('purchase');
        Route::get('/history',      [\App\Http\Controllers\AiToolsController::class, 'history'])->name('history');
        Route::get('/order/{id}',        [\App\Http\Controllers\AiToolsController::class, 'orderDetail'])->name('order.detail');
        Route::get('/order/{id}/status', [\App\Http\Controllers\AiToolsController::class, 'orderStatus'])->name('order.status');
    });


    Route::get('/api/virtual-numbers/products', [\App\Http\Controllers\VirtualNumberController::class, 'getProducts']);
    Route::get('/api/virtual-numbers/quote', [\App\Http\Controllers\VirtualNumberController::class, 'getLiveQuote'])->name('virtual-numbers.quote');
    Route::post('/api/virtual-numbers/purchase', [\App\Http\Controllers\VirtualNumberController::class, 'purchase']);
    Route::get('/api/virtual-numbers/{id}/check', [\App\Http\Controllers\VirtualNumberController::class, 'checkStatus']);
    Route::post('/api/virtual-numbers/{id}/cancel', [\App\Http\Controllers\VirtualNumberController::class, 'cancelOrder']);
    Route::post('/api/virtual-numbers/{id}/resend', [\App\Http\Controllers\VirtualNumberController::class, 'resendOrder'])->name('virtual-numbers.resend');
    
    Route::get('/gift-cards', function() {
        return view('services.coming_soon', ['title' => 'Buy Gift Cards', 'icon' => 'fa-gift']);
    })->name('services.gift-cards');

    Route::get('/buy-esim', function() {
        return view('services.coming_soon', ['title' => 'Buy eSim', 'icon' => 'fa-sim-card']);
    })->name('services.buy-esim');

    Route::get('/promote-brand', function() {
        return view('services.coming_soon', ['title' => 'Promote Brand & Ads', 'icon' => 'fa-bullseye']);
    })->name('services.promote-brand');

    Route::get('/residential-proxy', function() {
        return view('services.coming_soon', ['title' => 'Residential Proxies', 'icon' => 'fa-shield-halved']);
    })->name('services.residential-proxy');






    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/2fa/toggle', [App\Http\Controllers\ProfileController::class, 'toggle2fa'])->name('profile.2fa.toggle');
    Route::post('/profile/api-key/generate', [App\Http\Controllers\ProfileController::class, 'generateApiKey'])->name('profile.api-key.generate');
    Route::post('/profile/api-key/delete', [App\Http\Controllers\ProfileController::class, 'deleteApiKey'])->name('profile.api-key.delete');

    // Admin/HQ Routes
    Route::prefix('hq')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('hq.dashboard');
        Route::get('/services', [AdminController::class, 'services'])->name('hq.services');
        Route::post('/services/bulk-update', [AdminController::class, 'bulkUpdateServices'])->name('hq.services.bulk-update');
        Route::post('/services/sync', [AdminController::class, 'triggerServiceSync'])->name('hq.services.sync');
        Route::get('/users', [AdminController::class, 'users'])->name('hq.users');
        Route::get('/users-list', [AdminController::class, 'users'])->name('hq.users.index');
        Route::get('/users/export', [AdminController::class, 'exportUsersCsv'])->name('hq.users.export');
        Route::get('/users/{id}', [AdminController::class, 'showUser'])->name('hq.users.show');
        Route::put('/users/{id}', [AdminController::class, 'updateUserDetails'])->name('hq.users.update');
        Route::match(['get', 'post'], '/users/{id}/impersonate', [AdminController::class, 'impersonate'])->name('hq.users.impersonate');
        Route::get('/users/{id}/logins', [AdminController::class, 'userLogins'])->name('hq.users.logins');
        Route::get('/users/{id}/transactions', [AdminController::class, 'userTransactions'])->name('hq.users.transactions');
        Route::post('/users/{id}/balance', [AdminController::class, 'updateUserBalance'])->name('hq.users.balance');
        Route::post('/users/{id}/role', [AdminController::class, 'updateRole'])->name('hq.users.role');
        Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('hq.users.toggle-status');
        Route::post('/users/{id}/toggle-2fa', [AdminController::class, 'toggleUser2fa'])->name('hq.users.toggle-2fa');
        Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('hq.users.destroy');
        
        // Admin Blog Management
        Route::get('/blog', [App\Http\Controllers\AdminBlogController::class, 'index'])->name('hq.blog.index');
        Route::get('/blog/create', [App\Http\Controllers\AdminBlogController::class, 'create'])->name('hq.blog.create');
        Route::post('/blog', [App\Http\Controllers\AdminBlogController::class, 'store'])->name('hq.blog.store');
        Route::get('/blog/{id}/edit', [App\Http\Controllers\AdminBlogController::class, 'edit'])->name('hq.blog.edit');
        Route::put('/blog/{id}', [App\Http\Controllers\AdminBlogController::class, 'update'])->name('hq.blog.update');
        Route::delete('/blog/{id}', [App\Http\Controllers\AdminBlogController::class, 'destroy'])->name('hq.blog.destroy');
        
        Route::get('/orders', [AdminController::class, 'orders'])->name('hq.orders');
        Route::get('/social', [AdminController::class, 'orders'])->name('hq.social');
        Route::get('/orders/{id}', [AdminController::class, 'showOrder'])->name('hq.orders.show');
        Route::post('/orders/{id}/complete', [AdminController::class, 'forceCompleteOrder'])->name('hq.orders.complete');
        Route::post('/orders/{id}/cancel', [AdminController::class, 'cancelAndRefundOrder'])->name('hq.orders.cancel');
        Route::post('/orders/{id}/resubmit', [AdminController::class, 'resubmitOrder'])->name('hq.orders.resubmit');
        Route::post('/orders/{id}/refill', [AdminController::class, 'refillOrder'])->name('hq.orders.refill');

        // Provider Management & API Diagnostics
        Route::get('/providers', [AdminController::class, 'providers'])->name('hq.providers');
        Route::get('/providers-list', [AdminController::class, 'providers'])->name('hq.providers.index');
        Route::post('/providers', [AdminController::class, 'storeProvider'])->name('hq.providers.store');
        Route::patch('/providers/{id}/toggle', [AdminController::class, 'toggleProviderStatus'])->name('hq.providers.toggle');
        Route::delete('/providers/{id}', [AdminController::class, 'destroyProvider'])->name('hq.providers.destroy');
        Route::get('/providers/{id}/balance', [AdminController::class, 'providerBalance'])->name('hq.providers.balance');
        Route::get('/providers/{id}/services-count', [AdminController::class, 'providerServicesCount'])->name('hq.providers.services-count');
        Route::get('/providers/{id}/debug', [AdminController::class, 'debugProviderSync'])->name('hq.providers.debug');

        // Provider Pricing & Markup Settings
        Route::get('/providers/pricing', [AdminController::class, 'hqProviderPricingSettings'])->name('hq.providers.pricing');
        Route::post('/providers/pricing', [AdminController::class, 'hqUpdateProviderPricingSettings'])->name('hq.providers.pricing.update');
        Route::match(['get', 'post'], '/providers/pricing/preview', [AdminController::class, 'hqPreviewProviderSync'])->name('hq.providers.pricing.preview');
        Route::post('/providers/pricing/sync', [AdminController::class, 'hqExecuteProviderSync'])->name('hq.providers.pricing.sync');

        Route::get('/virtual-numbers', [\App\Http\Controllers\AdminVirtualNumberController::class, 'index'])->name('hq.virtual-numbers');
        Route::get('/virtual-numbers/pricing', [\App\Http\Controllers\AdminVirtualNumberController::class, 'pricing'])->name('hq.virtual-numbers.pricing');
        Route::post('/virtual-numbers/pricing', [\App\Http\Controllers\AdminVirtualNumberController::class, 'updatePricing'])->name('hq.virtual-numbers.pricing.update');
        Route::post('/virtual-numbers/daisysms-key', [\App\Http\Controllers\AdminVirtualNumberController::class, 'updateDaisySmsKey'])->name('hq.virtual-numbers.daisysms-key.update');
        Route::post('/virtual-numbers/{id}/refund', [\App\Http\Controllers\AdminVirtualNumberController::class, 'refund'])->name('hq.virtual-numbers.refund');

        Route::get('/vtu', [\App\Http\Controllers\AdminVtuController::class, 'index'])->name('hq.vtu');
        Route::get('/vtu/pricing', [\App\Http\Controllers\AdminVtuController::class, 'pricing'])->name('hq.vtu.pricing');
        Route::post('/vtu/pricing', [\App\Http\Controllers\AdminVtuController::class, 'updatePricing'])->name('hq.vtu.pricing.update');
        Route::post('/vtu/{id}/refund', [\App\Http\Controllers\AdminVtuController::class, 'refund'])->name('hq.vtu.refund');

        // Logs Marketplace Admin
        Route::get('/logs', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'index'])->name('hq.logs.index');
        Route::get('/logs/pricing', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'pricing'])->name('hq.logs.pricing');
        Route::post('/logs/pricing', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'updatePricing'])->name('hq.logs.pricing.update');
        Route::match(['get', 'post'], '/logs/sync', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'sync'])->name('hq.logs.sync');
        Route::post('/logs/{id}/price', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'updatePrice'])->name('hq.logs.price');
        Route::post('/logs/{id}/toggle', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'toggleStatus'])->name('hq.logs.toggle');
        Route::post('/logs/hot-picks', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'updateHotPicks'])->name('hq.logs.hot-picks.update');
        Route::post('/logs/hot-picks/toggle/{id}', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'toggleHotPick'])->name('hq.logs.hot-picks.toggle');
        Route::post('/logs/hot-picks/reorder', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'reorderHotPicks'])->name('hq.logs.hot-picks.reorder');
        Route::get('/logs/orders', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'orders'])->name('hq.logs.orders');
        
        // Local Logs Routes
        Route::post('/logs/categories', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'storeCategory'])->name('hq.logs.category.store');
        Route::delete('/logs/categories/{id}', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'deleteCategory'])->name('hq.logs.category.delete');
        Route::post('/logs/products', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'storeProduct'])->name('hq.logs.product.store');
        Route::match(['post', 'put'], '/logs/products/{id}', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'updateProduct'])->name('hq.logs.product.update');
        Route::get('/logs/products/{id}', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'showProductRedirect'])->name('hq.logs.product.show');
        Route::delete('/logs/products/{id}', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'deleteProduct'])->name('hq.logs.product.delete');
        Route::get('/logs/products/{id}/stock', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'stock'])->name('hq.logs.stock');
        Route::post('/logs/products/{id}/stock', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'addStock'])->name('hq.logs.stock.add');
        Route::delete('/logs/stock/item/{id}', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'deleteStockItem'])->name('hq.logs.stock.item.delete');
        Route::put('/logs/stock/item/{id}', [\App\Http\Controllers\AdminLogMarketplaceController::class, 'updateStockItem'])->name('hq.logs.stock.item.update');

        Route::post('/providers', [AdminController::class, 'storeProvider'])->name('hq.providers.store');
        Route::patch('/providers/{id}/toggle', [AdminController::class, 'toggleProviderStatus'])->name('hq.providers.toggle');
        Route::delete('/providers/{id}', [AdminController::class, 'destroyProvider'])->name('hq.providers.destroy');
        Route::get('/providers/{id}/balance', [AdminController::class, 'providerBalance']);
        Route::get('/providers/{id}/services-count', [AdminController::class, 'providerServicesCount']);
        Route::get('/providers/{id}/debug-sync', [AdminController::class, 'debugProviderSync'])->name('hq.providers.debug');
        Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('hq.activity-logs');
        Route::get('/logs/activity', [AdminController::class, 'activityLogs'])->name('hq.logs');
        Route::get('/queues', [AdminController::class, 'queueStatus'])->name('hq.queues');
        Route::get('/financial-transactions', [AdminController::class, 'allTransactions'])->name('hq.transactions');
        
        Route::get('/tickets', [AdminController::class, 'hqTickets'])->name('hq.tickets');
        Route::get('/tickets/{id}', [AdminController::class, 'hqShowTicket'])->name('hq.tickets.show');
        Route::post('/tickets/{id}/reply', [AdminController::class, 'hqReplyTicket'])->name('hq.tickets.reply');
        Route::patch('/tickets/{id}/status', [AdminController::class, 'hqUpdateTicketStatus'])->name('hq.tickets.status');
        
        Route::get('/withdrawals', [AdminController::class, 'hqWithdrawals'])->name('hq.withdrawals');
        Route::post('/withdrawals/{id}/approve', [AdminController::class, 'hqApproveWithdrawal'])->name('hq.withdrawals.approve');
        Route::post('/withdrawals/{id}/decline', [AdminController::class, 'hqDeclineWithdrawal'])->name('hq.withdrawals.decline');
        
        Route::get('/broadcasts', [AdminController::class, 'hqBroadcasts'])->name('hq.broadcasts');
        Route::post('/broadcasts', [AdminController::class, 'hqStoreBroadcast'])->name('hq.broadcasts.store');
        Route::delete('/broadcasts/{id}', [AdminController::class, 'hqDestroyBroadcast'])->name('hq.broadcasts.destroy');
        Route::post('/service-notices', [AdminController::class, 'hqUpdateServiceNotices'])->name('hq.service-notices.update');

        Route::get('/settings', [AdminController::class, 'hqSettings'])->name('hq.settings');
        Route::post('/settings', [AdminController::class, 'hqUpdateSettings'])->name('hq.settings.update');
        Route::post('/settings/test-email', [AdminController::class, 'hqSendTestEmail'])->name('hq.settings.test-email');
        Route::post('/settings/sliders', [AdminController::class, 'hqStoreSlider'])->name('hq.sliders.store');
        Route::delete('/settings/sliders/{id}', [AdminController::class, 'hqDestroySlider'])->name('hq.sliders.destroy');

        // Currency & Exchange Rate Settings
        Route::get('/settings/currency', [AdminController::class, 'hqCurrencySettings'])->name('hq.settings.currency');
        Route::post('/settings/currency', [AdminController::class, 'hqUpdateCurrencySettings'])->name('hq.settings.currency.update');
        Route::post('/settings/currency/reconcile-user', [AdminController::class, 'hqReconcileUser'])->name('hq.settings.currency.reconcile-user');
        Route::post('/settings/currency/run-recovery', [AdminController::class, 'hqRunWalletRecovery'])->name('hq.settings.currency.run-recovery');

        // AI TOOLS — Admin HQ Routes
        Route::prefix('ai-tools')->name('hq.ai-tools.')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'dashboard'])->name('dashboard');
            Route::get('/products',      [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'products'])->name('products');
            Route::patch('/products/{id}', [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'updateProduct'])->name('products.update');
            Route::get('/orders',        [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'orders'])->name('orders');
            Route::get('/orders/{id}',   [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'orderDetail'])->name('orders.show');
            Route::match(['GET', 'POST'], '/orders/{id}/fulfill', [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'manualFulfill'])->name('orders.fulfill');
            Route::match(['GET', 'POST'], '/orders/{id}/refund',  [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'manualRefund'])->name('orders.refund');
            Route::match(['GET', 'POST'], '/orders/{id}/sync',    [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'syncOrder'])->name('orders.sync');
            Route::get('/settings',      [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'settings'])->name('settings');
            Route::post('/settings',     [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'updateSettings'])->name('settings.update');
            Route::post('/test-connection', [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'testConnection'])->name('test-connection');
            Route::get('/balance',       [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'checkBalance'])->name('balance');
            Route::post('/sync',         [\App\Http\Controllers\Admin\AdminAiToolsController::class, 'syncProducts'])->name('sync');
        });

    });
});

// Direct SMM Reseller API route alias (v1 without /api prefix)
Route::match(['get', 'post', 'options'], '/v1', [\App\Http\Controllers\ResellerApiController::class, 'handle']);

// Fallback
Route::fallback(function () {
    return redirect()->route('landing');
});
