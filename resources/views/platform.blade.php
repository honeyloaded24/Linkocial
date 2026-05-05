<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Get Verified {{ $name }} Promotion Services and Growth Booster | {{ $siteName }}</title>
    <meta name="description" content="Boost your {{ $name }} presence instantly with {{ $siteName }}. Get high-quality {{ $name }} promotion services, followers, likes, and views with viral-speed delivery.">

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
            --primary: {{ str_contains($meta['color'], 'linear') ? '#6366f1' : $meta['color'] }};
            --bg-body: #ffffff;
            --bg-soft: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border: #e2e8f0;
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
        }

        .platform-hero {
            padding: 140px 0 80px;
            background: radial-gradient(circle at 50% 0%, {{ str_contains($meta['color'], 'linear') ? 'rgba(99, 102, 241, 0.05)' : $meta['color'].'08' }}, transparent);
            text-align: center;
        }
        @media (max-width: 768px) {
            .platform-hero { padding: 100px 0 40px; }
            .platform-hero h1 { font-size: 2rem !important; }
        }

        .platform-icon-large {
            width: 80px;
            height: 80px;
            background: {{ $meta['color'] }};
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .service-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .service-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.06);
        }

        .btn-premium {
            background: {{ str_contains($meta['color'], 'linear') ? 'var(--primary)' : $meta['color'] }};
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
        }
        .btn-premium:hover {
            background: #000000;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .price-badge {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }
    </style>
</head>
<body>

    @include('partials.navbar')

    <header class="platform-hero">
        <div class="container">
            <div class="platform-icon-large">
                <i class="{{ $meta['icon'] }}"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3" style="color: var(--text-main);">{{ $name }} Promotion Services</h1>
            <p class="fs-5 text-muted mx-auto" style="max-width: 700px;">{{ $meta['desc'] }}</p>
        </div>
    </header>

    <!-- Platform Selection Grids -->
    <section class="py-5">
        <div class="container text-center py-4">
            @if($name == 'Instagram')
                <h2 class="fw-bold fs-1 mb-5">Choose Your Growth Strategy</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.ig-followers') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #bc1888;"><i class="fa-solid fa-users"></i></div>
                                <h4 class="fw-bold text-dark">Instagram Followers</h4>
                                <p class="text-muted small">Gain real, organic-looking followers to boost your profile's social proof in minutes.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #bc1888;">Explore Followers</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.ig-comments') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #e6683c;"><i class="fa-solid fa-comments"></i></div>
                                <h4 class="fw-bold text-dark">Instagram Comments</h4>
                                <p class="text-muted small">Get authentic engagement from real accounts to spark conversations on your posts.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #e6683c;">Explore Comments</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.ig-likes') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #dc2743;"><i class="fa-solid fa-heart"></i></div>
                                <h4 class="fw-bold text-dark">Instagram Likes</h4>
                                <p class="text-muted small">Increase your post reach and visibility with high-quality likes delivered instantly.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #dc2743;">Explore Likes</span>
                            </div>
                        </a>
                    </div>
                </div>
            @elseif($name == 'TikTok')
                <h2 class="fw-bold fs-1 mb-5">Go Viral on TikTok</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.tk-followers') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #000000;"><i class="fa-solid fa-users"></i></div>
                                <h4 class="fw-bold text-dark">TikTok Followers</h4>
                                <p class="text-muted small">Build a massive community and trigger the FYP algorithm with real, active followers.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #000000;">Explore Followers</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.tk-likes') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #ff0050;"><i class="fa-solid fa-heart"></i></div>
                                <h4 class="fw-bold text-dark">TikTok Likes</h4>
                                <p class="text-muted small">Increase your video engagement and viral potential with high-quality TikTok likes.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #ff0050;">Explore Likes</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.tk-views') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #00f2ea;"><i class="fa-solid fa-play"></i></div>
                                <h4 class="fw-bold text-dark">TikTok Views</h4>
                                <p class="text-muted small">Skyrocket your visibility and reach the global FYP with lightning-speed views.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #00f2ea;">Explore Views</span>
                            </div>
                        </a>
                    </div>
                </div>
            @elseif($name == 'YouTube')
                <h2 class="fw-bold fs-1 mb-5">Master YouTube Growth</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.yt-subscribers') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #FF0000;"><i class="fa-brands fa-youtube"></i></div>
                                <h4 class="fw-bold text-dark">YouTube Subscribers</h4>
                                <p class="text-muted small">Build your channel authority and reach monetization milestones with real subscribers.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #FF0000;">Explore Subscribers</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.yt-views') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #FF0000;"><i class="fa-solid fa-play"></i></div>
                                <h4 class="fw-bold text-dark">YouTube Views</h4>
                                <p class="text-muted small">Increase your video reach and search ranking with high-retention YouTube views.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #FF0000;">Explore Views</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.yt-watchtime') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #FF0000;"><i class="fa-solid fa-clock"></i></div>
                                <h4 class="fw-bold text-dark">YouTube Watch Time</h4>
                                <p class="text-muted small">Unlock monetization and earn revenue faster with specialized watch time hours.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #FF0000;">Explore Watch Time</span>
                            </div>
                        </a>
                    </div>
                </div>
            @elseif($name == 'X')
                <h2 class="fw-bold fs-1 mb-5">Dominant Reach on X</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.x-followers') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #000000;"><i class="fa-brands fa-x-twitter"></i></div>
                                <h4 class="fw-bold text-dark">X Followers</h4>
                                <p class="text-muted small">Build your public square authority and gain massive reach with real X followers.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #000000;">Explore Followers</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.x-likes') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #000000;"><i class="fa-solid fa-heart"></i></div>
                                <h4 class="fw-bold text-dark">X Likes</h4>
                                <p class="text-muted small">Trigger the viral algorithm and boost your thread visibility with high-quality likes.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #000000;">Explore Likes</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.x-reposts') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #000000;"><i class="fa-solid fa-share-nodes"></i></div>
                                <h4 class="fw-bold text-dark">X Reposts</h4>
                                <p class="text-muted small">Explode your content reach by getting your threads shared across the entire platform.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #000000;">Explore Reposts</span>
                            </div>
                        </a>
                    </div>
                </div>
            @elseif($name == 'Facebook')
                <h2 class="fw-bold fs-1 mb-5">Master Facebook Engagement</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.fb-page-likes') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #1877F2;"><i class="fa-brands fa-facebook"></i></div>
                                <h4 class="fw-bold text-dark">Page Likes</h4>
                                <p class="text-muted small">Build instant brand trust and search visibility for your Facebook business page.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #1877F2;">Explore Page Likes</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.fb-post-likes') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #1877F2;"><i class="fa-solid fa-heart"></i></div>
                                <h4 class="fw-bold text-dark">Post Likes</h4>
                                <p class="text-muted small">Boost your news feed priority and organic reach with high-speed post likes.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #1877F2;">Explore Post Likes</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.fb-followers') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #1877F2;"><i class="fa-solid fa-users"></i></div>
                                <h4 class="fw-bold text-dark">Profile Followers</h4>
                                <p class="text-muted small">Build your personal influence and reach a massive audience with profile followers.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #1877F2;">Explore Followers</span>
                            </div>
                        </a>
                    </div>
                </div>
            @elseif($name == 'Telegram')
                <h2 class="fw-bold fs-1 mb-5">Scale Your Telegram Community</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.tg-members') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #0088cc;"><i class="fa-brands fa-telegram"></i></div>
                                <h4 class="fw-bold text-dark">Channel Members</h4>
                                <p class="text-muted small">Build instant channel legitimacy and search authority with real Telegram members.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #0088cc;">Explore Members</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.tg-views') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #0088cc;"><i class="fa-solid fa-eye"></i></div>
                                <h4 class="fw-bold text-dark">Post Views</h4>
                                <p class="text-muted small">Create a busy, high-authority channel image with instant views on every update.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #0088cc;">Explore Views</span>
                            </div>
                        </a>
                    </div>
                </div>
            @elseif($name == 'Spotify')
                <h2 class="fw-bold fs-1 mb-5">Dominate the Spotify Algorithm</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.sp-followers') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #1DB954;"><i class="fa-brands fa-spotify"></i></div>
                                <h4 class="fw-bold text-dark">Artist Followers</h4>
                                <p class="text-muted small">Boost your artist authority and get featured on Release Radar with real Spotify followers.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #1DB954;">Explore Followers</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.sp-plays') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #1DB954;"><i class="fa-solid fa-play"></i></div>
                                <h4 class="fw-bold text-dark">Track Plays</h4>
                                <p class="text-muted small">Trigger Discover Weekly and increase your royalty potential with high-retention plays.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #1DB954;">Explore Plays</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.sp-listeners') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #1DB954;"><i class="fa-solid fa-headphones"></i></div>
                                <h4 class="fw-bold text-dark">Monthly Listeners</h4>
                                <p class="text-muted small">Build a massive, consistent audience that attracts labels and playlist curators.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #1DB954;">Explore Listeners</span>
                            </div>
                        </a>
                    </div>
                </div>
            @elseif($name == 'Audiomack')
                <h2 class="fw-bold fs-1 mb-5">Trend on Audiomack</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.am-followers') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #FFA200;"><i class="fa-solid fa-users"></i></div>
                                <h4 class="fw-bold text-dark">Artist Followers</h4>
                                <p class="text-muted small">Build your artist status and secure your spot in the Audiomack Trending charts.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #FFA200;">Explore Followers</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <a href="{{ route('services.am-plays') }}" class="text-decoration-none">
                            <div class="service-card h-100">
                                <div class="platform-icon-large mb-3" style="background: #FFA200;"><i class="fa-solid fa-play"></i></div>
                                <h4 class="fw-bold text-dark">Track Plays</h4>
                                <p class="text-muted small">Increase your play velocity and explode your visibility on the Audiomack platform.</p>
                                <span class="btn btn-premium btn-sm mt-3 w-100" style="background: #FFA200;">Explore Plays</span>
                            </div>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- Unified Engagement Mastery Section (Only Once) -->
    @if(in_array($name, ['Instagram', 'TikTok', 'YouTube', 'X', 'Facebook', 'Telegram', 'Spotify', 'Audiomack']))
        <section class="py-5 bg-soft">
            <div class="container py-5">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-5 mb-lg-0 text-center text-lg-start">
                        <h2 class="fw-bold mb-4 fs-1">Boost Your Reach <span class="text-primary">In Minutes</span>.</h2>
                        <p class="text-muted fs-5 mb-4">Our specialized infrastructure handles Streams, Plays, and Global Interactions with lightning speed. Dominate your industry by combining our premium growth services.</p>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-4 border shadow-sm h-100">
                                    <i class="fa-solid fa-share text-primary mb-2 fs-4"></i>
                                    <div class="fw-bold">Global Shares</div>
                                    <div class="text-muted small">Viral Speed</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-4 border shadow-sm h-100">
                                    <i class="fa-solid fa-repeat text-success mb-2 fs-4"></i>
                                    <div class="fw-bold">Artist Velocity</div>
                                    <div class="text-muted small">Instant Action</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-4 border shadow-sm h-100">
                                    <i class="fa-solid fa-paper-plane text-info mb-2 fs-4"></i>
                                    <div class="fw-bold">Fan Network</div>
                                    <div class="text-muted small">Organic Flow</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-4 border shadow-sm h-100">
                                    <i class="fa-solid fa-eye text-warning mb-2 fs-4"></i>
                                    <div class="fw-bold">Artist Authority</div>
                                    <div class="text-muted small">Real Traffic</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 px-lg-5">
                        <div class="p-5 bg-white rounded-5 shadow-lg border text-center">
                            <h4 class="fw-bold mb-3">Ready to Dominate?</h4>
                            <p class="text-muted mb-4">Join thousands of influencers and brands who use our premium infrastructure for daily growth.</p>
                            @auth
                                <a href="{{ route('orders.create', ['platform' => $name]) }}" class="btn btn-premium btn-lg w-100 py-3 shadow">{{ $name }} Promotion Now</a>
                            @else
                                <a href="{{ route('register') }}" class="btn btn-premium btn-lg w-100 py-3 shadow">Join Linkocial Now</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @else
        <!-- Fallback Service List for Other Platforms -->
        <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    @forelse($services as $service)
                        <div class="col-lg-4 col-md-6">
                            <div class="service-card h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold mb-0 pe-3">{{ $service->name }}</h5>
                                    @if($service->is_hot)
                                        <span class="badge bg-danger">HOT</span>
                                    @endif
                                </div>
                                <p class="text-muted small flex-grow-1">{{ Str::limit($service->description, 100) }}</p>
                                <hr class="opacity-10">
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <div>
                                        <span class="text-muted small d-block">Starting from</span>
                                        <span class="price-badge">${{ number_format($service->selling_price, 2) }}</span>
                                        <span class="text-muted small">/ 1k</span>
                                    </div>
                                    <a href="{{ auth()->check() ? route('orders.create', ['platform' => $name]) : route('register') }}" class="btn btn-premium btn-sm">
                                        {{ auth()->check() ? 'Order Now' : 'Register' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <p class="text-muted">No specific services found for this platform yet.</p>
                            <a href="{{ auth()->check() ? route('orders.create', ['platform' => $name]) : route('register') }}" class="btn btn-premium">
                                {{ auth()->check() ? 'Go to Dashboard' : 'Register' }}
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    <!-- How It Works Section -->
    @include('partials.how-it-works', ['bg' => 'bg-soft'])

    <!-- Payment Methods Section -->
    @include('partials.payment-methods')

    @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
