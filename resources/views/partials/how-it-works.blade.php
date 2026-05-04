<section class="py-4 py-lg-5 {{ $bg ?? '' }}">
    <div class="container py-2 py-lg-4">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase small letter-spacing-lg mb-2 d-block">Simple 4-Step Process</span>
            <h2 class="fw-bold fs-1" style="color: var(--text-main);">How to Start Growing</h2>
            <p class="text-muted fs-5">Take your social presence to the next level in minutes.</p>
        </div>
        
        <div class="row g-4 position-relative">
            <!-- Connecting Line (Desktop Only) -->
            <div class="d-none d-lg-block position-absolute top-50 start-0 w-100 translate-middle-y" style="height: 2px; background: repeating-linear-gradient(to right, var(--border) 0, var(--border) 10px, transparent 10px, transparent 20px); z-index: 0; opacity: 0.5;"></div>

            <!-- Step 1 -->
            <div class="col-lg-3 col-md-6 text-center position-relative" style="z-index: 1;">
                <div class="step-number-wrapper mb-4">
                    <div class="step-number">1</div>
                </div>
                <h5 class="fw-bold mb-3">Register & Login</h5>
                <p class="text-muted small px-3">Create your account in seconds and access our premium aggregator dashboard.</p>
            </div>

            <!-- Step 2 -->
            <div class="col-lg-3 col-md-6 text-center position-relative" style="z-index: 1;">
                <div class="step-number-wrapper mb-4">
                    <div class="step-number">2</div>
                </div>
                <h5 class="fw-bold mb-3">Add Funds</h5>
                <p class="text-muted small px-3">Choose from our secure payment methods and top up your balance instantly.</p>
            </div>

            <!-- Step 3 -->
            <div class="col-lg-3 col-md-6 text-center position-relative" style="z-index: 1;">
                <div class="step-number-wrapper mb-4">
                    <div class="step-number">3</div>
                </div>
                <h5 class="fw-bold mb-3">Choose Services</h5>
                <p class="text-muted small px-3">Select from thousands of high-quality services tailored for your platform.</p>
            </div>

            <!-- Step 4 -->
            <div class="col-lg-3 col-md-6 text-center position-relative" style="z-index: 1;">
                <div class="step-number-wrapper mb-4">
                    <div class="step-number">4</div>
                </div>
                <h5 class="fw-bold mb-3">Enjoy Growth</h5>
                <p class="text-muted small px-3">Sit back and watch your engagement soar. Our automated systems handle the rest.</p>
            </div>
        </div>
    </div>
</section>

<style>
    .step-number-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        background: white;
        border: 2px solid var(--border);
        border-radius: 50%;
        padding: 8px;
        transition: all 0.3s ease;
    }
    .step-number {
        width: 100%;
        height: 100%;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 800;
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
    }
    .step-number-wrapper:hover {
        border-color: var(--primary);
        transform: scale(1.1);
    }
    .letter-spacing-lg { letter-spacing: 2px; }
</style>
