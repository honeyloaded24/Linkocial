<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ $siteName }}</title>
    <meta name="description" content="{{ $description }}">

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
            --primary: {{ $meta['color'] }};
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

        .service-hero {
            padding: 140px 0 80px;
            background: radial-gradient(circle at 50% 0%, {{ $meta['color'] }}11, transparent);
            text-align: center;
        }

        @media (max-width: 768px) {
            .service-hero { padding: 80px 0 40px; }
            .service-hero h1 { font-size: 2.25rem !important; }
            .btn-service { padding: 0.875rem 2rem; width: 100%; }
            .d-flex.justify-content-center.gap-3 { flex-direction: column; }
        }

        .service-icon-large {
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

        .text-gradient {
            background: linear-gradient(135deg, var(--primary), #14b8a6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        .feature-tag {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .btn-service {
            background-color: var(--primary);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-service:hover {
            background-color: #000000;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

    @include('partials.navbar')

    <header class="service-hero">
        <div class="container">
            <div class="feature-tag">🚀 Delivery in Minutes</div>
            <div class="service-icon-large">
                <i class="{{ $meta['icon'] }}"></i>
            </div>
            <h1 class="display-4 fw-extrabold mb-3">{{ $name }}</h1>
            <p class="fs-4 text-muted mb-4 mx-auto" style="max-width: 800px;">The most reliable way to <span class="fw-bold text-dark">{{ $keyword }}</span> with viral-speed delivery and 100% organic-looking results.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="{{ route('register') }}" class="btn-service btn-lg">Get Started Now</a>
                <a href="{{ route('platform', ['platform' => $platformSlug]) }}" class="btn btn-outline-dark btn-lg px-5 py-3 rounded-pill fw-bold">All {{ $platform }} Promotions</a>
            </div>

            <div class="mt-5 d-flex flex-column align-items-center">
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 gap-md-4 mb-4">
                    <!-- Stars -->
                    <div class="text-danger d-flex gap-1 fs-5">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <!-- Avatars -->
                    <div class="avatar-group d-flex">
                        <img src="https://i.pravatar.cc/100?u=7" class="rounded-circle border border-white shadow-sm" style="width: 42px; height: 42px; margin-right: -15px; object-fit: cover;" alt="User">
                        <img src="https://i.pravatar.cc/100?u=12" class="rounded-circle border border-white shadow-sm" style="width: 42px; height: 42px; margin-right: -15px; object-fit: cover;" alt="User">
                        <img src="https://i.pravatar.cc/100?u=5" class="rounded-circle border border-white shadow-sm" style="width: 42px; height: 42px; margin-right: -15px; object-fit: cover;" alt="User">
                        <img src="https://i.pravatar.cc/100?u=11" class="rounded-circle border border-white shadow-sm" style="width: 42px; height: 42px; object-fit: cover;" alt="User">
                    </div>
                    <!-- Stats Ticker -->
                    <div class="text-start position-relative overflow-hidden" style="min-width: 180px; height: 40px;">
                        <div id="stats-ticker" class="ticker-content">
                            <div class="ticker-item active">
                                <div class="fw-bold small text-dark"><i class="fa-solid fa-thumbs-up text-warning me-1"></i> <span id="purchased-count">12,482</span> people</div>
                                <div class="text-danger small fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">purchased 2+ times</div>
                            </div>
                            <div class="ticker-item">
                                <div class="fw-bold small text-dark"><i class="fa-solid fa-bolt text-success me-1"></i> 1,000 {{ $serviceUnit }}</div>
                                <div class="text-success small fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">delivered 1 minute ago</div>
                            </div>
                            <div class="ticker-item">
                                <div class="fw-bold small text-dark"><i class="fa-solid fa-check-double text-info me-1"></i> 6,500 {{ $serviceUnit }}</div>
                                <div class="text-info small fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">delivered 3 minutes ago</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trust Badges Bar -->
                <div class="px-4 py-3 rounded-4 d-flex flex-wrap justify-content-center gap-4 shadow-sm" style="background-color: #f0f9ff; border: 1px solid rgba(99, 102, 241, 0.1);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bolt text-info"></i>
                        <span class="text-dark fw-bold small">Instant Delivery Start</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-check text-info"></i>
                        <span class="text-dark fw-bold small">100% Growth Guarantee</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-headset text-info"></i>
                        <span class="text-dark fw-bold small">24/7 Expert Support</span>
                    </div>
                </div>
            </div>

            <style>
                .ticker-content {
                    position: relative;
                    height: 100%;
                }
                .ticker-item {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    opacity: 0;
                    transform: translateY(10px);
                    transition: all 0.5s ease;
                    pointer-events: none;
                }
                .ticker-item.active {
                    opacity: 1;
                    transform: translateY(0);
                    pointer-events: auto;
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Counter Logic
                    const countEl = document.getElementById('purchased-count');
                    const target = 12482 + Math.floor(Math.random() * 50);
                    let current = target - 30;
                    
                    const updateCount = () => {
                        if (current < target) {
                            current++;
                            countEl.innerText = current.toLocaleString();
                            setTimeout(updateCount, 100);
                        }
                    };
                    updateCount();

                    // Ticker Logic
                    const items = document.querySelectorAll('.ticker-item');
                    let currentIndex = 0;

                    setInterval(() => {
                        items[currentIndex].classList.remove('active');
                        currentIndex = (currentIndex + 1) % items.length;
                        items[currentIndex].classList.add('active');
                    }, 4000);
                });
            </script>
        </div>
    </header>

    <!-- Why It Matters Section -->
    @if($whyMatter)
        <section class="py-5 border-top">
            <div class="container py-5">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="feature-tag mb-4">The Impact of Growth</div>
                        <h2 class="fw-bold display-5 mb-4">{{ $whyMatter['title'] }}</h2>
                        <p class="text-muted fs-5 mb-0">{{ $whyMatter['desc'] }}</p>
                    </div>
                    <div class="col-lg-6">
                        <div class="row g-4">
                            @foreach($whyMatter['points'] as $point)
                                <div class="col-12">
                                    <div class="d-flex gap-4 p-4 rounded-4 bg-soft border">
                                        <div class="flex-shrink-0">
                                            <div class="bg-white shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fa-solid fa-check text-primary"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1">{{ $point['title'] }}</h5>
                                            <p class="text-muted small mb-0">{{ $point['desc'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Who Is This For Section -->
    @if(count($audience) > 0)
        <section class="py-5 bg-white border-top">
            <div class="container py-5">
                <div class="text-center mb-5">
                    <h2 class="fw-bold fs-1 mb-3">Who is This Service For?</h2>
                    <p class="text-muted fs-5">Linkocial powers growth for every level of the social ecosystem.</p>
                </div>
                <div class="row g-4">
                    @foreach($audience as $person)
                        <div class="col-lg-4">
                            <div class="p-4 p-lg-5 rounded-5 border h-100 text-center hover-lift transition-all">
                                <div class="mb-4 text-primary display-4">
                                    <i class="{{ $person['icon'] }}"></i>
                                </div>
                                <h4 class="fw-bold mb-3">{{ $person['name'] }}</h4>
                                <p class="text-muted small mb-0 leading-relaxed">{{ $person['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Service Tiers Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-1 mb-3">Choose Your Quality Tier</h2>
                <p class="text-muted fs-5">Select the perfect level of engagement for your growth goals.</p>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach($tiers as $index => $tier)
                    <div class="col-lg-4">
                        <div class="card h-100 border-0 rounded-5 shadow-sm p-4 tier-card @if($index == 1) active-tier @elseif($index == 2) vip-tier @endif">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="tier-icon">
                                    <i class="{{ $tier['icon'] }}"></i>
                                </div>
                                @if($index == 1)
                                    <span class="badge rounded-pill bg-primary px-3 py-2">MOST POPULAR</span>
                                @elseif($index == 2)
                                    <span class="badge rounded-pill bg-dark px-3 py-2 text-white">MAX IMPACT</span>
                                @endif
                            </div>
                            <h3 class="fw-bold mb-4">{{ $tier['name'] }}</h3>
                            <ul class="list-unstyled mb-5">
                                @foreach($tier['features'] as $feature)
                                    <li class="mb-3 d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-check text-success"></i>
                                        <span class="text-muted fw-semibold small">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}" class="btn @if($index == 2) btn-dark @else btn-outline-dark @endif w-100 rounded-pill py-3 fw-bold">Select {{ $tier['name'] }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-5 bg-soft">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-1 mb-3">Benefits of {{ str_replace('Buy', 'Buying', $name) }}</h2>
                <p class="text-muted fs-5 mx-auto" style="max-width: 700px;">Unlock the full potential of your {{ $platform }} account with strategic engagement from Linkocial.</p>
            </div>
            <div class="row g-4">
                @foreach($benefits as $benefit)
                    <div class="col-md-6 col-lg-3">
                        <div class="text-center p-4">
                            <div class="mb-4 text-primary fs-1">
                                <i class="{{ $benefit['icon'] }}"></i>
                            </div>
                            <h4 class="fw-bold mb-3">{{ $benefit['title'] }}</h4>
                            <p class="text-muted small mb-0">{{ $benefit['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Original Why Choose Section -->
    <section class="py-4 py-lg-5">
        <div class="container py-5 text-center">
            <h2 class="fw-bold mb-5">Why Choose {{ $siteName }} to {{ $name }}?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                        <i class="fa-solid fa-bolt-lightning text-warning fs-1 mb-3"></i>
                        <h4 class="fw-bold">Instant Delivery</h4>
                        <p class="text-muted small">Your order starts processing in minutes. We provide the fastest delivery speeds in the industry.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                        <i class="fa-solid fa-shield-check text-success fs-1 mb-3"></i>
                        <h4 class="fw-bold">Organic & Safe</h4>
                        <p class="text-muted small">We use compliant growth methods that ensure your account remains 100% safe and organic-looking.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
                        <i class="fa-solid fa-headset text-primary fs-1 mb-3"></i>
                        <h4 class="fw-bold">24/7 Support</h4>
                        <p class="text-muted small">Our team of social media experts is always here to help you achieve your growth goals.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.how-it-works')

    <!-- FAQ Section -->
    <section class="py-5 bg-white">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold fs-1 mb-3">Frequently Asked Questions</h2>
                        <p class="text-muted">Everything you need to know about {{ str_replace('Buy', 'Buying', $name) }} and our delivery process.</p>
                    </div>
                    <div class="accordion accordion-flush" id="serviceFaq">
                        @foreach($faqs as $i => $faq)
                            <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold py-4 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{{ $i }}">
                                        {{ $faq['q'] }}
                                    </button>
                                </h2>
                                <div id="faq-{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#serviceFaq">
                                    <div class="accordion-body py-4 px-4 text-muted">
                                        {{ $faq['a'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.payment-methods')
    @include('partials.footer')

    <style>
        .tier-card {
            transition: all 0.3s ease;
            background: #ffffff;
            border: 1px solid var(--border) !important;
        }
        .tier-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08) !important;
        }
        .active-tier {
            border: 2px solid var(--primary) !important;
            background: linear-gradient(to bottom, #ffffff, #f5f3ff);
        }
        .vip-tier {
            border: 1px solid #000000 !important;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        }
        .tier-icon {
            width: 50px;
            height: 50px;
            background: #f8fafc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--primary);
        }
        .vip-tier .tier-icon {
            background: #000000;
            color: #ffffff;
        }
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border-color: var(--primary) !important;
        }
        .transition-all { transition: all 0.3s ease; }
        .accordion-button:not(.collapsed) {
            background-color: var(--bg-soft);
            color: var(--primary);
            box-shadow: none;
        }
        .accordion-button:focus {
            box-shadow: none;
            border-color: var(--border);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
