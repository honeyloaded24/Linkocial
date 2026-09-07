@extends('layouts.app')

@section('header_title', 'Virtual Numbers')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { border-radius: 0.5rem !important; padding: 0.75rem 1rem !important; border: 0 !important; background: #f8f9fa !important; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important; font-size: 1.125rem !important; }
    .ts-wrapper.form-select-lg .ts-control { min-height: 3.5rem; display: flex; align-items: center; }
    .ts-dropdown { border-radius: 0.5rem; border: 0; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); z-index: 2000 !important; }
    .ts-dropdown .ts-dropdown-content { max-height: 250px; }
    [data-theme="dark"] .ts-control { background: #1e293b !important; color: #f8fafc; border: 1px solid #334155 !important; }
    [data-theme="dark"] .ts-control input { color: #f8fafc; }
    [data-theme="dark"] .ts-dropdown { background: #1e293b; color: #f8fafc; border: 1px solid #334155; }
    [data-theme="dark"] .ts-dropdown .active { background: #334155; color: #fff; }
</style>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <p class="text-muted small mb-0">Order phone numbers and receive verification codes dynamically.</p>
    </div>
    <a href="{{ route('virtual-numbers.history') }}" class="btn btn-outline-primary rounded-pill fw-bold btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> Order History & Logs
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6 col-xl-6" style="position: relative; z-index: 10;">
        <!-- Main Form Card -->
        <div class="premium-card p-4 border-0 shadow-sm h-100 rounded-4" style="background: var(--bg-surface); overflow: visible !important;">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary-custom bg-opacity-10 rounded-circle p-3 me-3 text-primary-custom shadow-sm">
                    <i class="fa-solid fa-phone-volume fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold" style="font-family: var(--heading-font);">Get Virtual Number</h4>
                    <p class="text-muted small mb-0">Virtual numbers for SMS verification (WhatsApp, Telegram, etc.)</p>
                </div>
            </div>

            <form id="vn-form" class="mt-4">
                @csrf
                <div class="row g-3">
                    <!-- Country Select -->
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">Select Country</label>
                        <select class="form-select form-select-lg rounded-3" id="vn-country" required disabled>
                            <option value="">Loading countries...</option>
                        </select>
                    </div>
                    
                    <!-- Service Select -->
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">Select Service</label>
                        <select class="form-select form-select-lg rounded-3" id="vn-service" required disabled>
                            <option value="">Select country first</option>
                        </select>
                    </div>
                </div> <!-- Closes row g-3 -->
                
                <div class="mt-4 p-3 p-sm-4 rounded-4 bg-primary-custom text-white d-flex flex-column d-none shadow" id="vn-price-box">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center w-100">
                        <div class="mb-3 mb-sm-0 text-center text-sm-start">
                            <span class="text-white-50 small fw-bold d-block text-uppercase tracking-widest">Estimated Cost</span>
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start gap-2 mt-1">
                                <span class="fs-3 fw-bold text-white" id="vn-cost">$0.00</span>
                                <span class="text-decoration-line-through text-white-50 small d-none" id="vn-original-cost">$0.00</span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-light rounded-pill fw-bold shadow-sm px-4 py-2" id="vn-buy-btn" style="color: var(--bs-primary); white-space: nowrap;">
                            Purchase Number <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                    <div class="mt-3 text-center text-sm-start w-100">
                        <small class="text-white-50 d-block" style="font-size: 0.75rem;">* Final price may slightly vary depending on real-time market rates.</small>
                    </div>
                </div>
                
                <!-- Error Alert -->
                <div class="alert alert-danger mt-3 d-none" id="vn-error"></div>
            </form>
        </div>
    </div>

    <!-- Active Orders Sidebar -->
    <div class="col-12 col-lg-6 col-xl-6">
        <div class="premium-card p-4 border-0 shadow-sm h-100 rounded-4" style="background: var(--bg-surface);">
            <h5 class="fw-bold mb-4" style="font-family: var(--heading-font);">
                <i class="fa-solid fa-tower-cell text-primary-custom me-2"></i> Active Verifications
            </h5>
            
            <div id="vn-active-orders">
                <!-- Orders injected by JS -->
                <div class="text-center text-muted py-5" id="vn-empty-state">
                    <i class="fa-solid fa-box-open fs-1 opacity-25 mb-3"></i>
                    <p class="small mb-0">No active numbers.<br>Purchase a number to see it here.</p>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Virtual Numbers Guide Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="premium-card p-4 p-md-5 border-0 shadow-sm rounded-4" style="background-color: var(--bg-surface);">
                <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                    <i class="fa-solid fa-book-open text-primary-custom fs-3 me-3"></i>
                    <h3 class="fw-bold mb-0" style="font-family: var(--heading-font);">SMS Virtual Numbers Guide</h3>
                </div>

                <div class="accordion accordion-flush promo-accordion" id="vnGuideAccordion">
                    <div class="row g-3">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <!-- Rule 1 -->
                            <div class="accordion-item border rounded-4 mb-3 overflow-hidden" style="border-color: var(--border-color) !important; background: transparent;">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold py-3 text-main" type="button" data-bs-toggle="collapse" data-bs-target="#guide-how-it-works" style="background: transparent;">
                                        <i class="fa-solid fa-circle-info text-primary-custom me-2"></i> How it works
                                    </button>
                                </h2>
                                <div id="guide-how-it-works" class="accordion-collapse collapse" data-bs-parent="#vnGuideAccordion">
                                    <div class="accordion-body text-muted small">
                                        Select a country and service, then click purchase. Copy the allocated phone number and paste it into the app or website you want to verify. Send the verification code from the target app, and wait for the code to display here.
                                    </div>
                                </div>
                            </div>

                            <!-- Rule 2 -->
                            <div class="accordion-item border rounded-4 mb-3 overflow-hidden" style="border-color: var(--border-color) !important; background: transparent;">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold py-3 text-main" type="button" data-bs-toggle="collapse" data-bs-target="#guide-refund-policy" style="background: transparent;">
                                        <i class="fa-solid fa-rotate-left text-primary-custom me-2"></i> Refund Policy
                                    </button>
                                </h2>
                                <div id="guide-refund-policy" class="accordion-collapse collapse" data-bs-parent="#vnGuideAccordion">
                                    <div class="accordion-body text-muted small">
                                        If no SMS is received within the 20-minute countdown, you will not be charged. The system automatically cancels the order and refunds your wallet balance. You can also manually cancel the order for an immediate refund if the lockout cooldown has passed.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <!-- Rule 3 -->
                            <div class="accordion-item border rounded-4 mb-3 overflow-hidden" style="border-color: var(--border-color) !important; background: transparent;">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold py-3 text-main" type="button" data-bs-toggle="collapse" data-bs-target="#guide-one-time" style="background: transparent;">
                                        <i class="fa-solid fa-mobile-screen text-primary-custom me-2"></i> One-time use numbers
                                    </button>
                                </h2>
                                <div id="guide-one-time" class="accordion-collapse collapse" data-bs-parent="#vnGuideAccordion">
                                    <div class="accordion-body text-muted small">
                                        These numbers are strictly temporary and intended for one-time verification. You cannot reuse the number to receive subsequent SMS codes or verify additional accounts once the session is completed or canceled.
                                    </div>
                                </div>
                            </div>

                            <!-- Rule 4 -->
                            <div class="accordion-item border rounded-4 mb-3 overflow-hidden" style="border-color: var(--border-color) !important; background: transparent;">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold py-3 text-main" type="button" data-bs-toggle="collapse" data-bs-target="#guide-troubleshooting" style="background: transparent;">
                                        <i class="fa-solid fa-triangle-exclamation text-primary-custom me-2"></i> Troubleshooting
                                    </button>
                                </h2>
                                <div id="guide-troubleshooting" class="accordion-collapse collapse" data-bs-parent="#vnGuideAccordion">
                                    <div class="accordion-body text-muted small">
                                        If you aren't receiving the SMS code:
                                        <ul class="ps-3 mt-2 mb-0">
                                            <li>Ensure you sent the SMS from your app to the correct number with the country code.</li>
                                            <li>Try a different country or server route if a specific carrier/pool is congested.</li>
                                            <li>Make sure your target service doesn't block virtual/VoIP numbers.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Active Order Template (Hidden) -->
<template id="vn-order-template">
    <div class="card border-0 bg-light shadow-sm rounded-4 mb-3 vn-order-item" data-id="">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary-custom bg-opacity-10 text-primary-custom rounded-pill service-name">Service</span>
                <span class="small text-muted fw-bold country-name">Country</span>
            </div>
            
            <div class="input-group mb-3 shadow-sm rounded-3 overflow-hidden">
                <input type="text" class="form-control text-center phone-number font-monospace fw-bold bg-white" value="" readonly style="font-size: clamp(0.82rem, 3.8vw, 1.05rem); letter-spacing: 0.5px; padding: 0.55rem 0.5rem;" title="Phone Number">
                <button class="btn btn-primary-custom copy-btn px-3 flex-shrink-0" type="button" title="Copy Number"><i class="fa-solid fa-copy"></i></button>
            </div>
            <span class="service-name-inline d-none"></span>
            
            <!-- SMS Code Area -->
            <div class="text-center p-3 rounded-3 bg-white border border-light sms-area">
                <div class="spinner-border spinner-border-sm text-primary-custom mb-2 waiting-spinner" role="status"></div>
                <p class="small text-muted mb-0 waiting-text fw-bold">Waiting for SMS...</p>
                <div class="small fw-bold text-danger mt-2 countdown-timer d-none">
                    <i class="fa-regular fa-clock me-1"></i> Expires in: <span class="time-left">--:--</span>
                </div>
                
                <div class="sms-result d-none">
                    <span class="d-block small text-muted mb-1 sms-sender"></span>
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <h3 class="fw-bold text-success mb-0 tracking-widest sms-code"></h3>
                        <button class="btn btn-sm btn-link text-muted copy-sms-btn p-0" type="button" title="Copy Verification Code"><i class="fa-regular fa-copy" style="font-size: 0.95rem;"></i></button>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <button class="btn btn-sm btn-outline-danger rounded-pill fw-bold cancel-btn">Cancel & Refund</button>
            </div>
        </div>
    </div>
</template>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('vn-country');
    const serviceSelect = document.getElementById('vn-service');
    const priceBox = document.getElementById('vn-price-box');
    const costDisplay = document.getElementById('vn-cost');
    const errorBox = document.getElementById('vn-error');
    const buyBtn = document.getElementById('vn-buy-btn');

    const originalCostDisplay = document.getElementById('vn-original-cost');
    let currentOriginalPrice = 0.00;
    
    // Load Tom Select Script dynamically
    const script = document.createElement('script');
    script.src = "https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js";
    script.onload = function() {
        initTomSelect();
    };
    document.head.appendChild(script);

    let countryTs, serviceTs;

    function initTomSelect() {
        countryTs = new TomSelect('#vn-country', {
            create: false,
            maxOptions: null,
            placeholder: 'Loading countries...'
        });

        serviceTs = new TomSelect('#vn-service', {
            create: false,
            maxOptions: null,
            placeholder: 'Select country first'
        });

        // Fetch and populate countries
        fetch('/api/virtual-numbers/countries')
            .then(res => res.json())
            .then(countries => {
                countries.sort((a, b) => a.name.localeCompare(b.name));
                countryTs.clearOptions();
                countries.forEach(c => {
                    countryTs.addOption({value: c.ID, text: c.name + (c.cc ? ` (+${c.cc})` : '')});
                });
                countryTs.settings.placeholder = 'Select Country';
                countryTs.enable();
                countryTs.clear();
            })
            .catch(err => {
                countryTs.settings.placeholder = 'Failed to load countries';
                console.error('Failed to load countries', err);
            });
            
        // On country select, load service pricing
        countryTs.on('change', function(countryId) {
            serviceTs.clearOptions();
            serviceTs.clear();
            priceBox.classList.add('d-none');
            
            if (countryId) {
                serviceTs.disable();
                serviceTs.settings.placeholder = 'Loading services...';
                
                fetch(`/api/virtual-numbers/products?country=${countryId}`)
                    .then(res => res.json())
                    .then(services => {
                        services.sort((a, b) => a.name.localeCompare(b.name));
                        services.forEach(s => {
                            serviceTs.addOption({value: s.id, text: `${s.name} — ${s.formatted_price}`, formatted_price: s.formatted_price});
                        });
                        serviceTs.settings.placeholder = 'Select Service';
                        serviceTs.enable();
                    })
                    .catch(err => {
                        serviceTs.settings.placeholder = 'Failed to load services';
                        console.error('Failed to load services', err);
                    });
            } else {
                serviceTs.settings.placeholder = 'Select country first';
                serviceTs.disable();
            }
        });
        
        // On service select, show price and buy button
        serviceTs.on('change', function(val) {
            if (val) {
                const option = serviceTs.options[val];
                if (option && option.formatted_price) {
                    costDisplay.textContent = option.formatted_price;
                    priceBox.classList.remove('d-none');
                }
            } else {
                currentOriginalPrice = 0;
                priceBox.classList.add('d-none');
            }
        });
    }



    // Handle Form Submit (Purchase)
    document.getElementById('vn-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        errorBox.classList.add('d-none');
        buyBtn.disabled = true;
        buyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
        
        fetch('/api/virtual-numbers/purchase', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                country: countrySelect.value,
                product: serviceSelect.value
            })
        })
        .then(res => res.json())
        .then(res => {
            buyBtn.disabled = false;
            buyBtn.innerHTML = 'Purchase Number <i class="fa-solid fa-arrow-right ms-2"></i>';
            
            if (res.error) {
                errorBox.innerHTML = res.error;
                errorBox.classList.remove('d-none');
            } else if (res.success && res.order) {
                // Success! Create active order card
                addOrderCard(res.order);
                // Clear selection
                if (serviceSelect.tomselect) {
                    serviceSelect.tomselect.clear();
                } else {
                    serviceSelect.value = '';
                }
                priceBox.classList.add('d-none');
            }
        })
        .catch(err => {
            buyBtn.disabled = false;
            buyBtn.innerHTML = 'Purchase Number <i class="fa-solid fa-arrow-right ms-2"></i>';
            errorBox.innerHTML = 'A network error occurred. Please try again.';
            errorBox.classList.remove('d-none');
        });
    });
    
    // Active Orders Tracking
    const activeOrdersContainer = document.getElementById('vn-active-orders');
    const emptyState = document.getElementById('vn-empty-state');
    const activePolls = {};
    
    function addOrderCard(order) {
        emptyState.classList.add('d-none');
        
        const template = document.getElementById('vn-order-template');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.vn-order-item');
        
        card.setAttribute('data-id', order.id);
        card.querySelector('.service-name').textContent = order.product;
        card.querySelector('.service-name-inline').textContent = order.product;
        card.querySelector('.country-name').textContent = order.country;
        card.querySelector('.phone-number').value = order.phone_number;
        
        // Setup countdown timer
        if (order.expires_at) {
            const countdownEl = card.querySelector('.countdown-timer');
            const timeLeftEl = card.querySelector('.time-left');
            countdownEl.classList.remove('d-none');
            
            // Ensure proper parsing of the date string
            const expiresAt = new Date(order.expires_at).getTime();
            
            const timerInterval = setInterval(() => {
                const now = new Date().getTime();
                const distance = expiresAt - now;
                
                if (distance < 0) {
                    clearInterval(timerInterval);
                    timeLeftEl.textContent = "Expired";
                    timeLeftEl.classList.replace('text-danger', 'text-muted');
                    // Automatically click cancel if it expires
                    const cancelBtn = card.querySelector('.cancel-btn');
                    if (cancelBtn && !cancelBtn.disabled) {
                        cancelBtn.click();
                    }
                } else {
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timeLeftEl.textContent = minutes.toString().padStart(2, '0') + ":" + seconds.toString().padStart(2, '0');
                }
            }, 1000);
            
            // Store interval ID to clear it when done
            card.dataset.timerId = timerInterval;
        }

        // Copy btn
        card.querySelector('.copy-btn').addEventListener('click', function() {
            navigator.clipboard.writeText(order.phone_number);
            const icon = this.querySelector('i');
            icon.className = 'fa-solid fa-check text-success';
            setTimeout(() => { icon.className = 'fa-solid fa-copy'; }, 2000);
        });

        // Copy SMS Code btn
        card.querySelector('.copy-sms-btn').addEventListener('click', function() {
            const smsCode = card.querySelector('.sms-code').textContent;
            if (smsCode) {
                navigator.clipboard.writeText(smsCode);
                const icon = this.querySelector('i');
                icon.className = 'fa-solid fa-check text-success';
                setTimeout(() => { icon.className = 'fa-regular fa-copy text-muted'; }, 2000);
            }
        });
        
        // Cancel btn
        card.querySelector('.cancel-btn').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'Canceling...';
            cancelOrder(order.id, card);
        });
        
        activeOrdersContainer.prepend(card);
        
        // Start polling
        startPolling(order.id, card);
    }
    
    function startPolling(id, cardNode) {
        if (activePolls[id]) clearInterval(activePolls[id]);
        
        activePolls[id] = setInterval(() => {
            fetch(`/api/virtual-numbers/${id}/check`)
                .then(res => res.json())
                .then(data => {
                    if (data.order && (data.order.status === 'received' || data.order.status === 'finished')) {
                        clearInterval(activePolls[id]);
                        delete activePolls[id];
                        if (cardNode.dataset.timerId) clearInterval(cardNode.dataset.timerId);
                        cardNode.querySelector('.countdown-timer').classList.add('d-none');
                        
                        // Show SMS
                        cardNode.querySelector('.waiting-spinner').classList.add('d-none');
                        cardNode.querySelector('.waiting-text').classList.add('d-none');
                        cardNode.querySelector('.sms-result').classList.remove('d-none');
                        cardNode.querySelector('.sms-code').textContent = data.order.sms_code || data.order.sms_text;
                        
                        // Hide Cancel button
                        cardNode.querySelector('.cancel-btn').classList.add('d-none');
                        cardNode.querySelector('.sms-area').classList.replace('bg-white', 'bg-success');
                        cardNode.querySelector('.sms-area').classList.add('bg-opacity-10');
                    } else if (data.order && data.order.status === 'canceled') {
                        clearInterval(activePolls[id]);
                        delete activePolls[id];
                        if (cardNode.dataset.timerId) clearInterval(cardNode.dataset.timerId);
                        cardNode.remove();
                        checkEmptyState();
                    }
                })
                .catch(err => console.error('Failed to poll order status:', err));
        }, 5000); // poll every 5 seconds
    }
    
    function cancelOrder(id, cardNode) {
        fetch(`/api/virtual-numbers/${id}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                cardNode.querySelector('.cancel-btn').disabled = false;
                cardNode.querySelector('.cancel-btn').textContent = 'Cancel & Refund';
                return;
            }
            if (activePolls[id]) {
                clearInterval(activePolls[id]);
                delete activePolls[id];
            }
            if (cardNode.dataset.timerId) {
                clearInterval(cardNode.dataset.timerId);
            }
            cardNode.remove();
            checkEmptyState();
        })
        .catch(err => {
            console.error('Failed to cancel order:', err);
            cardNode.querySelector('.cancel-btn').disabled = false;
            cardNode.querySelector('.cancel-btn').textContent = 'Cancel & Refund';
        });
    }
    
    function checkEmptyState() {
        if (document.querySelectorAll('.vn-order-item').length === 0) {
            emptyState.classList.remove('d-none');
        }
    }

    // Initialize with active orders passed from backend
    const initialOrders = @json($activeOrders ?? []);
    if (initialOrders && initialOrders.length > 0) {
        initialOrders.forEach(order => {
            addOrderCard(order);
        });
    }
});
</script>
@endpush
