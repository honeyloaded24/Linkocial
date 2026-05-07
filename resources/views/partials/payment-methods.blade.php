<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h6 class="text-uppercase ls-wide text-muted fw-bold small">Supported Payment Methods</h6>
        </div>
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 gap-lg-5 opacity-75 mb-4">
            <!-- Paystack -->
            <div class="payment-method-item d-flex align-items-center gap-2">
                <i class="fa-solid fa-credit-card fs-4 text-info"></i>
                <span class="fw-bold fs-5">Paystack</span>
            </div>

            <!-- Kora -->
            <div class="payment-method-item d-flex align-items-center gap-2">
                <i class="fa-solid fa-building-columns fs-4 text-primary"></i>
                <span class="fw-bold fs-5">Kora</span>
            </div>

            <!-- PayPal -->
            <div class="payment-method-item d-flex align-items-center gap-2">
                <i class="fa-brands fa-paypal fs-4" style="color: #003087;"></i>
                <span class="fw-bold fs-5">PayPal</span>
            </div>
        </div>

        <!-- Global Coverage - Now Below -->
        <div class="d-flex justify-content-center align-items-center gap-2 opacity-50">
            <i class="fa-solid fa-shield-halved text-success small"></i>
            <span class="text-muted small fw-semibold">PCI-DSS Compliant & Secure Transactions</span>
        </div>
    </div>
</section>

<style>
    .payment-method-item {
        transition: all 0.3s ease;
        cursor: default;
    }

    .payment-method-item:hover {
        opacity: 1;
        transform: translateY(-2px);
    }

    .letter-spacing-lg {
        letter-spacing: 2px;
    }
</style>