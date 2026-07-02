@extends('layouts.app')

@section('header_title', 'Virtual Numbers')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { border-radius: 0.5rem !important; padding: 0.75rem 1rem !important; border: 0 !important; background: #f8f9fa !important; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important; font-size: 1.125rem !important; }
    .ts-wrapper.form-select-lg .ts-control { min-height: 3.5rem; display: flex; align-items: center; }
    .ts-dropdown { border-radius: 0.5rem; border: 0; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
    .ts-dropdown .ts-dropdown-content { max-height: 250px; }
    [data-theme="dark"] .ts-control { background: #1e293b !important; color: #f8fafc; border: 1px solid #334155 !important; }
    [data-theme="dark"] .ts-control input { color: #f8fafc; }
    [data-theme="dark"] .ts-dropdown { background: #1e293b; color: #f8fafc; border: 1px solid #334155; }
    [data-theme="dark"] .ts-dropdown .active { background: #334155; color: #fff; }
</style>
<div class="row g-4">
    <div class="col-12 col-lg-6 col-xl-6">
        <!-- Main Form Card -->
        <div class="premium-card p-4 border-0 shadow-sm h-100 rounded-4" style="background: var(--surface-bg);">
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
                        <select class="form-select form-select-lg rounded-3 border-0 bg-light shadow-sm" id="vn-country" required disabled>
                            <option value="">Loading countries...</option>
                        </select>
                    </div>
                    
                    <!-- Service Select -->
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">Select Service</label>
                        <select class="form-select form-select-lg rounded-3 border-0 bg-light shadow-sm" id="vn-service" required disabled>
                            <option value="">Select country first</option>
                        </select>
                    </div>
                    <!-- Coupon Code Input -->
                    <div class="col-12 mt-3">
                        <label class="form-label fw-bold small text-muted">Have a Coupon?</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg rounded-start-3 border-0 bg-light shadow-sm" id="vn-coupon-code" placeholder="Enter coupon code" style="text-transform: uppercase;">
                            <button class="btn btn-outline-secondary border-0 bg-light shadow-sm px-3 d-none" type="button" id="vn-coupon-clear-btn" style="color: var(--text-muted);">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <button class="btn btn-primary-custom px-4" type="button" id="vn-coupon-apply-btn">Apply</button>
                        </div>
                        <div class="form-text small text-success mt-1 d-none fw-bold" id="vn-coupon-success-msg"></div>
                        <div class="form-text small text-danger mt-1 d-none fw-bold" id="vn-coupon-error-msg"></div>
                    </div>
                </div>
                
                <div class="mt-4 p-3 p-sm-4 rounded-4 bg-primary-custom text-white d-flex flex-column flex-sm-row justify-content-between align-items-sm-center d-none shadow" id="vn-price-box">
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
                
                <!-- Error Alert -->
                <div class="alert alert-danger mt-3 d-none" id="vn-error"></div>
            </form>
        </div>
    </div>

    <!-- Active Orders Sidebar -->
    <div class="col-12 col-lg-6 col-xl-6">
        <div class="premium-card p-4 border-0 shadow-sm h-100 rounded-4" style="background: var(--surface-bg);">
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

<!-- Active Order Template (Hidden) -->
<template id="vn-order-template">
    <div class="card border-0 bg-light shadow-sm rounded-4 mb-3 vn-order-item" data-id="">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary-custom bg-opacity-10 text-primary-custom rounded-pill service-name">Service</span>
                <span class="small text-muted fw-bold country-name">Country</span>
            </div>
            
            <div class="input-group mb-3">
                <input type="text" class="form-control fw-bold fs-5 text-center phone-number" value="" readonly style="letter-spacing: 2px;">
                <button class="btn btn-primary-custom copy-btn" type="button"><i class="fa-solid fa-copy"></i></button>
            </div>
            
            <!-- SMS Code Area -->
            <div class="text-center p-3 rounded-3 bg-white border border-light sms-area">
                <div class="spinner-border spinner-border-sm text-primary-custom mb-2 waiting-spinner" role="status"></div>
                <p class="small text-muted mb-0 waiting-text fw-bold">Waiting for SMS...</p>
                <div class="small fw-bold text-danger mt-2 countdown-timer d-none">
                    <i class="fa-regular fa-clock me-1"></i> Expires in: <span class="time-left">--:--</span>
                </div>
                
                <div class="sms-result d-none">
                    <span class="d-block small text-muted mb-1 sms-sender"></span>
                    <h3 class="fw-bold text-success mb-0 tracking-widest sms-code"></h3>
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

    // Coupon Elements
    const couponInput = document.getElementById('vn-coupon-code');
    const couponApplyBtn = document.getElementById('vn-coupon-apply-btn');
    const couponClearBtn = document.getElementById('vn-coupon-clear-btn');
    const couponSuccessMsg = document.getElementById('vn-coupon-success-msg');
    const couponErrorMsg = document.getElementById('vn-coupon-error-msg');
    const originalCostDisplay = document.getElementById('vn-original-cost');
    let currentOriginalPrice = 0.00;
    let appliedCouponCode = null;

    function resetCouponState() {
        appliedCouponCode = null;
        couponInput.value = '';
        couponInput.disabled = false;
        couponApplyBtn.disabled = false;
        couponClearBtn.classList.add('d-none');
        couponSuccessMsg.classList.add('d-none');
        couponErrorMsg.classList.add('d-none');
        originalCostDisplay.classList.add('d-none');
        if (currentOriginalPrice > 0) {
            costDisplay.textContent = `$${currentOriginalPrice.toFixed(2)}`;
        }
    }
    
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
            resetCouponState();
            
            if (countryId) {
                serviceTs.disable();
                serviceTs.settings.placeholder = 'Loading services...';
                
                fetch(`/api/virtual-numbers/products?country=${countryId}`)
                    .then(res => res.json())
                    .then(services => {
                        services.sort((a, b) => a.name.localeCompare(b.name));
                        services.forEach(s => {
                            serviceTs.addOption({value: s.id, text: `${s.name} — $${s.price.toFixed(2)}`, price: s.price});
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
            resetCouponState();
            if (val) {
                const option = serviceTs.options[val];
                if (option && option.price) {
                    const price = parseFloat(option.price);
                    currentOriginalPrice = price;
                    costDisplay.textContent = `$${price.toFixed(2)}`;
                    priceBox.classList.remove('d-none');
                }
            } else {
                currentOriginalPrice = 0;
                priceBox.classList.add('d-none');
            }
        });
    }

    // Coupon Apply
    couponApplyBtn.addEventListener('click', function() {
        const code = couponInput.value.trim();
        const country = countrySelect.value;
        const product = serviceSelect.value;

        couponSuccessMsg.classList.add('d-none');
        couponErrorMsg.classList.add('d-none');

        if (!code) {
            couponErrorMsg.textContent = 'Please enter a coupon code.';
            couponErrorMsg.classList.remove('d-none');
            return;
        }

        if (!country || !product) {
            couponErrorMsg.textContent = 'Please select a country and service first.';
            couponErrorMsg.classList.remove('d-none');
            return;
        }

        couponApplyBtn.disabled = true;
        couponApplyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        fetch('/api/virtual-numbers/coupon/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ coupon_code: code, country, product })
        })
        .then(res => res.json())
        .then(res => {
            couponApplyBtn.disabled = false;
            couponApplyBtn.innerHTML = 'Apply';

            if (res.error) {
                couponErrorMsg.textContent = res.error;
                couponErrorMsg.classList.remove('d-none');
            } else if (res.success) {
                appliedCouponCode = res.coupon_code;
                couponInput.disabled = true;
                couponApplyBtn.disabled = true;
                couponClearBtn.classList.remove('d-none');
                
                couponSuccessMsg.textContent = `Coupon applied! Discount: $${parseFloat(res.discount).toFixed(2)}`;
                couponSuccessMsg.classList.remove('d-none');

                costDisplay.textContent = `$${parseFloat(res.final_price).toFixed(2)}`;
                originalCostDisplay.textContent = `$${parseFloat(res.original_price).toFixed(2)}`;
                originalCostDisplay.classList.remove('d-none');
            }
        })
        .catch(err => {
            couponApplyBtn.disabled = false;
            couponApplyBtn.innerHTML = 'Apply';
            couponErrorMsg.textContent = 'A network error occurred. Please try again.';
            couponErrorMsg.classList.remove('d-none');
        });
    });

    couponClearBtn.addEventListener('click', resetCouponState);


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
                product: serviceSelect.value,
                coupon_code: appliedCouponCode
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
                resetCouponState();
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
