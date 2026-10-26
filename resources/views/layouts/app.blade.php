<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('site_name', 'Linkocial') }} - {!! strip_tags(trim($__env->yieldContent('header_title', 'Dashboard'))) !!}</title>
    
    @include('partials.tracking')

    
    @php
        $favicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/png" href="{{ $favicon }}">
    @endif
    
    <script>
        // Apply theme IMMEDIATELY to prevent flash of wrong colors
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-bs-theme', theme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            }
        })();
    </script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --secondary-color: #f8fafc;
            --accent-color: #14b8a6;
            --bg-body: #f1f5f9;
            --bg-surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --sidebar-width: 260px;
            --heading-font: 'Outfit', sans-serif;
            --body-font: 'Inter', sans-serif;
        }

        [data-theme="dark"], [data-bs-theme="dark"] {
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --secondary-color: #1e293b;
            --bs-body-color: #f8fafc;
            --bs-body-bg: #0f172a;
        }

        /* Dark Theme Global Text & Component Overrides */
        [data-theme="dark"] body, [data-bs-theme="dark"] body {
            background-color: var(--bg-body) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .text-dark, [data-bs-theme="dark"] .text-dark {
            color: var(--text-main) !important;
            --bs-text-opacity: 1 !important;
        }

        [data-theme="dark"] .text-muted, [data-bs-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
            --bs-text-opacity: 1 !important;
        }

        [data-theme="dark"] .bg-white, [data-bs-theme="dark"] .bg-white {
            background-color: var(--bg-surface) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .bg-light, [data-bs-theme="dark"] .bg-light {
            background-color: rgba(255, 255, 255, 0.04) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .card, [data-bs-theme="dark"] .card,
        [data-theme="dark"] .premium-card, [data-bs-theme="dark"] .premium-card {
            background-color: var(--bg-surface) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .table, [data-bs-theme="dark"] .table {
            --bs-table-color: var(--text-main) !important;
            --bs-table-bg: transparent !important;
            --bs-table-border-color: var(--border-color) !important;
            --bs-table-hover-color: var(--text-main) !important;
            --bs-table-hover-bg: rgba(255, 255, 255, 0.04) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .table th, [data-bs-theme="dark"] .table th {
            color: var(--text-muted) !important;
            border-bottom-color: var(--border-color) !important;
            background-color: transparent !important;
        }

        [data-theme="dark"] .table td, [data-bs-theme="dark"] .table td {
            color: var(--text-main) !important;
            border-bottom-color: var(--border-color) !important;
            background-color: transparent !important;
        }

        [data-theme="dark"] .table-hover tbody tr:hover td {
            background-color: rgba(99, 102, 241, 0.08) !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] .border {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .form-control, [data-bs-theme="dark"] .form-control,
        [data-theme="dark"] .form-select, [data-bs-theme="dark"] .form-select {
            background-color: var(--bg-surface) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        /* Logo Theme Switching */
        .logo-dark { display: none !important; }
        .logo-light { display: block !important; }

        [data-theme="dark"] .logo-dark { display: block !important; }
        [data-theme="dark"] .logo-light { display: none !important; }

        /* Ensure Bootstrap modals always layer above topbar and all elements */
        .modal {
            z-index: 2050 !important;
        }
        .modal-backdrop {
            z-index: 2040 !important;
        }
        body.modal-open {
            padding-right: 0 !important;
            overflow-y: auto !important;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Navigation Sidebar -->
    <nav class="sidebar" id="mainSidebar">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="{{ Auth::check() ? route('dashboard') : url('/') }}" class="brand d-flex align-items-center text-decoration-none mb-0">
                @php
                    $logoLight = \App\Models\Setting::get('site_logo_light');
                    $logoDark = \App\Models\Setting::get('site_logo_dark');
                    $siteName = \App\Models\Setting::get('site_name', 'Linkocial');
                @endphp

                @if($logoLight || $logoDark)
                    <div class="site-logo-container">
                        <img src="{{ $logoLight ?? $logoDark }}" alt="{{ $siteName }}" class="img-fluid logo-light {{ $logoLight ? '' : 'd-none' }}" id="mainLogoLight" style="max-height: 40px;">
                        <img src="{{ $logoDark ?? $logoLight }}" alt="{{ $siteName }}" class="img-fluid logo-dark {{ $logoDark ? '' : 'd-none' }}" id="mainLogoDark" style="max-height: 40px;">
                    </div>
                @else
                    <div class="bg-primary-custom p-2 rounded-3 me-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-bolt-lightning text-white fs-4"></i>
                    </div>
                    <span class="fs-4 fw-bold tracking-tight" style="color: var(--text-main); font-family: var(--heading-font);">{{ $siteName }}<span class="text-primary-custom">.</span></span>
                @endif
            </a>
            <button class="btn d-lg-none border-0 text-muted" id="sidebarClose">
                <i class="fa-solid fa-xmark fs-4"></i>
            </button>
        </div>
        
        <div class="nav flex-column">
            @auth
                @php
                    $isHq = request()->routeIs('hq.*');
                @endphp

            @if(Auth::check() && Auth::user()->is_admin)
                @if($isHq)
                    <!-- Admin View Menu -->
                    <div class="mb-4">
                        <h6 class="text-muted small fw-bold text-uppercase mb-3 px-3" style="letter-spacing: 1px;">Management HQ</h6>
                        
                        <a href="{{ route('hq.dashboard') }}" class="nav-link {{ request()->routeIs('hq.dashboard') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-line"></i> Dashboard
                        </a>
                        
                        <!-- Collapsible Management -->
                        <div class="px-3 py-2">
                            <a class="nav-link p-0 mb-3 d-flex justify-content-between align-items-center text-decoration-none" data-bs-toggle="collapse" href="#mgmtCollapse" role="button" style="color: var(--text-muted);">
                                <span class="small fw-bold">OPERATIONS</span>
                                <i class="fa-solid fa-chevron-down fs-xs"></i>
                            </a>
                            <div class="collapse show" id="mgmtCollapse">
                                <a href="{{ route('hq.users') }}" class="nav-link mb-2 {{ request()->routeIs('hq.users*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-users-gears"></i> Users
                                </a>
                                <a href="{{ route('hq.services') }}" class="nav-link mb-2 {{ request()->routeIs('hq.services*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-layer-group"></i> Services
                                </a>
                                <a href="{{ route('hq.orders') }}" class="nav-link mb-2 {{ request()->routeIs('hq.orders*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-box-open"></i> Orders
                                </a>
                                <a href="{{ route('hq.virtual-numbers') }}" class="nav-link mb-2 {{ request()->routeIs('hq.virtual-numbers*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-phone-volume"></i> Virtual Numbers
                                </a>
                                <a href="{{ route('hq.vtu') }}" class="nav-link mb-2 {{ request()->routeIs('hq.vtu*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-mobile-screen"></i> VTU Orders
                                </a>
                                <a href="{{ route('hq.logs.index') }}" class="nav-link mb-2 {{ request()->routeIs('hq.logs*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-box-open"></i> Logs & Accounts
                                </a>
                                <a href="{{ route('hq.blog.index') }}" class="nav-link mb-2 {{ request()->routeIs('hq.blog*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-pen-to-square"></i> Blog Manager
                                </a>
                                <a href="{{ route('hq.broadcasts') }}" class="nav-link mb-2 {{ request()->routeIs('hq.broadcasts*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-bullhorn"></i> Notice Bar & Tickers
                                </a>
                            </div>
                        </div>

                        <!-- Collapsible System -->
                        <div class="px-3 py-2">
                            <a class="nav-link p-0 mb-3 d-flex justify-content-between align-items-center text-decoration-none" data-bs-toggle="collapse" href="#sysCollapse" role="button" style="color: var(--text-muted);">
                                <span class="small fw-bold">SYSTEM</span>
                                <i class="fa-solid fa-chevron-down fs-xs"></i>
                            </a>
                            <div class="collapse show" id="sysCollapse">
                                <a href="{{ route('hq.providers') }}" class="nav-link mb-2 {{ request()->routeIs('hq.providers*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-server"></i> Providers
                                </a>
                                <a href="{{ route('hq.withdrawals') }}" class="nav-link mb-2 {{ request()->routeIs('hq.withdrawals*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-money-bill-transfer"></i> Payouts
                                </a>
                                <a href="{{ route('hq.transactions') }}" class="nav-link mb-2 {{ request()->routeIs('hq.transactions*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-receipt"></i> Ledger
                                </a>
                                <a href="{{ route('hq.tickets') }}" class="nav-link mb-2 {{ request()->routeIs('hq.tickets*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-headset"></i> Support
                                </a>
                                <a href="{{ route('hq.activity-logs') }}" class="nav-link mb-2 {{ request()->routeIs('hq.activity-logs*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Audit Logs
                                </a>
                                <a href="{{ route('hq.queues') }}" class="nav-link mb-2 {{ request()->routeIs('hq.queues*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-bolt-lightning"></i> Queue Monitor
                                </a>
                                <a href="{{ route('hq.settings') }}" class="nav-link mb-2 {{ request()->routeIs('hq.settings') ? 'active' : '' }}">
                                    <i class="fa-solid fa-gears"></i> Settings
                                </a>
                                <a href="{{ route('hq.settings.currency') }}" class="nav-link mb-2 {{ request()->routeIs('hq.settings.currency*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-money-bill-transfer"></i> Currency & Rates
                                </a>
                            </div>
                        </div>

                        <hr class="mx-3 opacity-10">
                        <a href="{{ route('dashboard') }}" class="nav-link text-primary-custom fw-bold">
                            <i class="fa-solid fa-rotate-left"></i> Switch to User View
                        </a>
                    </div>
                @else
                    <!-- User View with Admin Access -->
                    <div class="mb-4">
                        <h6 class="text-muted small fw-bold text-uppercase mb-3 px-3" style="letter-spacing: 1px;">Management</h6>
                        <a href="{{ route('hq.dashboard') }}" class="nav-link text-primary-custom fw-bold">
                            <i class="fa-solid fa-shield-halved"></i> Enter Admin HQ
                        </a>
                    </div>
                @endif
            @endif

            @if(!($isHq ?? false))
                <h6 class="text-muted small fw-bold text-uppercase mb-3 px-3" style="letter-spacing: 1px;">User Menu</h6>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
                <a href="{{ route('orders.create') }}" class="nav-link {{ request()->routeIs('orders.create') ? 'active' : '' }}">
                    <i class="fa-solid fa-cart-shopping"></i> New Order
                </a>
                <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-ul"></i> Order History
                </a>
                <a href="{{ route('wallet.index') }}" class="nav-link {{ request()->routeIs('wallet.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-wallet"></i> Fund Wallet
                </a>
                <a href="{{ route('wallet.history') }}" class="nav-link {{ request()->routeIs('wallet.history') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i> Transactions
                </a>
                <a href="{{ route('services.virtual-numbers') }}" class="nav-link {{ request()->routeIs('services.virtual-numbers') ? 'active' : '' }}">
                    <i class="fa-solid fa-phone-volume"></i> Virtual Numbers <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">NEW</span>
                </a>
                <a href="{{ route('logs.index') }}" class="nav-link {{ request()->routeIs('logs.index') || request()->routeIs('logs.show') ? 'active' : '' }}">
                    <i class="fa-solid fa-box-open"></i> Logs Marketplace <span class="badge bg-success ms-1" style="font-size: 0.6rem;">HOT</span>
                </a>
                <a href="{{ route('logs.history') }}" class="nav-link {{ request()->routeIs('logs.history') || request()->routeIs('logs.order') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i> Logs Purchases
                </a>
                <a href="{{ route('affiliate.index') }}" class="nav-link {{ request()->routeIs('affiliate.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-handshake"></i> Affiliates
                </a>
                <a href="{{ route('api.docs') }}" class="nav-link {{ request()->routeIs('api.docs') ? 'active' : '' }}">
                    <i class="fa-solid fa-code"></i> API Docs
                </a>
                <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-life-ring"></i> Support Tickets
                </a>


@endif


@else
            <h6 class="text-muted small fw-bold text-uppercase mb-3 px-3" style="letter-spacing: 1px;">Getting Started</h6>
            <a href="{{ route('login') }}" class="nav-link">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </a>
            <a href="{{ route('register') }}" class="nav-link">
                <i class="fa-solid fa-user-plus"></i> Register
            </a>
            <a href="{{ route('legal.terms') }}" class="nav-link">
                <i class="fa-solid fa-file-contract"></i> Terms of Service
            </a>
@endguest
        </div>

        @auth
        <div class="mt-auto pt-5 border-top border-secondary border-opacity-10">
            <div class="premium-card p-3 mb-4 bg-primary bg-opacity-10 border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-custom rounded-circle p-2 text-white shadow-sm d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">Balance</div>
                        <div class="fw-bold text-main">{{ money(Auth::user()->balance) }}</div>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link nav-link text-danger w-100 text-start border-0 ps-0">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
            @if(Auth::user()->is_admin)
                <a href="{{ route('hq.settings') }}" class="nav-link mt-2">
                    <i class="fa-solid fa-user-gear"></i> Account Settings
                </a>
            @endif
        </div>
        @endauth
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        @if(session()->has('impersonator_id'))
            <div class="bg-dark text-white text-center py-2 px-3 d-flex justify-content-between align-items-center" style="position: sticky; top: 0; z-index: 2000; font-size: 0.85rem;">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-user-secret me-2 text-warning"></i>
                    <span>Viewing account as <strong>{{ Auth::user()->username }}</strong></span>
                </div>
                <a href="{{ route('hq.users.leave-impersonation') }}" class="btn btn-sm btn-warning fw-bold py-1 px-3 rounded-pill" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Return to Admin HQ
                </a>
            </div>
        @endif
        <!-- Topbar -->
        <header class="topbar px-2 px-sm-3 px-md-4" style="position: relative; z-index: 100;">
            @php
                $unreadCount = Auth::check() ? \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count() : 0;
                $latestNotifications = Auth::check() ? \App\Models\Notification::where('user_id', Auth::id())->latest()->take(5)->get() : collect();
            @endphp

            <!-- Mobile Topbar: items evenly distributed across the width -->
            <div class="d-flex align-items-center justify-content-between w-100 d-lg-none">
                <!-- 1. Hamburger -->
                <button class="btn p-0 border-0 text-muted flex-shrink-0" id="sidebarToggle" title="Toggle Navigation">
                    <i class="fa-solid fa-bars-staggered fs-5"></i>
                </button>

                @auth
                <!-- 2. Balance Pill -->
                <div class="d-flex align-items-center gap-1 px-2 py-1 rounded-pill shadow-sm" style="background: var(--bg-surface); border: 1px solid var(--border-color); font-size: 0.8rem; height: 32px;" title="Current Balance">
                    <a href="{{ route('wallet.index') }}" class="text-decoration-none text-main fw-bold d-flex align-items-center gap-1">
                        <i class="fa-solid fa-wallet text-muted me-1"></i>
                        <span class="topbarBalanceValue" data-raw-balance="{{ money(Auth::user()->balance ?? 0) }}">{{ money(Auth::user()->balance ?? 0) }}</span>
                    </a>
                    <button type="button" class="btn btn-link p-0 text-muted border-0 lh-1" onclick="toggleTopbarBalance(this)" style="font-size: 0.78rem;" title="Show/Hide Balance">
                        <i class="fa-regular fa-eye topbarBalanceEye"></i>
                    </button>
                </div>

                <!-- 3. Fund Wallet Button (+) -->
                <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-success fw-bold rounded-pill d-flex align-items-center justify-content-center shadow-sm px-2 py-1" style="height: 32px; min-width: 32px;" title="Fund Wallet">
                    <i class="fa-solid fa-plus"></i>
                </a>
                @endauth

                @auth
                <!-- 5. Profile Avatar -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary-custom text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 34px; height: 34px; font-size: 0.85rem;">
                            {{ substr(Auth::user()->first_name ?? 'U', 0, 1) }}{{ substr(Auth::user()->last_name ?? 'U', 0, 1) }}
                        </div>
                        <i class="fa-solid fa-chevron-down small text-muted ms-1" style="font-size: 0.7em;"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 premium-card mt-3">
                        <li><a class="dropdown-item rounded p-2" href="{{ route('profile.index') }}"><i class="fa-solid fa-user-pen me-2 text-primary"></i> Edit Profile</a></li>
                        <li><a class="dropdown-item rounded p-2" href="{{ route('wallet.index') }}"><i class="fa-solid fa-wallet me-2 text-primary"></i> My Wallet</a></li>
                        <li><a class="dropdown-item rounded p-2" href="#" data-bs-toggle="modal" data-bs-target="#mobileNotificationsModal"><i class="fa-solid fa-bell me-2 text-warning"></i> Notifications @if($unreadCount > 0)<span class="badge bg-danger ms-2">{{ $unreadCount }}</span>@endif</a></li>
                        <li><hr class="dropdown-divider opacity-10"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item rounded p-2 text-danger">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                @else
                <a href="{{ route('login') }}" class="btn btn-primary-custom px-3 py-1 rounded-pill fw-bold small shadow-sm" style="font-size: 0.8rem;">
                    Login
                </a>
                @endauth
            </div>

            <!-- Desktop Topbar (>= 992px) -->
            <div class="d-none d-lg-flex align-items-center justify-content-between w-100">
                <!-- Left: Balance Pill -->
                <div class="d-flex align-items-center">
                    @auth
                    <div class="d-flex align-items-center gap-2 px-3 py-1 rounded-pill shadow-sm" style="background: var(--bg-surface); border: 1px solid var(--border-color); font-size: 0.82rem; height: 34px;" title="Current Balance">
                        <a href="{{ route('wallet.index') }}" class="text-decoration-none text-main fw-bold d-flex align-items-center gap-1">
                            <i class="fa-solid fa-wallet text-muted me-1"></i>
                            <span class="topbarBalanceValue" data-raw-balance="{{ money(Auth::user()->balance ?? 0) }}">{{ money(Auth::user()->balance ?? 0) }}</span>
                        </a>
                        <button type="button" class="btn btn-link p-0 text-muted border-0 lh-1" onclick="toggleTopbarBalance(this)" style="font-size: 0.78rem;" title="Show/Hide Balance">
                            <i class="fa-regular fa-eye topbarBalanceEye"></i>
                        </button>
                    </div>
                    @endauth
                </div>

                <!-- Right: Desktop Actions -->
                <div class="d-flex align-items-center gap-3">
                    @auth
                    <!-- Fund Wallet Button -->
                    <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-success fw-bold rounded-pill d-flex align-items-center justify-content-center shadow-sm transition-hover px-3 py-1.5" style="height: 34px;" title="Fund Wallet">
                        <i class="fa-solid fa-plus me-1"></i>
                        <span>Fund Wallet</span>
                    </a>
                    @endauth

                    <!-- Theme Switcher -->
                    <div class="theme-switch-wrapper me-1">
                        <i class="fa-solid fa-sun text-muted"></i>
                        <label class="theme-switch" for="checkbox">
                            <input type="checkbox" id="checkbox" checked />
                            <div class="slider round"></div>
                        </label>
                        <i class="fa-solid fa-moon text-muted"></i>
                    </div>

                    <!-- User Tier Badge -->
                    @auth
                        @php
                            $roleBadges = [
                                'admin' => ['label' => 'ADMIN HQ', 'class' => 'bg-primary text-white'],
                                'reseller' => ['label' => 'GOLD RESELLER', 'class' => 'bg-info text-white'],
                                'vip' => ['label' => 'VIP PARTNER', 'class' => 'bg-warning text-dark'],
                                'user' => ['label' => 'SILVER PARTNER', 'class' => 'bg-light bg-opacity-10 text-muted']
                            ];
                            $badge = $roleBadges[Auth::user()->role ?? 'user'] ?? $roleBadges['user'];
                        @endphp
                        <span class="badge {{ $badge['class'] }} tier-badge shadow-sm d-inline-flex align-items-center">
                            <i class="fa-solid fa-crown"></i>
                            <span class="tier-label">{{ $badge['label'] }}</span>
                        </span>
                    @endauth

                    <!-- Notification Bell -->
                    <div class="dropdown">
                        @auth
                            <a href="#" class="text-decoration-none position-relative p-2" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--text-main);">
                                <i class="fa-solid fa-bell fs-5"></i>
                                @if($unreadCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger border border-2 border-white" style="padding: 0.35rem; font-size: 0.5rem;">
                                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                    </span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 premium-card mt-3" style="width: 320px; z-index: 2000;">
                                <li class="p-2 border-bottom mb-2 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0">Notifications</h6>
                                    <a href="#" class="small text-decoration-none text-primary fw-bold" style="font-size: 0.7rem;">Mark all as read</a>
                                </li>
                                <div style="max-height: 350px; overflow-y: auto;">
                                    @forelse($latestNotifications as $notif)
                                        <li class="p-2 rounded hover-bg mb-1">
                                            <a href="{{ $notif->link ?? '#' }}" class="text-decoration-none d-flex gap-3">
                                                <div class="rounded-circle bg-{{ $notif->type }} bg-opacity-10 text-{{ $notif->type }} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px;">
                                                    <i class="fa-solid fa-{{ $notif->type == 'success' ? 'check' : ($notif->type == 'danger' ? 'circle-exclamation' : ($notif->type == 'warning' ? 'triangle-exclamation' : 'info-circle')) }}"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark small">{{ $notif->title }}</div>
                                                    <div class="text-muted" style="font-size: 0.75rem; line-height: 1.3;">{{ Str::limit($notif->message, 60) }}</div>
                                                    <div class="text-muted mt-1" style="font-size: 0.65rem;">{{ $notif->created_at->diffForHumans() }}</div>
                                                </div>
                                            </a>
                                        </li>
                                    @empty
                                        <li class="p-4 text-center text-muted">
                                            <i class="fa-solid fa-bell-slash fa-2x mb-2 opacity-20"></i>
                                            <p class="small mb-0">No notifications yet</p>
                                        </li>
                                    @endforelse
                                </div>
                            </ul>
                        @endauth
                    </div>

                    @auth
                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-primary-custom text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; font-size: 0.9rem;">
                                {{ substr(Auth::user()->first_name ?? 'U', 0, 1) }}{{ substr(Auth::user()->last_name ?? 'U', 0, 1) }}
                            </div>
                            <i class="fa-solid fa-chevron-down small text-muted ms-2"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 premium-card mt-3">
                            <li><a class="dropdown-item rounded p-2" href="{{ route('profile.index') }}"><i class="fa-solid fa-user-pen me-2 text-primary"></i> Edit Profile</a></li>
                            <li><a class="dropdown-item rounded p-2" href="{{ route('wallet.index') }}"><i class="fa-solid fa-wallet me-2 text-primary"></i> My Wallet</a></li>
                            <li><hr class="dropdown-divider opacity-10"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded p-2 text-danger">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="btn btn-primary-custom px-4 py-2 rounded-pill fw-bold small shadow-sm">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                    </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Global Session Alerts -->
        <div class="px-3 px-md-5 pt-3">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4 animate__animated animate__fadeInDown">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3">
                        <i class="fa-solid fa-check-circle fs-5"></i>
                    </div>
                    <div class="fw-bold">{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4 animate__animated animate__fadeInDown">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-3">
                        <i class="fa-solid fa-circle-exclamation fs-5"></i>
                    </div>
                    <div class="fw-bold">{{ session('error') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4 animate__animated animate__fadeInDown">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3">
                        <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                    </div>
                    <div class="fw-bold">{{ session('warning') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        @yield('content')
        @include('partials.promo_popup')

        @auth
        <!-- Mobile Notifications Modal -->
        <div class="modal fade" id="mobileNotificationsModal" tabindex="-1" aria-labelledby="mobileNotificationsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content premium-card border-0 shadow-lg">
                    <div class="modal-header border-bottom border-secondary border-opacity-10">
                        <h6 class="modal-title fw-bold" id="mobileNotificationsModalLabel"><i class="fa-solid fa-bell text-warning me-2"></i> Notifications</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-2" style="max-height: 400px; overflow-y: auto;">
                        @forelse($latestNotifications ?? [] as $notif)
                            <div class="p-2 rounded hover-bg mb-2">
                                <a href="{{ $notif->link ?? '#' }}" class="text-decoration-none d-flex gap-3">
                                    <div class="rounded-circle bg-{{ $notif->type }} bg-opacity-10 text-{{ $notif->type }} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px;">
                                        <i class="fa-solid fa-{{ $notif->type == 'success' ? 'check' : ($notif->type == 'danger' ? 'circle-exclamation' : ($notif->type == 'warning' ? 'triangle-exclamation' : 'info-circle')) }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $notif->title }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem; line-height: 1.3;">{{ Str::limit($notif->message, 80) }}</div>
                                        <div class="text-muted mt-1" style="font-size: 0.65rem;">{{ $notif->created_at->diffForHumans() }}</div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">
                                <i class="fa-solid fa-bell-slash fa-2x mb-2 opacity-20"></i>
                                <p class="small mb-0">No notifications yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endauth
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Theme Toggle JS -->
    <script>
        const toggleSwitch = document.querySelector('#checkbox');
        const currentTheme = localStorage.getItem('theme');

        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            document.documentElement.setAttribute('data-bs-theme', currentTheme);
            if (currentTheme === 'dark') {
                toggleSwitch.checked = false;
            }
        }

        function switchTheme(e) {
            if (e.target.checked) {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.setAttribute('data-bs-theme', 'light');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }    
        }

        toggleSwitch.addEventListener('change', switchTheme, false);

        // Topbar Balance Visibility Toggle
        function toggleTopbarBalance(btn) {
            const spans = document.querySelectorAll('.topbarBalanceValue, #topbarBalanceValue');
            const eyes = document.querySelectorAll('.topbarBalanceEye, #topbarBalanceEye');
            if (!spans.length) return;
            const isHidden = spans[0].innerText.trim() === '••••••';
            spans.forEach(span => {
                const raw = span.getAttribute('data-raw-balance');
                span.innerText = isHidden ? raw : '••••••';
            });
            eyes.forEach(eye => {
                eye.className = isHidden ? 'fa-regular fa-eye topbarBalanceEye' : 'fa-regular fa-eye-slash topbarBalanceEye';
            });
            localStorage.setItem('topbar_balance_hidden', isHidden ? '0' : '1');
        }
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('topbar_balance_hidden') === '1') {
                const spans = document.querySelectorAll('.topbarBalanceValue, #topbarBalanceValue');
                const eyes = document.querySelectorAll('.topbarBalanceEye, #topbarBalanceEye');
                spans.forEach(span => span.innerText = '••••••');
                eyes.forEach(eye => eye.className = 'fa-regular fa-eye-slash topbarBalanceEye');
            }
        });

        // Sidebar Toggle for Mobile
        const sidebar = document.getElementById('mainSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
            });
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @stack('scripts')

    @include('partials.telegram-sticky')
</body>
</html>
