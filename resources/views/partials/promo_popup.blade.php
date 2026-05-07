@php
    $promoEnabled = \App\Models\Setting::get('promo_enabled') == '1';
    $expiresAt = \App\Models\Setting::get('promo_expires_at');
    $isExpired = $expiresAt ? \Carbon\Carbon::parse($expiresAt)->isPast() : true;
    $shouldShow = $promoEnabled && !$isExpired;
@endphp

@if($shouldShow)
<div class="modal fade" id="promoPopup" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 10000; max-width: 400px;">
        <div class="modal-content border-0 rounded-5 shadow-lg overflow-hidden">
            <div class="modal-header border-0 p-3 position-absolute end-0 top-0" style="z-index: 10;">
                <button type="button" class="btn-close bg-white rounded-circle p-2 shadow-sm" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.6rem;"></button>
            </div>
            
            <div class="modal-body p-0">
                <div class="text-center p-4">
                    @php
                        $promoImage = \App\Models\Setting::get('promo_image');
                    @endphp
                    <div class="mb-3">
                        @if($promoImage)
                            <img src="{{ $promoImage }}" alt="Promo" class="img-fluid rounded-4 shadow-sm" style="max-height: 140px; width: 100%; object-fit: cover;">
                        @else
                            <img src="https://cdn-icons-png.flaticon.com/512/2331/2331924.png" alt="Promo" width="60" class="img-fluid">
                        @endif
                    </div>
                    
                    <h4 class="fw-bold mb-1">{{ \App\Models\Setting::get('promo_title', "Special Offer For You!") }}</h4>
                    <p class="text-muted small mb-3">
                        {{ \App\Models\Setting::get('promo_message', "Hello " . auth()->user()->username . ", we have a special gift waiting for you in your account!") }}
                    </p>
                    
                    <div class="p-3 rounded-4 mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, var(--primary-color), #8b5cf6);">
                        <p class="text-white text-opacity-75 small mb-1 fw-medium">Add ₦{{ number_format(\App\Models\Setting::get('promo_min_amount', 1000)) }}+ and get an extra</p>
                        <h4 class="fw-bold text-white mb-0"><i class="fa-solid fa-gift me-2 text-white-50"></i>+{{ \App\Models\Setting::get('promo_bonus_percent', 10) }}% Bonus</h4>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                        <div class="d-flex align-items-center gap-2 text-danger small fw-bold">
                            <i class="fa-solid fa-circle-dot animate-pulse"></i> Ends Soon
                        </div>
                        <div class="badge bg-secondary bg-opacity-75 text-white px-3 py-1 rounded-pill border-0" id="promoCountdown" style="font-family: monospace; font-size: 0.85rem; letter-spacing: 1px;">
                            <i class="fa-regular fa-clock me-1"></i> --:--
                        </div>
                    </div>
                    
                    <a href="{{ \App\Models\Setting::get('promo_button_link', route('wallet.index')) }}" class="btn btn-primary-custom w-100 py-2 rounded-pill fw-bold shadow-sm mb-2" onclick="localStorage.setItem('promo_dismissed', 'true')">
                        <i class="fa-solid fa-bolt me-2"></i> {{ \App\Models\Setting::get('promo_button_text', 'Claim My Bonus Now') }}
                    </a>
                    
                    <button type="button" class="btn btn-link text-muted small text-decoration-none py-0" data-bs-dismiss="modal" onclick="localStorage.setItem('promo_dismissed', 'true')">I'll check this later</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .modal-backdrop { z-index: 9990 !important; }
    .animate-pulse {
        animation: pulse-red 2s infinite;
    }
    @keyframes pulse-red {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.9); }
        100% { opacity: 1; transform: scale(1); }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const promoModal = new bootstrap.Modal(document.getElementById('promoPopup'));
        // Urgency Timer: 10 Minute Countdown
        let startTime = localStorage.getItem('promo_timer_start');
        const duration = 10 * 60 * 1000; // 10 minutes in ms
        
        if (!startTime) {
            startTime = new Date().getTime();
            localStorage.setItem('promo_timer_start', startTime);
        }

        const countdownEl = document.getElementById('promoCountdown');
        
        // 24-Hour Cooldown Logic
        const cooldownHours = 24;
        const lastShown = localStorage.getItem('promo_last_shown');
        const now = new Date().getTime();
        
        let showPopup = true;
        
        // Check if permanently dismissed or in cooldown period
        if (localStorage.getItem('promo_dismissed') === 'true') {
            showPopup = false;
        } else if (lastShown) {
            const hoursSinceLastShown = (now - parseInt(lastShown)) / (1000 * 60 * 60);
            if (hoursSinceLastShown < cooldownHours) {
                showPopup = false;
            }
        }

        if (showPopup) {
            setTimeout(() => {
                promoModal.show();
                // Log exactly when we showed it to start the 24-hour cooldown
                localStorage.setItem('promo_last_shown', now.toString());
            }, 1000);
        }

        // Timer Logic
        const timer = setInterval(function() {
            const now = new Date().getTime();
            const elapsed = now - startTime;
            const distance = duration - elapsed;

            if (distance <= 0) {
                clearInterval(timer);
                countdownEl.innerHTML = "00:00";
                localStorage.setItem('promo_dismissed', 'true');
                promoModal.hide();
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            countdownEl.innerHTML = `<i class="fa-regular fa-clock me-1"></i> ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    });
</script>
@endpush
@endif
