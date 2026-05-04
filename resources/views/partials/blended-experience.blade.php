<section class="py-5">
    <div class="container">
        <div class="blended-box p-4 p-lg-5 rounded-5" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
            <div class="row g-4">
                <!-- Card 1 -->
                <div class="col-lg-4">
                    <div class="white-card h-100 p-4 p-lg-5 rounded-4 bg-white d-flex flex-column">
                        <h4 class="fw-bold mb-3 lh-sm">Enterprise-Grade Infrastructure</h4>
                        <p class="text-muted small mb-4">We leverage an advanced supply network to ensure the fastest delivery and the most competitive market prices for our users.</p>
                        <a href="{{ route('register') }}" class="mt-auto fw-bold text-primary text-decoration-none d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus small"></i> Register
                        </a>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="col-lg-4">
                    <div class="white-card h-100 p-4 p-lg-5 rounded-4 bg-white d-flex flex-column">
                        <h4 class="fw-bold mb-3 lh-sm">Tailored Growth Strategies</h4>
                        <p class="text-muted small mb-4">Every account is different. Our platform allows you to customize your engagement speed and targets to match your specific goals.</p>
                        <a href="{{ route('register') }}" class="mt-auto fw-bold text-primary text-decoration-none d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus small"></i> Register
                        </a>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="col-lg-4">
                    <div class="white-card h-100 p-4 p-lg-5 rounded-4 bg-white d-flex flex-column">
                        <h4 class="fw-bold mb-3 lh-sm">Verified Premium Interactions</h4>
                        <p class="text-muted small mb-4">Experience the difference of high-quality SMM. We provide stable, high-retention engagement that builds long-term authority.</p>
                        <a href="{{ route('register') }}" class="mt-auto fw-bold text-primary text-decoration-none d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus small"></i> Register
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .blended-box {
        box-shadow: 0 40px 80px -20px rgba(99, 102, 241, 0.3);
    }
    .white-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .white-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }
</style>
