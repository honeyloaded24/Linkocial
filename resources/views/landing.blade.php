@extends('layouts.marketing')

@section('title', $siteName . ' - World\'s #1 SMM & SMS Virtual Numbers Verification Platform')
@section('meta_description', 'Linkocial is the world\'s most reliable SMM and SMS verification platform. Get instant virtual numbers and viral-speed social media services at unbeatable prices.')

@push('styles')
<style>
    .text-gradient {
        background: linear-gradient(135deg, var(--primary), var(--accent));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 700;
    }

    /* Navbar Scrolled State */
    .navbar.scrolled {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        padding: 1rem 0;
        border-bottom: 1px solid var(--border);
        box-shadow: 0 4px 10px rgba(0,0,0,0.02);
    }

    /* Hero Section */
    .hero-section {
        padding: 140px 0 80px;
        background: radial-gradient(circle at 50% -20%, rgba(99, 102, 241, 0.08), transparent);
        position: relative;
    }
    .hero-title {
        font-size: 3rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        color: var(--text-main);
    }
    @media (max-width: 768px) {
        .hero-title { font-size: 2rem; }
        .hero-subtitle { font-size: 0.95rem; }
        .hero-section { padding: 100px 0 60px; }
    }
    .hero-subtitle {
        font-size: 1.25rem;
        color: var(--text-muted);
        max-width: 600px;
        margin-bottom: 2.5rem;
    }

    /* Premium Cards */
    .premium-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 2.5rem;
        transition: all 0.3s ease;
        box-shadow: var(--card-shadow);
    }
    .premium-card:hover {
        transform: translateY(-10px);
        border-color: var(--primary);
        box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.15);
    }

    /* Stats */
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }
    .stat-label {
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.875rem;
    }

    /* Buttons */
    .btn-premium {
        background: var(--primary);
        color: white;
        padding: 0.875rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        border: none;
        transition: all 0.3s;
        box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
    }
    .btn-premium:hover {
        background: #000000;
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.3);
        color: white;
    }
    .btn-outline-premium {
        background: transparent;
        color: var(--text-main);
        padding: 0.875rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        border: 2px solid var(--border);
        transition: all 0.3s;
    }
    .btn-outline-premium:hover {
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.05);
        color: var(--primary);
    }

    .service-icon {
        width: 64px;
        height: 64px;
        background: rgba(99, 102, 241, 0.1);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
        color: var(--primary);
    }

    /* Floating Mockup */
    .floating-mockup img {
        filter: drop-shadow(0 20px 30px rgba(0,0,0,0.05));
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
        100% { transform: translateY(0px); }
    }

    .text-instagram {
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
    }

    .text-tiktok {
        color: #000000;
        font-weight: 800;
        text-shadow: 2px 2px #ff0050, -2px -2px #00f2ea;
        position: relative;
    }

    .text-youtube {
        color: #FF0000;
        font-weight: 800;
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<header class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-center text-lg-start">
                <span class="hero-badge mb-3 d-inline-block">
                    🚀 High-Performance Growth & SMS Verification
                </span>
                <h1 class="hero-title" data-aos="fade-up" data-aos-delay="100">Buy <span class="text-instagram">Instagram</span>, <span class="text-tiktok">TikTok</span> Followers, <span class="text-youtube">YouTube</span> Subscribers, <span style="color: #06b6d4;">SMS Verification</span>, And More In Minutes.</h1>
                <p class="hero-subtitle mx-auto mx-lg-0" data-aos="fade-up" data-aos-delay="200">With <span class="text-gradient">Linkocial</span>, you can buy real <span class="text-tiktok">TikTok</span> & <span class="text-instagram">Instagram</span> Followers, Likes, Views, <span class="text-youtube">YouTube</span> Subscribers, and instant <span style="color: #06b6d4; font-weight: 600;">Virtual Numbers for SMS Verification</span>.</p>
                <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3" data-aos="fade-up" data-aos-delay="300">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-premium btn-lg">Go to Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-premium btn-lg">Register Now</a>
                    @endauth
                    <a href="{{ route('services.vn-marketing') }}" class="btn btn-lg text-white shadow-sm d-inline-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0284c7, #06b6d4); border: none; border-radius: 50rem; padding: 0.8rem 1.8rem; font-weight: 700;"><i class="fa-solid fa-mobile-screen-button"></i> SMS Verification</a>
                </div>
                
                <!-- Stats - Hidden on Mobile -->
                <div class="d-none d-lg-flex flex-wrap gap-4 mt-5 pt-4 border-top">
                    <div class="stat-item">
                        <h4 class="fw-bold mb-0">{{ number_format($stats['total_orders']) }}+</h4>
                        <span class="text-muted small text-uppercase fw-bold">Orders</span>
                    </div>
                    <div class="stat-item">
                        <h4 class="fw-bold mb-0">{{ number_format($stats['active_users']) }}+</h4>
                        <span class="text-muted small text-uppercase fw-bold">Users</span>
                    </div>
                    <div class="stat-item">
                        <h4 class="fw-bold mb-0">{{ $stats['total_services'] }}</h4>
                        <span class="text-muted small text-uppercase fw-bold">Services</span>
                    </div>
                </div>
            </div>
            <!-- Illustration - Hidden on Mobile -->
            <div class="col-lg-6 mt-5 mt-lg-0 text-center d-none d-lg-block" data-aos="fade-left" data-aos-delay="400">
                <div class="floating-mockup">
                    <img src="{{ asset('branding/undraw_social-media-interactions_5rls.svg') }}" width="450" height="450" alt="Social Media Growth Illustration" class="img-fluid" style="max-height: 450px;" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Blended Experience Section -->
@include('partials.blended-experience')

<!-- Platforms Section -->
<section class="py-5" id="services">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold fs-1 mb-3">Choose Your Platform</h2>
            <p class="text-muted fs-5">Select a network to see specialized growth services.</p>
        </div>
        <div class="row g-4">
            @foreach($channels as $channel)
                <div class="col-6 col-lg-3" data-aos="zoom-in" data-aos-delay="{{ $loop->index * 100 }}">
                    <a href="{{ route('platform', ['platform' => strtolower($channel['name'] == 'X' ? 'x' : $channel['name'])]) }}" class="text-decoration-none">
                        <div class="premium-card text-center py-4 px-3 h-100 pulse-hover">
                            <div class="mb-3 mx-auto d-flex align-items-center justify-content-center rounded-circle" 
                                 style="width: 60px; height: 60px; background: {{ $channel['color'] }}11; color: {{ $channel['color'] }}; font-size: 1.5rem;">
                                <i class="{{ $channel['icon'] }}"></i>
                            </div>
                            <h5 class="fw-bold mb-1" style="color: var(--text-main);">{{ $channel['name'] }}</h5>
                            <span class="text-muted small">{{ $channel['count'] }} Services</span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Premium Value Section -->
@include('partials.premium-value')

<!-- Why Buy Section -->
@include('partials.why-buy')

<!-- How It Works Section -->
@include('partials.how-it-works', ['bg' => 'bg-soft'])

<!-- Platform Blends Section -->
@include('partials.platform-blends')

<!-- Benefits Section -->
<section class="py-5 bg-soft" id="features">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold fs-1 mb-3">Why Professionals Choose Us</h2>
            <p class="text-muted fs-5">We don't just provide services; we fuel your social media success.</p>
        </div>
        <div class="row g-4">
            <!-- Benefit 1 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="premium-card h-100 shadow-none border-0 bg-white pulse-hover">
                    <div class="service-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fa-solid fa-rocket"></i></div>
                    <h4 class="fw-bold mb-3">Skyrocket Your Reach</h4>
                    <p class="text-muted mb-0">Get noticed by the right audience and go viral faster. Our services are designed to trigger social algorithms for maximum visibility.</p>
                </div>
            </div>
            <!-- Benefit 2 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="premium-card h-100 shadow-none border-0 bg-white pulse-hover">
                    <div class="service-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fa-solid fa-tags"></i></div>
                    <h4 class="fw-bold mb-3">Unbeatable Prices</h4>
                    <p class="text-muted mb-0">Get the highest quality SMM services at the lowest market rates. As a leading platform, we pass the savings directly to you.</p>
                </div>
            </div>
            <!-- Benefit 3 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="premium-card h-100 shadow-none border-0 bg-white pulse-hover">
                    <div class="service-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;"><i class="fa-solid fa-user-shield"></i></div>
                    <h4 class="fw-bold mb-3">100% Safe & Anonymous</h4>
                    <p class="text-muted mb-0">Your privacy is our priority. We never ask for passwords, and all our growth methods are fully compliant with platform terms.</p>
                </div>
            </div>
            <!-- Benefit 4 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="premium-card h-100 shadow-none border-0 bg-white pulse-hover">
                    <div class="service-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899;"><i class="fa-solid fa-chart-line"></i></div>
                    <h4 class="fw-bold mb-3">Real-Time Analytics</h4>
                    <p class="text-muted mb-0">Track your growth in real-time. Our advanced dashboard gives you full transparency on every order and every interaction.</p>
                </div>
            </div>
            <!-- Benefit 5 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="premium-card h-100 shadow-none border-0 bg-white pulse-hover">
                    <div class="service-icon" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;"><i class="fa-solid fa-bolt"></i></div>
                    <h4 class="fw-bold mb-3">Viral-Speed Delivery</h4>
                    <p class="text-muted mb-0">Don't miss the momentum. Our automated systems ensure your engagement starts arriving within minutes of your order.</p>
                </div>
            </div>
            <!-- Benefit 6 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="premium-card h-100 shadow-none border-0 bg-white pulse-hover">
                    <div class="service-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class="fa-solid fa-headset"></i></div>
                    <h4 class="fw-bold mb-3">Expert Growth Partners</h4>
                    <p class="text-muted mb-0">You're not alone. Our team of social media experts is available 24/7 to help you strategize and reach your influence goals.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
@include('partials.faq')
@endsection

@push('scripts')
<script>
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNav');
        if (nav) {
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        }
    });
</script>
@endpush
