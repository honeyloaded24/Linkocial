@extends('layouts.app')

@section('header_title', 'Virtual Numbers')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { border-radius: 0.5rem !important; padding: 0.75rem 1rem !important; border: 0 !important; background: #f8f9fa !important; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important; font-size: 1.125rem !important; }
    .ts-wrapper.form-select-lg .ts-control { min-height: 3.5rem; display: flex; align-items: center; }
    .ts-dropdown { border-radius: 0.5rem; border: 0; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); z-index: 2000 !important; }
    .ts-dropdown .ts-dropdown-content { max-height: 250px; }
    [data-theme="dark"] .ts-control, [data-bs-theme="dark"] .ts-control { background: var(--bg-surface, #1e293b) !important; color: var(--text-main, #f8fafc) !important; border: 1px solid var(--border-color, #334155) !important; }
    [data-theme="dark"] .ts-control input, [data-bs-theme="dark"] .ts-control input { color: var(--text-main, #f8fafc) !important; }
    [data-theme="dark"] .ts-control input::placeholder, [data-bs-theme="dark"] .ts-control input::placeholder { color: var(--text-muted, #94a3b8) !important; }
    [data-theme="dark"] .ts-dropdown, [data-bs-theme="dark"] .ts-dropdown { background: var(--bg-surface, #1e293b) !important; color: var(--text-main, #f8fafc) !important; border: 1px solid var(--border-color, #334155) !important; }
    [data-theme="dark"] .ts-dropdown .option, [data-bs-theme="dark"] .ts-dropdown .option { color: var(--text-main, #f8fafc) !important; }
    [data-theme="dark"] .ts-dropdown .active, [data-bs-theme="dark"] .ts-dropdown .active { background: #334155 !important; color: #fff !important; }
    [data-theme="dark"] .ts-dropdown .option:hover, [data-bs-theme="dark"] .ts-dropdown .option:hover { background: #334155 !important; color: #fff !important; }

    /* Active virtual-number cards and SMS panels */
    [data-theme="dark"] .vn-order-item.bg-light, [data-bs-theme="dark"] .vn-order-item.bg-light {
        background: var(--bg-surface, #1e293b) !important;
        color: var(--text-main, #f8fafc) !important;
        border: 1px solid var(--border-color, #334155) !important;
    }
    [data-theme="dark"] .vn-order-item .phone-number.bg-white, [data-bs-theme="dark"] .vn-order-item .phone-number.bg-white {
        background: var(--bg-body, #0f172a) !important;
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--border-color, #334155) !important;
    }
    [data-theme="dark"] .vn-order-item .sms-area.bg-white, [data-bs-theme="dark"] .vn-order-item .sms-area.bg-white {
        background: var(--bg-body, #0f172a) !important;
        color: var(--text-main, #f8fafc) !important;
    }

    /* ZuvaPay Server Selection Styles */
    .server-toggle-btn {
        background: var(--bg-surface, #fff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        color: var(--text-main, #334155);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        padding: 0.7rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .server-toggle-btn:hover {
        border-color: #f43f5e;
    }
    .server-toggle-btn.active-server {
        border-color: #f43f5e !important;
        background: rgba(244, 63, 94, 0.06) !important;
        color: var(--text-main, #0f172a) !important;
        box-shadow: 0 0 0 1px #f43f5e;
    }
    .server-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 0.5rem;
    }
    .server-dot-hot {
        background-color: #f43f5e;
        box-shadow: 0 0 8px rgba(244, 63, 94, 0.7);
    }
    .server-dot-idle {
        background-color: #94a3b8;
    }
    .pulse-hot {
        animation: pulseDot 2s infinite;
    }
    @keyframes pulseDot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(244, 63, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(244, 63, 94, 0); }
    }
</style>

@include('partials.service_ticker', ['service' => 'numbers'])

<!-- Desktop Order History Link -->
<div class="d-none d-md-flex justify-content-end align-items-center mb-3">
    <a href="{{ route('virtual-numbers.history') }}" class="btn btn-outline-primary rounded-pill fw-bold btn-sm px-3 shadow-sm">
        <i class="fa-solid fa-clock-rotate-left me-1"></i> Order History & Logs
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6 col-xl-6" style="position: relative; z-index: 10;">
        <!-- Main Form Card -->
        <div class="premium-card p-4 border-0 shadow-sm h-100 rounded-4" style="background: var(--bg-surface); overflow: visible !important;">
            <div class="mb-4">
                <h4 class="mb-0 fw-bold" style="font-family: var(--heading-font);">Get Virtual Number</h4>
            </div>

            <form id="vn-form">
                @csrf

                <!-- Server Selector (ZuvaPay Style) -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted mb-2">Select Server</label>
                    <div class="row g-2">
                        <div class="col-{{ $server3Enabled ? '4' : '6' }}">
                            <button type="button" class="btn w-100 rounded-3 text-center server-toggle-btn active-server" data-server="server1" id="server-btn-1">
                                <span class="server-dot server-dot-hot pulse-hot"></span>
                                <span class="fw-bold">Server 1</span>
                                <span class="badge bg-danger ms-1 text-white" style="font-size: 0.65rem;">HOT</span>
                            </button>
                        </div>
                        <div class="col-{{ $server3Enabled ? '4' : '6' }}">
                            <button type="button" class="btn w-100 rounded-3 text-center server-toggle-btn" data-server="server2" id="server-btn-2">
                                <span class="server-dot server-dot-idle"></span>
                                <span class="fw-bold">Server 2</span>
                            </button>
                        </div>
                        @if($server3Enabled)
                        <div class="col-4">
                            <button type="button" class="btn w-100 rounded-3 text-center server-toggle-btn" data-server="server3" id="server-btn-3">
                                <span class="server-dot server-dot-idle"></span>
                                <span class="fw-bold">Server 3</span>
                            </button>
                        </div>
                        @endif
                    </div>
                    <input type="hidden" name="server" id="vn-server-input" value="server1">
                </div>

                <div class="row g-3">
                    <!-- 1. Select Country -->
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">1. Select Country</label>
                        <select class="form-select form-select-lg rounded-3" id="vn-country" required disabled>
                            <option value="">-- Select Country --</option>
                        </select>
                    </div>
                    
                    <!-- 2. Select Online Service / Application -->
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">2. Select Service</label>
                        <select class="form-select form-select-lg rounded-3" id="vn-service" required disabled>
                            <option value="">-- Select Service --</option>
                        </select>
                    </div>
                </div> <!-- Closes row g-3 -->
                
                <!-- Price & Order Confirmation Container -->
                <div class="mt-3 rounded-4 d-none shadow-sm overflow-hidden" id="vn-price-box" style="background: var(--bg-surface); border: 1.5px solid var(--border-color, #e2e8f0);">
                    <!-- Loading state indicator -->
                    <div id="vn-quote-loading" class="p-3 text-center d-none">
                        <div class="spinner-border spinner-border-sm text-primary-custom mb-1" role="status"></div>
                        <div class="small fw-semibold text-muted">Checking availability...</div>
                    </div>

                    <!-- Clean Quote Content -->
                    <div id="vn-quote-content" class="p-3">
                        <!-- Clean Price & Balance Row -->
                        <div class="p-3 rounded-3 mb-3 d-flex justify-content-between align-items-center" style="background: rgba(99, 102, 241, 0.04); border: 1px solid rgba(99, 102, 241, 0.12);">
                            <div>
                                <span class="text-muted d-block small fw-bold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Price</span>
                                <div class="fw-bold text-primary-custom text-nowrap font-monospace" id="vn-cost" style="font-size: 1.25rem; line-height: 1.2;">₦0.00</div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block small fw-bold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Your Balance</span>
                                <div class="fw-semibold font-monospace text-nowrap" id="vn-user-balance" style="font-size: 0.90rem; color: var(--text-main);">₦0.00</div>
                            </div>
                        </div>

                        <!-- Purchase Button -->
                        <button type="submit" class="btn btn-primary-custom w-100 py-2 py-md-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="vn-buy-btn" style="font-size: 0.95rem;">
                            <span>Purchase Number</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        <!-- Low Balance Warning -->
                        <div class="alert alert-danger mt-3 mb-0 py-2 px-3 rounded-3 d-none align-items-center justify-content-between gap-2" id="vn-insufficient-alert">
                            <div class="small fw-semibold" id="vn-insufficient-msg">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Insufficient balance for this order.
                            </div>
                            <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-danger rounded-pill fw-bold text-nowrap px-3 shadow-sm">
                                <i class="fa-solid fa-wallet me-1"></i> Fund Wallet
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Subtle Refund Note -->
                <div class="text-center mt-3">
                    <small class="text-muted d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-shield-halved text-success"></i> Instant auto-refund if SMS is not received
                    </small>
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

    <!-- Mobile Order History Button -->
    <div class="col-12 d-md-none mt-2">
        <a href="{{ route('virtual-numbers.history') }}" class="btn btn-outline-primary rounded-pill fw-bold w-100 py-2 shadow-sm">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> View Order History & Logs
        </a>
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
                <span class="badge rounded-pill px-3 py-1 fw-bold service-name text-white" style="background: linear-gradient(135deg, #6366f1, #8b5cf6) !important; color: #ffffff !important; font-size: 0.78rem; letter-spacing: 0.3px; box-shadow: 0 2px 4px rgba(99, 102, 241, 0.25);">Service</span>
                <span class="small text-muted fw-bold country-name">Country</span>
            </div>
            
            <div class="input-group mb-3 shadow-sm rounded-3 overflow-hidden flex-nowrap">
                <input type="text" class="form-control text-center phone-number font-monospace fw-bold bg-white" value="" readonly style="font-size: clamp(0.70rem, 3.2vw, 0.95rem); letter-spacing: -0.2px; padding: 0.5rem 0.25rem; min-width: 0;" title="Phone Number">
                <button class="btn btn-primary-custom copy-btn px-2 px-sm-3 flex-shrink-0" type="button" title="Copy Number"><i class="fa-solid fa-copy"></i></button>
            </div>
            <span class="service-name-inline d-none"></span>
            
            <!-- SMS Code Area -->
            <div class="text-center p-3 rounded-3 bg-white border border-secondary border-opacity-25 sms-area">
                <div class="spinner-border spinner-border-sm text-primary-custom mb-2 waiting-spinner" role="status"></div>
                <p class="small text-muted mb-0 waiting-text fw-bold">Waiting for SMS...</p>
                <div class="small fw-bold text-danger mt-2 countdown-timer d-none">
                    <i class="fa-regular fa-clock me-1"></i> Expires in: <span class="time-left">--:--</span>
                </div>
                
                <div class="sms-result d-none">
                    <span class="d-block small text-muted mb-1 sms-sender"></span>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <h3 class="fw-bold text-success mb-0 tracking-widest sms-code user-select-all" style="font-family: monospace; font-size: 1.6rem; cursor: pointer;" title="Click to copy OTP code"></h3>
                        <button class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-bold copy-sms-btn mt-1 shadow-sm" type="button" title="Copy Verification Code">
                            <i class="fa-regular fa-copy me-1"></i> Copy Code
                        </button>
                    </div>
                </div>
            </div>

            <div class="extra-sms-panel mt-2 p-3 rounded-3 border d-none" style="border-color: var(--border-color, #cbd5e1) !important; background: var(--bg-surface, #fff);">
                <div class="small fw-bold text-muted mb-1">Additional SMS</div>
                <div class="extra-sms-waiting small text-muted">Your additional SMS request is being processed...</div>
                <div class="extra-sms-result d-none text-center">
                    <div class="extra-sms-text small text-muted mb-2"></div>
                    <div class="d-flex flex-column align-items-center gap-2">
                        <h4 class="extra-sms-code fw-bold text-success mb-0 user-select-all" style="font-family: monospace; cursor: pointer;" title="Tap to copy additional code"></h4>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill copy-extra-sms-btn"><i class="fa-regular fa-copy me-1"></i> Copy Additional Code</button>
                    </div>
                </div>
            </div>

            <div class="resend-alert mt-2 text-center small text-danger d-none"></div>
            
            <div class="mt-3 text-center d-flex justify-content-center align-items-center gap-2 flex-wrap action-buttons">
                <button class="btn btn-sm btn-outline-danger rounded-pill fw-bold cancel-btn">Cancel & Refund</button>
                <button class="btn btn-sm btn-primary-custom rounded-pill fw-bold resend-btn d-none" title="Request another SMS / Reuse this number">
                    <i class="fa-solid fa-rotate-right me-1"></i> Resend Code / Reuse
                </button>
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
    const quoteLoading = document.getElementById('vn-quote-loading');
    const quoteContent = document.getElementById('vn-quote-content');
    const costDisplay = document.getElementById('vn-cost');
    const userBalanceDisplay = document.getElementById('vn-user-balance');
    const insufficientAlert = document.getElementById('vn-insufficient-alert');
    const errorBox = document.getElementById('vn-error');
    const buyBtn = document.getElementById('vn-buy-btn');

    // Server Selector Logic (ZuvaPay Dual Server Style)
    const serverInput = document.getElementById('vn-server-input');
    const serverBtns = document.querySelectorAll('.server-toggle-btn');
    let currentServer = serverInput ? serverInput.value : 'server1';

    serverBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const chosenServer = this.dataset.server;
            if (chosenServer === currentServer) return;

            currentServer = chosenServer;
            if (serverInput) serverInput.value = currentServer;

            serverBtns.forEach(b => {
                b.classList.remove('active-server');
                const dot = b.querySelector('.server-dot');
                if (b.dataset.server === 'server1') {
                    if (dot) {
                        dot.className = 'server-dot server-dot-hot';
                        dot.style.backgroundColor = '';
                        dot.style.boxShadow = '';
                    }
                } else {
                    if (dot) {
                        dot.className = 'server-dot server-dot-idle';
                        dot.style.backgroundColor = '';
                        dot.style.boxShadow = '';
                    }
                }
            });

            this.classList.add('active-server');
            const activeDot = this.querySelector('.server-dot');
            if (chosenServer === 'server1') {
                if (activeDot) activeDot.className = 'server-dot server-dot-hot pulse-hot';
            } else {
                if (activeDot) {
                    activeDot.className = 'server-dot pulse-hot';
                    activeDot.style.backgroundColor = '#6366f1';
                    activeDot.style.boxShadow = '0 0 8px rgba(99, 102, 241, 0.7)';
                }
            }

            // Reset downstream quote and products
            priceBox.classList.add('d-none');
            if (insufficientAlert) {
                insufficientAlert.classList.add('d-none');
                insufficientAlert.classList.remove('d-flex');
            }
            if (errorBox) errorBox.classList.add('d-none');

            if (serviceTs) {
                serviceTs.clearOptions();
                serviceTs.clear();
                serviceTs.disable();
                serviceTs.settings.placeholder = 'Select country first';
                if (serviceTs.control_input) serviceTs.control_input.placeholder = 'Select country first';
            }

            // Reload countries for this server
            loadCountries(currentServer);
        });
    });

    // Load Tom Select Script dynamically
    const script = document.createElement('script');
    script.src = "https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js";
    script.onload = function() {
        initTomSelect();
    };
    document.head.appendChild(script);

    let countryTs, serviceTs;
    let serviceLoadSequence = 0;

    function setTsPlaceholder(tsInstance, text) {
        if (!tsInstance) return;
        tsInstance.settings.placeholder = text;
        if (tsInstance.control_input) tsInstance.control_input.placeholder = text;
    }

    function loadCountries(server) {
        if (!countryTs) return;
        countryTs.clearOptions();
        countryTs.clear();
        countryTs.disable();
        const serverName = server === 'server2' ? 'Server 2' : (server === 'server3' ? 'Server 3' : 'Server 1');
        setTsPlaceholder(countryTs, `Loading ${serverName} countries...`);

        fetch(`/api/virtual-numbers/countries?server=${encodeURIComponent(server)}`)
            .then(res => res.json())
            .then(countries => {
                // Discard stale response if user switched server while request was in-flight
                if (server !== currentServer) return;

                if (!Array.isArray(countries) || countries.length === 0) {
                    setTsPlaceholder(countryTs, `No countries available on ${serverName}`);
                    return;
                }
                countries.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                countryTs.clearOptions();
                countries.forEach(c => {
                    countryTs.addOption({value: c.ID, text: c.name + (c.cc ? ` (+${c.cc})` : '')});
                });
                setTsPlaceholder(countryTs, 'Select Country');
                countryTs.enable();
                countryTs.clear();
            })
            .catch(err => {
                if (server !== currentServer) return;
                setTsPlaceholder(countryTs, 'Failed to load countries');
                console.error(`Failed to load countries for ${server}:`, err);
            });
    }

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

        // Initial populate of countries for active server
        loadCountries(currentServer);
            
        // On country select, load service pricing for active server
        countryTs.on('change', function(countryId) {
            const requestSequence = ++serviceLoadSequence;
            serviceTs.clearOptions();
            serviceTs.clear();
            priceBox.classList.add('d-none');
            if (insufficientAlert) {
                insufficientAlert.classList.add('d-none');
                insufficientAlert.classList.remove('d-flex');
            }
            if (errorBox) errorBox.classList.add('d-none');
            
            if (countryId) {
                serviceTs.disable();
                setTsPlaceholder(serviceTs, 'Loading services...');
                const requestedServer = currentServer;
                
                const loadServices = async () => {
                    let lastError = null;
                    for (let attempt = 0; attempt < (requestedServer === 'server3' ? 2 : 1); attempt++) {
                        try {
                            const response = await fetch(`/api/virtual-numbers/products?country=${encodeURIComponent(countryId)}&server=${encodeURIComponent(requestedServer)}`);
                            const services = await response.json();

                            // Ignore responses for a previous country or server selection.
                            if (requestSequence !== serviceLoadSequence
                                || requestedServer !== currentServer
                                || String(countryTs.getValue()) !== String(countryId)) return;

                            if (response.ok && Array.isArray(services) && services.length > 0) {
                                services.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                                services.forEach(s => {
                                    serviceTs.addOption({value: s.id, text: `${s.name} — ${s.formatted_price}`, formatted_price: s.formatted_price});
                                });
                                setTsPlaceholder(serviceTs, 'Select Service');
                                serviceTs.enable();
                                return;
                            }

                            lastError = Array.isArray(services)
                                ? (services.length === 0 ? 'No services available' : 'Failed to load services')
                                : (services?.error || 'Failed to load services');

                            // Server 3's Gold catalogue is assembled from several provider
                            // lookups. Retry one transient/empty response before showing failure.
                            if (requestedServer !== 'server3' || attempt === 1) {
                                setTsPlaceholder(serviceTs, lastError);
                                return;
                            }
                        } catch (err) {
                            lastError = 'Failed to load services';
                            if (requestedServer !== 'server3' || attempt === 1) {
                                if (requestSequence === serviceLoadSequence && requestedServer === currentServer) {
                                    setTsPlaceholder(serviceTs, lastError);
                                }
                                console.error('Failed to load services', err);
                                return;
                            }
                        }

                        await new Promise(resolve => setTimeout(resolve, 600));
                    }
                    if (requestSequence === serviceLoadSequence && requestedServer === currentServer) {
                        setTsPlaceholder(serviceTs, lastError || 'Failed to load services');
                    }
                };
                loadServices();
            } else {
                setTsPlaceholder(serviceTs, 'Select country first');
                serviceTs.disable();
            }
        });
        
        // On service select, fetch real-time live price quote with markup & balance check
        serviceTs.on('change', function(val) {
            if (errorBox) errorBox.classList.add('d-none');
            if (insufficientAlert) {
                insufficientAlert.classList.add('d-none');
                insufficientAlert.classList.remove('d-flex');
            }

            if (val) {
                const countryId = countryTs.getValue();
                const requestedServer = currentServer;
                priceBox.classList.remove('d-none');
                if (quoteLoading) quoteLoading.classList.remove('d-none');
                if (quoteContent) quoteContent.classList.add('d-none');
                buyBtn.disabled = true;

                fetch(`/api/virtual-numbers/quote?country=${encodeURIComponent(countryId)}&product=${encodeURIComponent(val)}&server=${encodeURIComponent(requestedServer)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (requestedServer !== currentServer) return;
                        if (quoteLoading) quoteLoading.classList.add('d-none');
                        if (data.success) {
                            currentQuotedPrice = data.quote_price;
                            if (quoteContent) quoteContent.classList.remove('d-none');
                            costDisplay.textContent = data.formatted_quote;
                            if (userBalanceDisplay) userBalanceDisplay.textContent = data.formatted_balance;

                            // Dynamically update dropdown option text so dropdown and quote card always match 100%
                            if (serviceTs && serviceTs.options && serviceTs.options[val]) {
                                const currentOpt = serviceTs.options[val];
                                const rawName = data.service_name || currentOpt.text.split(' — ')[0];
                                const newText = `${rawName} — ${data.formatted_quote}`;
                                if (currentOpt.text !== newText) {
                                    serviceTs.updateOption(val, {
                                        value: val,
                                        text: newText,
                                        formatted_price: data.formatted_quote
                                    });
                                }
                            }

                            if (!data.sufficient_balance) {
                                if (insufficientAlert) {
                                    const msgEl = document.getElementById('vn-insufficient-msg');
                                    if (msgEl) {
                                        if (data.formatted_shortfall) {
                                            msgEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> Insufficient balance. Short by <strong>${data.formatted_shortfall}</strong>.`;
                                        } else {
                                            msgEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> Insufficient balance for this order.`;
                                        }
                                    }
                                    insufficientAlert.classList.remove('d-none');
                                    insufficientAlert.classList.add('d-flex');
                                }
                                buyBtn.disabled = true;
                                buyBtn.innerHTML = 'Insufficient Balance';
                            } else {
                                if (insufficientAlert) {
                                    insufficientAlert.classList.add('d-none');
                                    insufficientAlert.classList.remove('d-flex');
                                }
                                buyBtn.disabled = false;
                                buyBtn.innerHTML = '<span>Purchase Number</span> <i class="fa-solid fa-arrow-right ms-2"></i>';
                            }
                        } else {
                            priceBox.classList.add('d-none');
                            if (errorBox) {
                                if (data.out_of_stock) {
                                    errorBox.innerHTML = `<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-boxes-packing fs-5 text-warning"></i> <div><strong>Out of Stock:</strong> ${data.message}</div></div>`;
                                } else {
                                    errorBox.textContent = data.message || 'Service is temporarily unavailable for this country. Please select another.';
                                }
                                errorBox.classList.remove('d-none');
                            }
                        }
                    })
                    .catch(err => {
                        if (requestedServer !== currentServer) return;
                        if (quoteLoading) quoteLoading.classList.add('d-none');
                        console.error('Failed to fetch live quote:', err);
                        // Fallback to option price if network glitch
                        const option = serviceTs.options[val];
                        if (option && option.formatted_price) {
                            if (quoteContent) quoteContent.classList.remove('d-none');
                            costDisplay.textContent = option.formatted_price;
                            currentQuotedPrice = option.price || null;
                            buyBtn.disabled = false;
                            buyBtn.innerHTML = 'Purchase Number <i class="fa-solid fa-arrow-right ms-2"></i>';
                        }
                    });
            } else {
                priceBox.classList.add('d-none');
            }
        });
    }

    let currentQuotedPrice = null;

    // Handle Form Submit (Purchase)
    document.getElementById('vn-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        errorBox.classList.add('d-none');
        buyBtn.disabled = true;
        buyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
        
        const selectedCountry = countryTs ? countryTs.getValue() : countrySelect.value;
        const selectedProduct = serviceTs ? serviceTs.getValue() : serviceSelect.value;

        fetch('/api/virtual-numbers/purchase', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                server: currentServer,
                country: selectedCountry,
                product: selectedProduct,
                expected_price: currentQuotedPrice
            })
        })
        .then(res => res.json())
        .then(res => {
            buyBtn.disabled = false;
            buyBtn.innerHTML = '<span>Purchase Number</span> <i class="fa-solid fa-arrow-right ms-2"></i>';
            
            if (res.error) {
                if (res.price_changed && res.new_price) {
                    currentQuotedPrice = res.new_price;
                    costDisplay.textContent = res.formatted_new_price;
                    errorBox.innerHTML = '<div class="alert alert-warning mb-0 py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> ' + res.error + '</div>';
                    errorBox.classList.remove('d-none');
                    buyBtn.disabled = false;
                    buyBtn.innerHTML = '<span>Confirm & Purchase (' + res.formatted_new_price + ')</span> <i class="fa-solid fa-arrow-right ms-2"></i>';
                } else {
                    errorBox.innerHTML = res.error;
                    errorBox.classList.remove('d-none');
                }
            } else if (res.success && res.order) {
                // Success! Create active order card
                addOrderCard(res.order);
                currentQuotedPrice = null;
                // Clear selection
                if (serviceTs) {
                    serviceTs.clear();
                } else if (serviceSelect.tomselect) {
                    serviceSelect.tomselect.clear();
                } else {
                    serviceSelect.value = '';
                }
                priceBox.classList.add('d-none');
            }
        })
        .catch(err => {
            buyBtn.disabled = false;
            buyBtn.innerHTML = '<span>Purchase Number</span> <i class="fa-solid fa-arrow-right ms-2"></i>';
            errorBox.innerHTML = 'A network error occurred. Please try again.';
            errorBox.classList.remove('d-none');
        });
    });
    
    // Active Orders Tracking
    const activeOrdersContainer = document.getElementById('vn-active-orders');
    const emptyState = document.getElementById('vn-empty-state');
    const activePolls = {};
    
    function showReceivedState(cardNode, smsCode) {
        if (cardNode.dataset.timerId) clearInterval(cardNode.dataset.timerId);
        const countdownEl = cardNode.querySelector('.countdown-timer');
        if (countdownEl) countdownEl.classList.add('d-none');
        
        cardNode.querySelector('.waiting-spinner').classList.add('d-none');
        cardNode.querySelector('.waiting-text').classList.add('d-none');
        cardNode.querySelector('.sms-result').classList.remove('d-none');
        cardNode.querySelector('.sms-code').textContent = smsCode;
        
        // Hide cancel button and show resend button
        cardNode.querySelector('.cancel-btn').classList.add('d-none');
        const resendBtn = cardNode.querySelector('.resend-btn');
        if (resendBtn && cardNode.dataset.supportsResend !== '0') {
            resendBtn.classList.remove('d-none');
            resendBtn.disabled = false;
            resendBtn.innerHTML = resendBtn.title && resendBtn.title.includes('USD $0.20')
                ? '<i class="fa-solid fa-rotate-right me-1"></i> Request Another SMS · $0.20 converted'
                : '<i class="fa-solid fa-rotate-right me-1"></i> Resend Code / Reuse';
        }
        
        const smsArea = cardNode.querySelector('.sms-area');
        smsArea.classList.remove('bg-white');
        smsArea.classList.add('bg-success', 'bg-opacity-10');
    }

    function showWaitingState(cardNode, isResent = false) {
        cardNode.querySelector('.waiting-spinner').classList.remove('d-none');
        const waitingText = cardNode.querySelector('.waiting-text');
        waitingText.classList.remove('d-none');
        waitingText.textContent = isResent ? 'Waiting for next SMS (Resent)...' : 'Waiting for SMS...';
        
        // Hide old SMS OTP result when requesting a new code
        const smsResult = cardNode.querySelector('.sms-result');
        if (smsResult) smsResult.classList.add('d-none');

        const smsArea = cardNode.querySelector('.sms-area');
        smsArea.classList.remove('bg-success', 'bg-opacity-10');
        smsArea.classList.add('bg-white');
        
        cardNode.querySelector('.cancel-btn').classList.add('d-none');
        const resendBtn = cardNode.querySelector('.resend-btn');
        if (resendBtn) resendBtn.classList.add('d-none');
    }

    function renderExtraSms(cardNode, order) {
        const panel = cardNode.querySelector('.extra-sms-panel');
        if (!panel || !order || (!order.daisy_extra_activation_id && !order.daisy_extra_sms_code)) return;
        const resendButton = cardNode.querySelector('.resend-btn');
        if (resendButton) resendButton.classList.add('d-none');
        panel.classList.remove('d-none');
        const waiting = panel.querySelector('.extra-sms-waiting');
        const result = panel.querySelector('.extra-sms-result');
        const code = (order.daisy_extra_sms_code || '').toString();
        if (code) {
            waiting.classList.add('d-none');
            result.classList.remove('d-none');
            panel.querySelector('.extra-sms-code').textContent = code;
            panel.querySelector('.extra-sms-text').textContent = order.daisy_extra_sms_text || '';
        } else {
            result.classList.add('d-none');
            waiting.classList.remove('d-none');
            if (order.daisy_extra_activation_status === 'waiting') {
                waiting.textContent = 'Your additional SMS is queued and will be checked automatically.';
            } else if (order.daisy_extra_activation_status === 'failed' || order.daisy_extra_activation_status === 'canceled') {
                waiting.textContent = 'The additional SMS activation ended without a code. The request fee remains charged because the provider accepted the request.';
            } else {
                waiting.textContent = 'Waiting for your additional SMS...';
            }
        }
    }

    function addOrderCard(order) {
        emptyState.classList.add('d-none');
        
        const template = document.getElementById('vn-order-template');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.vn-order-item');
        
        card.setAttribute('data-id', order.id);
        card.dataset.supportsResend = order.supports_resend === false ? '0' : '1';
        if (order.resend_fee_notice) {
            const feeButton = card.querySelector('.resend-btn');
            if (feeButton) {
                feeButton.title = order.resend_fee_notice;
                feeButton.innerHTML = '<i class="fa-solid fa-rotate-right me-1"></i> Request Another SMS · $0.20 converted';
            }
        }

        // An uncertain DaisySMS extra-activation request must remain locked after
        // a page refresh. Do not invite the customer to submit a duplicate request.
        if (order.provider_key === 'daisysms' &&
            ['requesting', 'requested', 'waiting', 'ready', 'unknown'].includes(order.daisy_extra_activation_status)) {
            const feeButton = card.querySelector('.resend-btn');
            const alertBox = card.querySelector('.resend-alert');
            if (feeButton) {
                feeButton.disabled = true;
                feeButton.classList.add('d-none');
            }
            if (alertBox) {
                alertBox.textContent = order.daisy_extra_activation_status === 'unknown'
                    ? 'Your additional SMS request is being verified. Please do not request another SMS while this check is in progress.'
                    : 'An additional SMS request has already been submitted for this number.';
                alertBox.classList.remove('d-none');
            }
        }
        const serviceName = order.product || order.service || 'Service';
        card.querySelector('.service-name').textContent = serviceName;
        card.querySelector('.service-name-inline').textContent = serviceName;
        const serverBadge = (order.server === 'server2')
            ? '<span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Server 2</span>'
            : (order.server === 'server3'
                ? '<span class="badge bg-primary ms-1" style="font-size: 0.65rem;">Server 3</span>'
                : '<span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Server 1</span>');
        card.querySelector('.country-name').innerHTML = `${order.country} ${serverBadge}`;
        card.querySelector('.phone-number').value = order.phone_number;
        
        // Setup countdown timer for pending orders
        if (order.expires_at && order.status === 'pending') {
            const countdownEl = card.querySelector('.countdown-timer');
            const timeLeftEl = card.querySelector('.time-left');
            countdownEl.classList.remove('d-none');
            
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
                    if (cancelBtn && !cancelBtn.disabled && !cancelBtn.classList.contains('d-none')) {
                        cancelBtn.click();
                    }
                } else {
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timeLeftEl.textContent = minutes.toString().padStart(2, '0') + ":" + seconds.toString().padStart(2, '0');
                }
            }, 1000);
            
            card.dataset.timerId = timerInterval;
        }

        // Copy phone button
        card.querySelector('.copy-btn').addEventListener('click', function() {
            navigator.clipboard.writeText(order.phone_number);
            const icon = this.querySelector('i');
            icon.className = 'fa-solid fa-check text-success';
            setTimeout(() => { icon.className = 'fa-solid fa-copy'; }, 2000);
        });

        // Copy SMS Code action (both on button and clicking code itself)
        const copySmsAction = () => {
            const smsCode = card.querySelector('.sms-code').textContent.trim();
            if (smsCode && smsCode !== 'Waiting for SMS...' && smsCode !== 'Received') {
                navigator.clipboard.writeText(smsCode);
                const btn = card.querySelector('.copy-sms-btn');
                if (btn) {
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
                    btn.classList.replace('btn-outline-success', 'btn-success');
                    btn.classList.add('text-white');
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.replace('btn-success', 'btn-outline-success');
                        btn.classList.remove('text-white');
                    }, 2000);
                }
            }
        };

        card.querySelector('.copy-sms-btn')?.addEventListener('click', copySmsAction);
        const copyExtraSms = () => {
            const value = card.querySelector('.extra-sms-code')?.textContent.trim();
            if (value) navigator.clipboard.writeText(value);
        };
        card.querySelector('.copy-extra-sms-btn')?.addEventListener('click', copyExtraSms);
        card.querySelector('.extra-sms-code')?.addEventListener('click', copyExtraSms);
        renderExtraSms(card, order);
        card.querySelector('.sms-code')?.addEventListener('click', copySmsAction);
        
        // Cancel btn
        card.querySelector('.cancel-btn').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'Canceling...';
            cancelOrder(order.id, card);
        });

        // Resend / Reuse btn
        const resendBtn = card.querySelector('.resend-btn');
        if (resendBtn) {
            resendBtn.addEventListener('click', function() {
                const btn = this;
                const alertBox = card.querySelector('.resend-alert');
                alertBox.classList.add('d-none');
                alertBox.textContent = '';
                
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Requesting Resend...';
                
                fetch(`/api/virtual-numbers/${order.id}/resend`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'unknown') {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa-solid fa-clock me-1"></i> Verifying Request...';
                        alertBox.textContent = data.error || 'Your additional SMS request is being verified. Please do not submit another request.';
                        alertBox.classList.remove('d-none');
                        return;
                    }

                    if (data.error) {
                        btn.disabled = false;
                        btn.innerHTML = btn.title && btn.title.includes('USD $0.20')
                            ? '<i class="fa-solid fa-rotate-right me-1"></i> Request Another SMS · $0.20 converted'
                            : '<i class="fa-solid fa-rotate-right me-1"></i> Resend Code / Reuse';
                        alertBox.textContent = data.error;
                        alertBox.classList.remove('d-none');
                        return;
                    }
                    
                    // DaisySMS adds a second activation on the same number. Keep the first
                    // code visible and show a separate panel for the additional message.
                    if (data.extra_sms_status) {
                        renderExtraSms(card, {
                            daisy_extra_activation_id: true,
                            daisy_extra_activation_status: data.extra_sms_status
                        });
                    } else {
                        showWaitingState(card, true);
                    }
                    startPolling(order.id, card);
                })
                .catch(err => {
                    console.error('Failed to request resend:', err);
                    // The request may have reached the server even if its response
                    // was lost. Keep the control locked until the customer refreshes
                    // and the order's persisted activation state is reloaded.
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-clock me-1"></i> Verifying Request...';
                    alertBox.textContent = 'We could not confirm the response. Your request may still be processing. Please refresh the order before trying again.';
                    alertBox.classList.remove('d-none');
                });
            });
        }
        
        activeOrdersContainer.prepend(card);
        
        // Check order status
        if ((order.status === 'received' || order.status === 'finished') && order.sms_code) {
            showReceivedState(card, order.sms_code);
            renderExtraSms(card, order);
            if (order.daisy_extra_activation_id && !order.daisy_extra_sms_code && !['failed', 'canceled'].includes(order.daisy_extra_activation_status)) startPolling(order.id, card);
        } else {
            // Keep polling orders whose provider marked SMS received but whose code was not saved.
            startPolling(order.id, card);
        }
    }
    
    function startPolling(id, cardNode) {
        if (activePolls[id]) clearInterval(activePolls[id]);
        
        activePolls[id] = setInterval(() => {
            fetch(`/api/virtual-numbers/${id}/check`)
                .then(res => res.json())
                .then(data => {
                    const status = data.order?.status;
                    if (data.order) renderExtraSms(cardNode, data.order);
                    if (data.order && data.order.daisy_extra_activation_id && !data.order.daisy_extra_sms_code && !['failed', 'canceled'].includes(data.order.daisy_extra_activation_status)) {
                        // Continue polling the additional activation while preserving the original SMS.
                        if (status === 'received' || status === 'finished') return;
                    }
                    if (data.order && (status === 'received' || status === 'finished') && data.order.sms_code) {
                        clearInterval(activePolls[id]);
                        delete activePolls[id];
                        showReceivedState(cardNode, data.order.sms_code);
                        // showReceivedState reveals the resend control; hide it again
                        // when the one permitted DaisySMS extra activation already exists.
                        renderExtraSms(cardNode, data.order);
                    } else if (data.order && (status === 'canceled' || status === 'expired' || status === 'refunded' || status === 'failed')) {
                        clearInterval(activePolls[id]);
                        delete activePolls[id];
                        if (cardNode.dataset.timerId) clearInterval(cardNode.dataset.timerId);
                        cardNode.remove();
                        checkEmptyState();
                    }
                })
                .catch(err => console.error('Failed to poll order status:', err));
        }, 15000); // DaisySMS recommends polling every 15 seconds
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
