<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }} - World's #1 Social Media Marketing Platform for Followers, Likes, Comments</title>
    <meta name="description" content="Linkocial is the world's most reliable SMM platform. Get viral-speed delivery on Instagram, TikTok, and YouTube services at unbeatable prices. Register today for professional growth.">

    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ $siteFavicon }}">
    @endif

    <script>
        // Apply theme IMMEDIATELY to prevent flash of wrong colors
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #14b8a6;
            --bg-body: #ffffff;
            --bg-soft: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border: #e2e8f0;
            --card-shadow: 0 10px 30px -10px rgba(0,0,0,0.08);
        }

        /* Logo Theme Switching */
        .logo-dark { display: none !important; }
        .logo-light { display: block !important; }

        [data-theme="dark"] .logo-dark { display: block !important; }
        [data-theme="dark"] .logo-light { display: none !important; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
        }

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
    </style>
</head>
<body>

    @include('partials.navbar')

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center text-lg-start">
                    <span class="hero-badge mb-3 d-inline-block">
                        🚀 High-Performance Growth
                    </span>
                    <h1 class="hero-title">Buy Instagram, TikTok Followers, YouTube Subscribers, And More In Minutes.</h1>
                    <p class="hero-subtitle mx-auto mx-lg-0">With <span class="text-gradient">Linkocial</span>, you can buy hundreds of real TikTok & Instagram Followers, Comments, Likes, Views, and YouTube Subscribers from real accounts.</p>
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
                        <a href="{{ route('register') }}" class="btn btn-premium btn-lg">Register</a>
                        <a href="#features" class="btn btn-outline-premium btn-lg">Explore Features</a>
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
                <div class="col-lg-6 mt-5 mt-lg-0 text-center d-none d-lg-block">
                    <div class="floating-mockup">
                        <img src="{{ asset('branding/undraw_social-media-interactions_5rls.svg') }}" alt="Social Media Growth Illustration" class="img-fluid" style="max-height: 450px;">
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
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-1 mb-3">Choose Your Platform</h2>
                <p class="text-muted fs-5">Select a network to see specialized growth services.</p>
            </div>
            <div class="row g-4">
                @foreach($channels as $channel)
                    <div class="col-6 col-lg-3">
                        <a href="{{ route('platform', ['platform' => strtolower($channel['name'] == 'X' ? 'x' : $channel['name'])]) }}" class="text-decoration-none">
                            <div class="premium-card text-center py-4 px-3 h-100">
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

    <!-- Why Buy Section -->
    @include('partials.why-buy')

    <!-- How It Works Section -->
    @include('partials.how-it-works', ['bg' => 'bg-soft'])

    <!-- Platform Blends Section -->
    @include('partials.platform-blends')

    <!-- Benefits Section -->
    <section class="py-5 bg-soft" id="features">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-1 mb-3">Why Professionals Choose Us</h2>
                <p class="text-muted fs-5">We don't just provide services; we fuel your social media success.</p>
            </div>
            <div class="row g-4">
                <!-- Benefit 1 -->
                <div class="col-md-4">
                    <div class="premium-card h-100 shadow-none border-0 bg-white">
                        <div class="service-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fa-solid fa-rocket"></i></div>
                        <h4 class="fw-bold mb-3">Skyrocket Your Reach</h4>
                        <p class="text-muted mb-0">Get noticed by the right audience and go viral faster. Our services are designed to trigger social algorithms for maximum visibility.</p>
                    </div>
                </div>
                <!-- Benefit 2 -->
                <div class="col-md-4">
                    <div class="premium-card h-100 shadow-none border-0 bg-white">
                        <div class="service-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fa-solid fa-tags"></i></div>
                        <h4 class="fw-bold mb-3">Unbeatable Prices</h4>
                        <p class="text-muted mb-0">Get the highest quality SMM services at the lowest market rates. As a leading platform, we pass the savings directly to you.</p>
                    </div>
                </div>
                <!-- Benefit 3 -->
                <div class="col-md-4">
                    <div class="premium-card h-100 shadow-none border-0 bg-white">
                        <div class="service-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;"><i class="fa-solid fa-user-shield"></i></div>
                        <h4 class="fw-bold mb-3">100% Safe & Anonymous</h4>
                        <p class="text-muted mb-0">Your privacy is our priority. We never ask for passwords, and all our growth methods are fully compliant with platform terms.</p>
                    </div>
                </div>
                <!-- Benefit 4 -->
                <div class="col-md-4">
                    <div class="premium-card h-100 shadow-none border-0 bg-white">
                        <div class="service-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899;"><i class="fa-solid fa-chart-line"></i></div>
                        <h4 class="fw-bold mb-3">Real-Time Analytics</h4>
                        <p class="text-muted mb-0">Track your growth in real-time. Our advanced dashboard gives you full transparency on every order and every interaction.</p>
                    </div>
                </div>
                <!-- Benefit 5 -->
                <div class="col-md-4">
                    <div class="premium-card h-100 shadow-none border-0 bg-white">
                        <div class="service-icon" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;"><i class="fa-solid fa-bolt"></i></div>
                        <h4 class="fw-bold mb-3">Viral-Speed Delivery</h4>
                        <p class="text-muted mb-0">Don't miss the momentum. Our automated systems ensure your engagement starts arriving within minutes of your order.</p>
                    </div>
                </div>
                <!-- Benefit 6 -->
                <div class="col-md-4">
                    <div class="premium-card h-100 shadow-none border-0 bg-white">
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

    @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.getElementById('mainNav').classList.add('scrolled');
            } else {
                document.getElementById('mainNav').classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
