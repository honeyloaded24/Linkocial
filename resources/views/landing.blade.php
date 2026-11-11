@extends('layouts.marketing')

@section('title', $siteName . ' - One Platform. Four Ways to Get More Done.')
@section('meta_description', 'Verify numbers, boost your social media, subscribe to affordable data, and access digital products — all from one powerful Linkocial account.')

@push('styles')
<style>
    :root {
        --c-verify: #0284c7;
        --c-verify-soft: rgba(2, 132, 199, 0.08);
        --c-grow: #6366f1;
        --c-grow-soft: rgba(99, 102, 241, 0.08);
        --c-connect: #10b981;
        --c-connect-soft: rgba(16, 185, 129, 0.08);
        --c-shop: #f59e0b;
        --c-shop-soft: rgba(245, 158, 11, 0.08);
    }

    .text-gradient {
        background: linear-gradient(135deg, #6366f1 0%, #0284c7 50%, #10b981 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-section {
        padding: 130px 0 70px;
        background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.09), transparent 70%),
                    radial-gradient(circle at 10% 40%, rgba(2, 132, 199, 0.06), transparent 50%),
                    radial-gradient(circle at 90% 40%, rgba(16, 185, 129, 0.06), transparent 50%);
        position: relative;
        overflow: hidden;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        background: #ffffff;
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 50rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: #4f46e5;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.08);
    }

    .hero-title {
        font-size: 3.35rem;
        font-weight: 800;
        line-height: 1.15;
        color: var(--text-main);
        letter-spacing: -0.02em;
    }

    @media (max-width: 768px) {
        .hero-title { font-size: 2.2rem; }
        .hero-subtitle { font-size: 1rem; }
        .hero-tagline span { font-size: 1.1rem; }
    }

    .hero-tagline {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        font-weight: 800;
        font-size: 1.35rem;
        margin-bottom: 1.25rem;
    }

    .tag-verify { color: var(--c-verify); }
    .tag-grow { color: var(--c-grow); }
    .tag-connect { color: var(--c-connect); }
    .tag-shop { color: var(--c-shop); }
    .tag-dot { color: #cbd5e1; font-size: 1rem; }

    .hero-subtitle {
        font-size: 1.2rem;
        line-height: 1.6;
        color: var(--text-muted);
        max-width: 580px;
    }

    .btn-hero-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #ffffff;
        font-weight: 700;
        font-size: 1.05rem;
        padding: 0.9rem 2.2rem;
        border-radius: 14px;
        border: none;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-hero-primary:hover {
        background: linear-gradient(135deg, #4338ca, #4f46e5);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 14px 30px -5px rgba(79, 70, 229, 0.5);
    }

    .btn-hero-secondary {
        background: #ffffff;
        color: var(--text-main);
        font-weight: 700;
        font-size: 1.05rem;
        padding: 0.9rem 2rem;
        border-radius: 14px;
        border: 1.5px solid var(--border);
        transition: all 0.25s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-hero-secondary:hover {
        border-color: #6366f1;
        color: #6366f1;
        background: rgba(99, 102, 241, 0.04);
        transform: translateY(-2px);
    }

    /* Concept Highlight Ticker */
    .concept-ticker {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1rem 1.5rem;
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }

    .concept-ticker-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text-main);
    }

    /* Live Multi-Product Showcase Card */
    .showcase-container {
        position: relative;
    }

    .showcase-card {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.12);
        padding: 1.75rem;
        position: relative;
    }

    .showcase-item {
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        margin-bottom: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .showcase-item:last-child {
        margin-bottom: 0;
    }

    .showcase-item:hover {
        transform: translateX(4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.04);
    }

    .showcase-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    /* Four Pillars Section */
    .pillar-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 2rem 1.5rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    .pillar-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: transparent;
        transition: background 0.3s ease;
    }

    .pillar-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
    }

    .pillar-card.card-verify:hover::before { background: var(--c-verify); }
    .pillar-card.card-grow:hover::before { background: var(--c-grow); }
    .pillar-card.card-connect:hover::before { background: var(--c-connect); }
    .pillar-card.card-shop:hover::before { background: var(--c-shop); }

    .pillar-card.card-verify:hover { border-color: rgba(2, 132, 199, 0.3); }
    .pillar-card.card-grow:hover { border-color: rgba(99, 102, 241, 0.3); }
    .pillar-card.card-connect:hover { border-color: rgba(16, 185, 129, 0.3); }
    .pillar-card.card-shop:hover { border-color: rgba(245, 158, 11, 0.3); }

    .pillar-icon-box {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 1.25rem;
    }

    .pillar-badge {
        font-size: 0.725rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 0.35rem 0.75rem;
        border-radius: 50rem;
    }

    .feature-tag {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.3rem 0.75rem;
        border-radius: 8px;
    }

    /* Step Card */
    .step-number {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #6366f1;
        color: #ffffff;
        font-weight: 800;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
        box-shadow: 0 6px 15px rgba(99, 102, 241, 0.3);
    }

    /* FAQ accordion */
    .faq-item {
        border: 1px solid var(--border);
        border-radius: 16px;
        background: #ffffff;
        margin-bottom: 1rem;
        overflow: hidden;
        transition: border-color 0.2s ease;
    }

    .faq-item:hover {
        border-color: #cbd5e1;
    }

    .faq-trigger {
        width: 100%;
        text-align: left;
        padding: 1.35rem 1.5rem;
        background: transparent;
        border: none;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-main);
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .faq-content {
        padding: 0 1.5rem 1.35rem;
        color: var(--text-muted);
        line-height: 1.6;
        display: none;
    }

    .faq-item.active .faq-content {
        display: block;
    }

    .faq-item.active .faq-chevron {
        transform: rotate(180deg);
    }

    .faq-chevron {
        transition: transform 0.25s ease;
    }
</style>
@endpush

@section('content')

<!-- HERO SECTION -->
<header class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left Column: Copy & Actions -->
            <div class="col-lg-7 text-center text-lg-start">
                <div class="hero-badge mb-3" data-aos="fade-up">
                    <i class="fa-solid fa-bolt-lightning text-primary"></i>
                    <span>Everything You Need. All in One Platform.</span>
                </div>

                <h1 class="hero-title mb-3" data-aos="fade-up" data-aos-delay="100">
                    One Platform. <br>
                    <span class="text-gradient">Four Ways to Get More Done.</span>
                </h1>

                <!-- Action Tagline Hook -->
                <div class="hero-tagline justify-content-center justify-content-lg-start" data-aos="fade-up" data-aos-delay="150">
                    <span class="tag-verify"><i class="fa-solid fa-mobile-screen-button me-1"></i> Verify.</span>
                    <span class="tag-dot">•</span>
                    <span class="tag-grow"><i class="fa-solid fa-arrow-trend-up me-1"></i> Grow.</span>
                    <span class="tag-dot">•</span>
                    <span class="tag-connect"><i class="fa-solid fa-bolt me-1"></i> Connect.</span>
                    <span class="tag-dot">•</span>
                    <span class="tag-shop"><i class="fa-solid fa-bag-shopping me-1"></i> Shop.</span>
                </div>

                <p class="hero-subtitle mb-4 mx-auto mx-lg-0" data-aos="fade-up" data-aos-delay="200">
                    Get virtual numbers for verification, boost your social media, subscribe to affordable data, and access digital products — all from one powerful Linkocial account.
                </p>

                <!-- Call to Action Buttons -->
                <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3 mb-5" data-aos="fade-up" data-aos-delay="250">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-hero-primary">
                            <span>Open Dashboard</span>
                            <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn-hero-primary">
                            <span>Get Started Free</span>
                            <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    @endauth
                    <a href="#pillars" class="btn-hero-secondary">
                        <i class="fa-solid fa-layer-group text-primary"></i>
                        <span>Explore 4 Services</span>
                    </a>
                </div>

                <!-- Concept Pill Ticker -->
                <div class="concept-ticker justify-content-center justify-content-lg-start" data-aos="fade-up" data-aos-delay="300">
                    <div class="concept-ticker-item">
                        <i class="fa-solid fa-circle-check text-success small"></i>
                        <span>Number Verification</span>
                    </div>
                    <div class="concept-ticker-item">
                        <i class="fa-solid fa-circle-check text-primary small"></i>
                        <span>Social Media Boosting</span>
                    </div>
                    <div class="concept-ticker-item">
                        <i class="fa-solid fa-circle-check text-info small"></i>
                        <span>Data Subscription</span>
                    </div>
                    <div class="concept-ticker-item">
                        <i class="fa-solid fa-circle-check text-warning small"></i>
                        <span>Digital Marketplace</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Interactive Multi-Service Preview -->
            <div class="col-lg-5" data-aos="fade-left" data-aos-delay="300">
                <div class="showcase-container">
                    <div class="showcase-card">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block rounded-circle bg-success" style="width: 10px; height: 10px;"></span>
                                <span class="small fw-bold text-dark text-uppercase" style="letter-spacing: 0.5px;">Live Capabilities</span>
                            </div>
                            <span class="badge bg-light text-muted border small">All-in-One Engine</span>
                        </div>

                        <!-- 1. Verify -->
                        <div class="showcase-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="showcase-icon" style="background: var(--c-verify-soft); color: var(--c-verify);">
                                    <i class="fa-solid fa-mobile-screen"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small text-dark">Number Verification</div>
                                    <div class="text-muted x-small">USA • WhatsApp OTP: <strong>594-201</strong></div>
                                </div>
                            </div>
                            <span class="badge" style="background: rgba(2, 132, 199, 0.15); color: var(--c-verify);">Received</span>
                        </div>

                        <!-- 2. Grow -->
                        <div class="showcase-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="showcase-icon" style="background: var(--c-grow-soft); color: var(--c-grow);">
                                    <i class="fa-solid fa-arrow-trend-up"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small text-dark">Social Media Growth</div>
                                    <div class="text-muted x-small">Instagram • <strong>+2,500 Followers</strong></div>
                                </div>
                            </div>
                            <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: var(--c-grow);">Delivered</span>
                        </div>

                        <!-- 3. Connect -->
                        <div class="showcase-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="showcase-icon" style="background: var(--c-connect-soft); color: var(--c-connect);">
                                    <i class="fa-solid fa-wifi"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small text-dark">Data & Utilities</div>
                                    <div class="text-muted x-small">MTN Corporate Gifting • <strong>10 GB</strong></div>
                                </div>
                            </div>
                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--c-connect);">Active</span>
                        </div>

                        <!-- 4. Shop -->
                        <div class="showcase-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="showcase-icon" style="background: var(--c-shop-soft); color: var(--c-shop);">
                                    <i class="fa-solid fa-key"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small text-dark">Digital Marketplace</div>
                                    <div class="text-muted x-small">2025 Aged Gmail Account</div>
                                </div>
                            </div>
                            <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--c-shop);">Instant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- TRUST & STATS BAR -->
<section class="py-4 bg-white border-top border-bottom">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-3 text-primary">{{ number_format($stats['total_orders']) }}+</div>
                <div class="text-muted small fw-bold text-uppercase">Completed Tasks</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-3 text-dark">{{ number_format($stats['active_users']) }}+</div>
                <div class="text-muted small fw-bold text-uppercase">Active Members</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-3" style="color: var(--c-connect);">99.9%</div>
                <div class="text-muted small fw-bold text-uppercase">Instant Automation</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold fs-3" style="color: var(--c-verify);">100+</div>
                <div class="text-muted small fw-bold text-uppercase">Countries Supported</div>
            </div>
        </div>
    </div>
</section>

<!-- THE FOUR PILLARS SECTION -->
<section class="py-5" id="pillars" style="background-color: var(--bg-soft);">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold small mb-2">
                FOUR PILLARS • ONE ACCOUNT
            </span>
            <h2 class="fw-bold fs-1 mb-2">Everything You Need To Succeed Online</h2>
            <p class="text-muted fs-5 mx-auto" style="max-width: 650px;">
                Replace four separate subscriptions with one powerful Linkocial account. Simple, lightning-fast, and wallet-friendly.
            </p>
        </div>

        <div class="row g-4">
            <!-- Pillar 1: Verify -->
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="pillar-card card-verify">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="pillar-icon-box" style="background: var(--c-verify-soft); color: var(--c-verify);">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <span class="pillar-badge" style="background: var(--c-verify-soft); color: var(--c-verify);">Verify</span>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: var(--text-main); font-size: 1.2rem;">Number Verification</h5>
                    <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.55;">
                        Rent clean, non-VoIP temporary phone numbers from 100+ countries to receive instant OTP verification codes for any app.
                    </p>
                    <div class="d-flex flex-wrap gap-1 mb-4">
                        <span class="feature-tag">WhatsApp</span>
                        <span class="feature-tag">Telegram</span>
                        <span class="feature-tag">OpenAI</span>
                        <span class="feature-tag">Google</span>
                    </div>
                    <a href="{{ route('services.vn-marketing') }}" class="btn btn-outline-primary rounded-pill fw-bold w-100 py-2 mt-auto" style="border-color: var(--c-verify); color: var(--c-verify);">
                        Get Virtual Numbers <i class="fa-solid fa-arrow-right ms-1 small"></i>
                    </a>
                </div>
            </div>

            <!-- Pillar 2: Grow -->
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="pillar-card card-grow">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="pillar-icon-box" style="background: var(--c-grow-soft); color: var(--c-grow);">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                        </div>
                        <span class="pillar-badge" style="background: var(--c-grow-soft); color: var(--c-grow);">Grow</span>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: var(--text-main); font-size: 1.2rem;">Social Boosting</h5>
                    <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.55;">
                        Skyrocket your online authority. High-speed, high-retention followers, likes, comments, and views designed to trigger algorithms.
                    </p>
                    <div class="d-flex flex-wrap gap-1 mb-4">
                        <span class="feature-tag">Instagram</span>
                        <span class="feature-tag">TikTok</span>
                        <span class="feature-tag">YouTube</span>
                        <span class="feature-tag">X / Twitter</span>
                    </div>
                    <a href="{{ route('orders.create') }}" class="btn btn-outline-primary rounded-pill fw-bold w-100 py-2 mt-auto" style="border-color: var(--c-grow); color: var(--c-grow);">
                        Boost Socials <i class="fa-solid fa-arrow-right ms-1 small"></i>
                    </a>
                </div>
            </div>

            <!-- Pillar 3: Connect -->
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="pillar-card card-connect">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="pillar-icon-box" style="background: var(--c-connect-soft); color: var(--c-connect);">
                            <i class="fa-solid fa-wifi"></i>
                        </div>
                        <span class="pillar-badge" style="background: var(--c-connect-soft); color: var(--c-connect);">Connect</span>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: var(--text-main); font-size: 1.2rem;">Data & Utilities</h5>
                    <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.55;">
                        Instant cheap mobile data bundles, airtime top-ups, prepaid electricity tokens, and Cable TV subscriptions cheaper than bank apps.
                    </p>
                    <div class="d-flex flex-wrap gap-1 mb-4">
                        <span class="feature-tag">MTN / Airtel</span>
                        <span class="feature-tag">Glo / 9mobile</span>
                        <span class="feature-tag">Electricity</span>
                        <span class="feature-tag">Cable TV</span>
                    </div>
                    <a href="{{ route('vtu.data') }}" class="btn btn-outline-success rounded-pill fw-bold w-100 py-2 mt-auto" style="border-color: var(--c-connect); color: var(--c-connect);">
                        Buy Data & Airtime <i class="fa-solid fa-arrow-right ms-1 small"></i>
                    </a>
                </div>
            </div>

            <!-- Pillar 4: Shop -->
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                <div class="pillar-card card-shop">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="pillar-icon-box" style="background: var(--c-shop-soft); color: var(--c-shop);">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <span class="pillar-badge" style="background: var(--c-shop-soft); color: var(--c-shop);">Shop</span>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: var(--text-main); font-size: 1.2rem;">Digital Marketplace</h5>
                    <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.55;">
                        Access pre-verified accounts, aged profiles, developer credentials, and digital tools with 100% automated instant delivery.
                    </p>
                    <div class="d-flex flex-wrap gap-1 mb-4">
                        <span class="feature-tag">Gmail Accounts</span>
                        <span class="feature-tag">Social Accs</span>
                        <span class="feature-tag">VPN / Proxy</span>
                        <span class="feature-tag">Instant Delivery</span>
                    </div>
                    <a href="{{ route('logs.index') }}" class="btn btn-outline-warning rounded-pill fw-bold w-100 py-2 mt-auto" style="border-color: var(--c-shop); color: var(--c-shop);">
                        Browse Marketplace <i class="fa-solid fa-arrow-right ms-1 small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="py-5 bg-white">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-secondary bg-opacity-10 text-dark px-3 py-2 rounded-pill fw-bold small mb-2">
                EFFORTLESS ONBOARDING
            </span>
            <h2 class="fw-bold fs-1 mb-2">Get Started in 3 Simple Steps</h2>
            <p class="text-muted fs-5">No paperwork, no delays. Begin in less than two minutes.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="p-4 rounded-4 bg-light h-100 border">
                    <div class="step-number">1</div>
                    <h4 class="fw-bold mb-2">Create Free Account</h4>
                    <p class="text-muted mb-0">Sign up with just your username and email. No hidden fees or verification hurdles to get started.</p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="p-4 rounded-4 bg-light h-100 border">
                    <div class="step-number" style="background: #10b981;">2</div>
                    <h4 class="fw-bold mb-2">Fund Your Unified Wallet</h4>
                    <p class="text-muted mb-0">Top up via dedicated Virtual Account bank transfer (9PSB, PalmPay) or Card / Mobile Money. One balance powers all services.</p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="p-4 rounded-4 bg-light h-100 border">
                    <div class="step-number" style="background: #0284c7;">3</div>
                    <h4 class="fw-bold mb-2">Verify, Grow, Connect & Shop</h4>
                    <p class="text-muted mb-0">Select your desired service, submit your order, and receive instant automated results directly on your screen.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- UNIFIED WALLET ADVANTAGE -->
<section class="py-5" style="background: linear-gradient(135deg, #1e1b4b, #0f172a); color: #ffffff;">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">
                    The Linkocial Advantage
                </span>
                <h2 class="display-6 fw-bold mb-4">Why Manage 4 Different Apps When You Can Have One?</h2>
                <p class="lead text-light text-opacity-75 mb-4">
                    Tired of funding multiple balances across different websites? With Linkocial, deposit once into your secure wallet and use it instantly across:
                </p>
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-check small text-white"></i>
                        </div>
                        <span class="fw-semibold">Virtual SMS numbers for worldwide OTPs</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-check small text-white"></i>
                        </div>
                        <span class="fw-semibold">Social media boosting across all major networks</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-check small text-white"></i>
                        </div>
                        <span class="fw-semibold">High-speed data bundles & utility bills at wholesale rates</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-check small text-white"></i>
                        </div>
                        <span class="fw-semibold">Pre-verified accounts and digital marketplace tools</span>
                    </div>
                </div>

                @auth
                    <a href="{{ route('wallet.index') }}" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold">
                        Top Up Wallet Now <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold">
                        Create Free Account <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                @endauth
            </div>

            <div class="col-lg-6" data-aos="fade-left">
                <div class="p-4 p-lg-5 rounded-4 bg-white bg-opacity-10 border border-light border-opacity-25 backdrop-blur">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-bold mb-0">Supported Payment Channels</h5>
                        <span class="badge bg-success">Instant Crediting</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-white bg-opacity-10 text-center">
                                <i class="fa-solid fa-credit-card fa-2x mb-2 text-info"></i>
                                <div class="fw-bold small">Debit Cards</div>
                                <span class="text-light text-opacity-50 x-small">Visa, Mastercard, Verve</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-white bg-opacity-10 text-center">
                                <i class="fa-solid fa-building-columns fa-2x mb-2 text-warning"></i>
                                <div class="fw-bold small">Bank Transfer</div>
                                <span class="text-light text-opacity-50 x-small">Automated Virtual Accounts</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-white bg-opacity-10 text-center">
                                <i class="fa-solid fa-building-columns fa-2x mb-2 text-warning"></i>
                                <div class="fw-bold small">Virtual Accounts</div>
                                <span class="text-light text-opacity-50 x-small">9PSB & PalmPay Auto-Credit</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-white bg-opacity-10 text-center">
                                <i class="fa-solid fa-shield-halved fa-2x mb-2 text-success"></i>
                                <div class="fw-bold small">Zero Hidden Fees</div>
                                <span class="text-light text-opacity-50 x-small">Transparent Pricing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FREQUENTLY ASKED QUESTIONS -->
<section class="py-5 bg-soft" id="faq">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold small mb-2">
                QUESTIONS & ANSWERS
            </span>
            <h2 class="fw-bold fs-1 mb-2">Frequently Asked Questions</h2>
            <p class="text-muted fs-5">Everything you need to know about Linkocial.</p>
        </div>

        <div class="mx-auto" style="max-width: 800px;">
            <div class="faq-item active">
                <button class="faq-trigger" onclick="toggleFaq(this)">
                    <span>What services can I access on Linkocial?</span>
                    <i class="fa-solid fa-chevron-down faq-chevron"></i>
                </button>
                <div class="faq-content">
                    Linkocial brings together four core digital services under one account: <strong>Virtual Numbers</strong> for SMS verification, <strong>Social Media Boosting</strong> for followers and engagement, <strong>Data & Utility Subscriptions</strong> (MTN, Airtel, Glo, Electricity, Cable TV), and a <strong>Digital Marketplace</strong> for verified accounts and tools.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-trigger" onclick="toggleFaq(this)">
                    <span>How does Number Verification work?</span>
                    <i class="fa-solid fa-chevron-down faq-chevron"></i>
                </button>
                <div class="faq-content">
                    You can rent a virtual phone number from over 100+ supported countries (including USA, UK, Nigeria, Canada). Use the number to register on WhatsApp, Telegram, Google, or other platforms. The SMS verification code will appear live on your screen within seconds.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-trigger" onclick="toggleFaq(this)">
                    <span>How fast are VTU data and airtime delivered?</span>
                    <i class="fa-solid fa-chevron-down faq-chevron"></i>
                </button>
                <div class="faq-content">
                    Delivery is 100% automated and instant. Once you confirm your purchase with your phone number and network, data or airtime is credited immediately (usually within 1 to 5 seconds).
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-trigger" onclick="toggleFaq(this)">
                    <span>Are social media followers and likes permanent?</span>
                    <i class="fa-solid fa-chevron-down faq-chevron"></i>
                </button>
                <div class="faq-content">
                    We deliver high-retention services designed to withstand social network updates. Many of our services also come with automatic or manual refill coverage guarantees to ensure your numbers remain stable.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-trigger" onclick="toggleFaq(this)">
                    <span>How do I fund my wallet?</span>
                    <i class="fa-solid fa-chevron-down faq-chevron"></i>
                </button>
                <div class="faq-content">
                    You can deposit funds securely via dedicated virtual bank accounts (9PSB & PalmPay) with automated instant crediting, or via Cards and Mobile Money. Your balance is credited automatically in seconds.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CALL TO ACTION -->
<section class="py-5" style="background: linear-gradient(135deg, #4f46e5, #0284c7); color: #ffffff;">
    <div class="container py-5 text-center">
        <div class="mx-auto" style="max-width: 700px;" data-aos="zoom-in">
            <h2 class="display-5 fw-bold mb-3">One Platform. Four Ways to Get More Done.</h2>
            <p class="fs-5 text-light text-opacity-90 mb-4">
                Verify. Grow. Connect. Shop. Join thousands of individuals, creators, and businesses making Linkocial their daily digital powerhouse.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-primary shadow">
                        Go To Your Dashboard <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-primary shadow">
                        Get Started Free <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    function toggleFaq(btn) {
        const item = btn.closest('.faq-item');
        const isActive = item.classList.contains('active');
        
        document.querySelectorAll('.faq-item').forEach(el => {
            el.classList.remove('active');
        });

        if (!isActive) {
            item.classList.add('active');
        }
    }
</script>
@endpush
