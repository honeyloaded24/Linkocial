<nav class="navbar navbar-expand-lg fixed-top border-bottom" id="mainNav" class="theme-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('landing') }}">
            @if($siteLogoLight || $siteLogoDark)
                <img src="{{ $siteLogoLight ?? $siteLogoDark }}" alt="{{ $siteName }}" height="32" class="site-logo logo-light {{ $siteLogoLight ? '' : 'd-none' }}">
                <img src="{{ $siteLogoDark ?? $siteLogoLight }}" alt="{{ $siteName }}" height="32" class="site-logo logo-dark {{ $siteLogoDark ? '' : 'd-none' }}">
            @else
                <i class="fa-solid fa-bolt-lightning text-primary fs-3"></i>
                <span class="fw-bold fs-4">{{ $siteName }}</span>
            @endif
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-lg-3 py-3 py-lg-0">
                <li class="nav-item w-100 text-lg-center"><a class="nav-link py-2" href="{{ route('landing') }}">Home</a></li>
                <li class="nav-item w-100 text-lg-center"><a class="nav-link py-2" href="{{ route('info.about') }}">About</a></li>

                <!-- Mobile Services List -->
                <li class="nav-item dropdown d-lg-none w-100">
                    <a class="nav-link dropdown-toggle d-flex justify-content-between align-items-center" href="#" data-bs-toggle="dropdown">
                        Services
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm w-100 mb-3">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 py-2" href="{{ route('vtu.airtime') }}">
                                <i class="fa-solid fa-mobile-screen" style="color: #10b981; width: 20px;"></i>
                                <span class="fw-bold lh-base">Airtime Top-up</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 py-2" href="{{ route('vtu.data') }}">
                                <i class="fa-solid fa-wifi" style="color: #3b82f6; width: 20px;"></i>
                                <span class="fw-bold lh-base">Data Subscriptions</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 py-2" href="{{ route('vtu.electricity') }}">
                                <i class="fa-solid fa-lightbulb" style="color: #f59e0b; width: 20px;"></i>
                                <span class="fw-bold lh-base">Electricity Bills</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 py-2" href="{{ route('vtu.cable') }}">
                                <i class="fa-solid fa-tv" style="color: #64748b; width: 20px;"></i>
                                <span class="fw-bold lh-base">Cable TV</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 py-2" href="{{ route('logs.index') }}">
                                <i class="fa-solid fa-box-open" style="color: #0ea5e9; width: 20px;"></i>
                                <span class="fw-bold lh-base">Logs & Accounts <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">HOT</span></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 py-2" href="{{ route('orders.create') }}">
                                <i class="fa-solid fa-bullhorn" style="color: #ef4444; width: 20px;"></i>
                                <span class="fw-bold lh-base">Social Boost</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 py-2" href="{{ route('services.vn-marketing') }}">
                                <i class="fa-solid fa-sim-card" style="color: #6366f1; width: 20px;"></i>
                                <span class="fw-bold lh-base">Virtual Numbers</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Desktop Mega Menu -->
                <li class="nav-item dropdown d-none d-lg-block">
                    <a class="nav-link d-flex align-items-center gap-1" href="#">
                        Services <i class="fa-solid fa-chevron-down small opacity-50" style="font-size: 0.7rem;"></i>
                    </a>
                    <div class="mega-menu">
                        <!-- Column 1: Our Services -->
                        <div class="mega-col border-end">
                            <h6 class="mega-title"><i class="fa-solid fa-layer-group me-2 text-primary"></i>Our Services</h6>
                            <div class="mega-list">
                                <a href="{{ route('vtu.airtime') }}" class="mega-item">
                                    <div class="mega-icon-sm" style="background: #10b98111; color: #10b981;">
                                        <i class="fa-solid fa-mobile-screen"></i>
                                    </div>
                                    <span>Airtime Top-up</span>
                                </a>
                                <a href="{{ route('vtu.data') }}" class="mega-item">
                                    <div class="mega-icon-sm" style="background: #3b82f611; color: #3b82f6;">
                                        <i class="fa-solid fa-wifi"></i>
                                    </div>
                                    <span>Data Subscriptions</span>
                                </a>
                                <a href="{{ route('vtu.electricity') }}" class="mega-item">
                                    <div class="mega-icon-sm" style="background: #f59e0b11; color: #f59e0b;">
                                        <i class="fa-solid fa-lightbulb"></i>
                                    </div>
                                    <span>Electricity Bills</span>
                                </a>
                                <a href="{{ route('vtu.cable') }}" class="mega-item">
                                    <div class="mega-icon-sm" style="background: #64748b11; color: #64748b;">
                                        <i class="fa-solid fa-tv"></i>
                                    </div>
                                    <span>Cable TV</span>
                                </a>
                                <a href="{{ route('logs.index') }}" class="mega-item">
                                    <div class="mega-icon-sm" style="background: #0ea5e911; color: #0ea5e9;">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                    <span>Logs & Accounts</span>
                                </a>
                                <a href="{{ route('orders.create') }}" class="mega-item">
                                    <div class="mega-icon-sm" style="background: #ef444411; color: #ef4444;">
                                        <i class="fa-solid fa-bullhorn"></i>
                                    </div>
                                    <span>Social Boost (SMM)</span>
                                </a>
                            </div>
                        </div>

                        <!-- Column 2: Best Sellers -->
                        <div class="mega-col">
                            <h6 class="mega-title"><i class="fa-solid fa-fire me-2 text-danger"></i>Best Sellers</h6>
                            <div class="mega-list">
                                <a href="{{ route('services.vn-marketing') }}" class="mega-item">
                                    <div class="mega-icon-sm text-white" style="background-color: #6366f1;"><i class="fa-solid fa-phone"></i></div>
                                    <span>Virtual Numbers <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">NEW</span></span>
                                </a>
                                <a href="{{ route('services.ig-followers') }}" class="mega-item">
                                    <div class="mega-icon-sm bg-danger-soft text-danger"><i class="fa-brands fa-instagram"></i></div>
                                    <span>Instagram Followers</span>
                                </a>
                                <a href="{{ route('services.tk-followers') }}" class="mega-item">
                                    <div class="mega-icon-sm bg-dark-soft text-dark"><i class="fa-brands fa-tiktok"></i></div>
                                    <span>TikTok Followers</span>
                                </a>
                                <a href="{{ route('services.yt-subscribers') }}" class="mega-item">
                                    <div class="mega-icon-sm bg-danger-soft text-danger"><i class="fa-brands fa-youtube"></i></div>
                                    <span>YouTube Subscribers</span>
                                </a>
                                <a href="{{ route('services.yt-watchtime') }}" class="mega-item">
                                    <div class="mega-icon-sm bg-warning-soft text-warning"><i class="fa-solid fa-clock"></i></div>
                                    <span>YouTube Watchtime</span>
                                </a>
                                <a href="{{ route('services.fb-followers') }}" class="mega-item">
                                    <div class="mega-icon-sm bg-primary-soft text-primary"><i class="fa-brands fa-facebook"></i></div>
                                    <span>Facebook Followers</span>
                                </a>
                            </div>
                        </div>

                        <!-- Column 3: Featured -->
                        <div class="mega-col bg-soft p-4 rounded-4">
                            <div class="text-center h-100 d-flex flex-column justify-content-center">
                                <i class="fa-solid fa-crown text-warning fs-1 mb-3"></i>
                                <h6 class="fw-bold mb-2">Join the Elite</h6>
                                <p class="text-muted small mb-4">Get exclusive rates and priority support by joining our premium network.</p>
                                <a href="{{ route('register') }}" class="btn btn-premium btn-sm w-100">Join Linkocial</a>
                            </div>
                        </div>
                    </div>
                </li>


                <li class="nav-item w-100 text-lg-center"><a class="nav-link py-2" href="{{ route('info.faq') }}">FAQ</a></li>
                <li class="nav-item w-100 text-lg-center"><a class="nav-link py-2" href="{{ route('info.blog') }}">Blog</a></li>
                <li class="nav-item w-100 text-lg-center"><a class="nav-link py-2" href="{{ route('api.docs') }}">API Docs</a></li>
                <li class="nav-item w-100 text-lg-center"><a class="nav-link py-2" href="{{ route('info.contact') }}">Contact</a></li>
                @auth
                    <li class="nav-item dropdown ms-lg-2 w-100 mt-2 mt-lg-0">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 justify-content-center justify-content-lg-start" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-sm bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-user small"></i>
                            </div>
                            <span class="fw-semibold">{{ auth()->user()->first_name ?: auth()->user()->username }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2 mt-2" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item rounded-3 py-2" href="{{ auth()->user()->isAdmin() ? route('hq.dashboard') : route('dashboard') }}">
                                    <i class="fa-solid fa-chart-line me-2 opacity-50"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2" href="{{ route('profile.index') }}">
                                    <i class="fa-solid fa-circle-user me-2 opacity-50"></i> Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded-3 py-2 text-danger">
                                        <i class="fa-solid fa-right-from-bracket me-2 opacity-50"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item ms-lg-2 w-100 mt-2 mt-lg-0">
                        <a class="btn btn-outline-premium btn-sm w-100 px-4" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item w-100 mt-2 mt-lg-0">
                        <a class="btn btn-premium btn-sm w-100 px-4" href="{{ route('register') }}">Register</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Logo Theme Switching */
    .logo-dark { display: none !important; }
    .logo-light { display: block !important; }

    [data-theme="dark"] .logo-dark, [data-bs-theme="dark"] .logo-dark { display: block !important; }
    [data-theme="dark"] .logo-light, [data-bs-theme="dark"] .logo-light { display: none !important; }

    /* Desktop Mega Menu */
    @media (min-width: 992px) {
        .nav-item.dropdown {
            position: static;
        }
        .nav-item.dropdown:hover .mega-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
        .mega-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            width: 850px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.12);
            padding: 1.5rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1fr;
            gap: 1.5rem;
            z-index: 1100;
        }
    }

    .mega-col {
        padding: 0.5rem;
    }
    .mega-title {
        font-size: 0.85rem;
        font-weight: 800;
        text-uppercase: uppercase;
        letter-spacing: 1px;
        color: var(--text-main);
        margin-bottom: 1.25rem;
        padding-left: 0.5rem;
    }
    .mega-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .mega-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .mega-item:hover {
        background: var(--bg-soft);
        transform: translateX(5px);
    }
    .mega-icon-sm {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    .mega-item span {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-main);
    }
    
    .bg-danger-soft { background: rgba(220, 39, 67, 0.1); }
    .bg-dark-soft { background: rgba(15, 23, 42, 0.1); }
    .bg-warning-soft { background: rgba(245, 158, 11, 0.1); }
    .bg-primary-soft { background: rgba(99, 102, 241, 0.1); }

    /* Premium Buttons */
    .btn-premium {
        background: var(--primary);
        color: white;
        border: none;
        font-weight: 700;
        border-radius: 12px;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
    }
    .btn-premium:hover {
        background: #4f46e5;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
    }
    .btn-outline-premium {
        background: transparent;
        color: var(--primary);
        border: 2px solid var(--primary);
        font-weight: 700;
        border-radius: 12px;
        transition: all 0.3s;
    }
    .btn-outline-premium:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }

    /* Mobile Adjustments */
    @media (max-width: 991px) {
        .navbar-collapse {
            max-height: 80vh;
            overflow-y: auto;
        }
        .nav-link {
            font-size: 1.1rem;
        }
        .site-logo {
            height: 28px;
        }
    }

    /* Public navigation mega-menu surfaces must follow the active theme. */
    [data-theme="dark"] .mega-menu, [data-bs-theme="dark"] .mega-menu {
        background: var(--bg-surface, #1f2937) !important;
        border-color: var(--border-color, #374151) !important;
        box-shadow: 0 30px 60px rgba(0,0,0,0.35);
    }
    [data-theme="dark"] .mega-item:hover, [data-bs-theme="dark"] .mega-item:hover {
        background: var(--bg-muted, rgba(255,255,255,0.06));
    }
</style>
