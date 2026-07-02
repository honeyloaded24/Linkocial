@extends('layouts.marketing')

@section('title', 'Buy Virtual Numbers for SMS Verification | ' . $siteName)
@section('meta_description', 'Get instant disposable virtual numbers for SMS verification. Bypass restrictions securely for WhatsApp, Telegram, Tinder, OpenAI, and more. Prices starting from as low as $0.09.')

@push('styles')
<style>
    :root {
        --vn-primary: #6366f1; /* Indigo */
    }
    .vn-hero {
        padding: 140px 0 80px;
        background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.1), transparent);
        text-align: center;
    }
    .feature-tag {
        background: rgba(99, 102, 241, 0.1);
        color: var(--vn-primary);
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 700;
        display: inline-block;
        margin-bottom: 1.5rem;
    }
    .btn-vn {
        background-color: var(--vn-primary);
        color: white;
        padding: 1rem 2.5rem;
        border-radius: 50px;
        font-weight: 700;
        border: none;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-vn:hover {
        background-color: #4f46e5;
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
    }
    .service-logo-card {
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s;
        background: var(--surface-bg);
    }
    .service-logo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.05);
        border-color: var(--vn-primary);
    }
    .service-logo-card i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }
    .price-badge {
        background: #10b981;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<header class="vn-hero">
    <div class="container">
        <div class="feature-tag">📱 Instant SMS Verification</div>
        <h1 class="display-4 fw-extrabold mb-3">Buy Virtual Numbers</h1>
        <p class="fs-4 text-muted mb-4 mx-auto" style="max-width: 800px;">Bypass SMS verifications instantly and protect your privacy with premium disposable numbers. <br> <span class="fw-bold text-success">Prices starting from as low as $0.09.</span></p>
        
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('services.virtual-numbers') }}" class="btn-vn btn-lg">Get Your Number Now <i class="fa-solid fa-arrow-right ms-2"></i></a>
        </div>
        
        <!-- Trust Badges Bar -->
        <div class="mt-5 px-4 py-3 rounded-4 d-flex flex-wrap justify-content-center gap-4 shadow-sm mx-auto" style="max-width: 800px; background-color: #f0f9ff; border: 1px solid rgba(99, 102, 241, 0.1);">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-bolt text-info"></i>
                <span class="text-dark fw-bold small">Instant Delivery</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-earth-americas text-info"></i>
                <span class="text-dark fw-bold small">Global Coverage</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-shield-halved text-info"></i>
                <span class="text-dark fw-bold small">100% Private</span>
            </div>
        </div>
    </div>
</header>

<!-- Supported Services Section -->
<section class="py-5 bg-white border-top">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold fs-1 mb-3">Unlock Any Service Instantly</h2>
            <p class="text-muted fs-5">We provide clean numbers for hundreds of apps and websites.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <!-- WhatsApp -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-brands fa-whatsapp text-success"></i>
                    <h5 class="fw-bold mb-0">WhatsApp</h5>
                </div>
            </div>
            <!-- Telegram -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-brands fa-telegram text-info"></i>
                    <h5 class="fw-bold mb-0">Telegram</h5>
                </div>
            </div>
            <!-- Tinder -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-solid fa-fire" style="color: #fd5068;"></i>
                    <h5 class="fw-bold mb-0">Tinder</h5>
                </div>
            </div>
            <!-- OpenAI -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-solid fa-robot text-dark"></i>
                    <h5 class="fw-bold mb-0">OpenAI / ChatGPT</h5>
                </div>
            </div>
            <!-- Google -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-brands fa-google text-danger"></i>
                    <h5 class="fw-bold mb-0">Google / Gmail</h5>
                </div>
            </div>
            <!-- Discord -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-brands fa-discord" style="color: #5865F2;"></i>
                    <h5 class="fw-bold mb-0">Discord</h5>
                </div>
            </div>
            <!-- Instagram -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-brands fa-instagram" style="color: #E1306C;"></i>
                    <h5 class="fw-bold mb-0">Instagram</h5>
                </div>
            </div>
            <!-- More -->
            <div class="col-6 col-md-3">
                <div class="service-logo-card">
                    <i class="fa-solid fa-ellipsis text-muted"></i>
                    <h5 class="fw-bold mb-0">+500 More</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-soft">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold fs-1 mb-3">How It Works</h2>
            <p class="text-muted fs-5">Get verified in 3 simple steps.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center p-4">
                    <div class="mb-4 text-primary fs-1">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <h4 class="fw-bold mb-3">1. Top Up Wallet</h4>
                    <p class="text-muted small mb-0">Deposit funds using our secure payment gateways. Pricing starts as low as $0.09 per verification.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4">
                    <div class="mb-4 text-primary fs-1">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                    <h4 class="fw-bold mb-3">2. Choose Country & Service</h4>
                    <p class="text-muted small mb-0">Select from hundreds of countries and specific apps you want to verify (e.g., USA + WhatsApp).</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4">
                    <div class="mb-4 text-primary fs-1">
                        <i class="fa-solid fa-message"></i>
                    </div>
                    <h4 class="fw-bold mb-3">3. Receive SMS Code</h4>
                    <p class="text-muted small mb-0">The virtual number will receive the SMS code instantly on your dashboard. Use it to complete registration!</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5">
    <div class="container">
        <div class="p-5 rounded-5 shadow text-center text-white mb-5" style="background: linear-gradient(135deg, var(--vn-primary), #3b82f6);">
            <h2 class="fw-bold mb-3">Ready to protect your privacy?</h2>
            <p class="fs-5 mb-4 opacity-75">Join thousands of users bypassing SMS restrictions effortlessly.</p>
            <a href="{{ route('services.virtual-numbers') }}" class="btn btn-light btn-lg text-primary fw-bold px-5 rounded-pill shadow-sm">Get Started Now</a>
        </div>
    </div>
</section>
@endsection
