@extends('layouts.app')

@section('header_title', 'Add Funds')

@section('content')
<div class="container-fluid px-3 px-md-4 py-2">
    
    <!-- Balance & Overview Hero Card (Full Width) -->
    <div class="premium-card p-4 p-md-5 mb-4 position-relative overflow-hidden" style="border: 1px solid var(--border-color); background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(16, 185, 129, 0.04) 100%);">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-7 col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="d-flex align-items-center justify-content-center rounded-3 text-white shadow-sm flex-shrink-0" style="width: 48px; height: 48px; background: linear-gradient(135deg, #6366f1, #4f46e5);">
                        <i class="fa-solid fa-wallet fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-main">Fund Your Wallet</h3>
                        <p class="text-muted small mb-0">Select your preferred payment channel. Every deposit powers all Linkocial services automatically.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-5 col-lg-4 text-md-end">
                <div class="d-inline-flex flex-column align-items-md-end">
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Available Balance</span>
                    <span class="fs-2 fw-bolder text-primary-custom lh-1 mt-1 mb-2">{{ money(Auth::user()->balance ?? 0) }}</span>
                    <a href="{{ route('wallet.history') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Transaction History
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2-Channel Full-Width Tabs Switcher -->
    <ul class="nav nav-pills p-1 rounded-4 mb-4 shadow-sm" style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--border-color);" id="walletTabs" role="tablist">
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link active rounded-3 fw-bold py-3 text-center w-100" id="tab-virtual-btn" data-bs-toggle="tab" data-bs-target="#tab-virtual" type="button" role="tab" aria-controls="tab-virtual" aria-selected="true">
                <i class="fa-solid fa-building-columns me-2 fs-5 align-middle"></i>
                <span class="align-middle">Dedicated Virtual Account</span>
                <span class="badge bg-success bg-opacity-25 text-success ms-2 px-2 py-1 align-middle">⚡ Instant Auto-Credit</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link rounded-3 fw-bold py-3 text-center w-100" id="tab-korapay-btn" data-bs-toggle="tab" data-bs-target="#tab-korapay" type="button" role="tab" aria-controls="tab-korapay" aria-selected="false">
                <i class="fa-solid fa-credit-card me-2 fs-5 align-middle"></i>
                <span class="align-middle">Korapay Checkout</span>
                <span class="badge bg-primary bg-opacity-25 text-primary ms-2 px-2 py-1 align-middle">Cards & Mobile Money</span>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="walletTabsContent">
        
        <!-- ========================================== -->
        <!-- TAB 1: DEDICATED VIRTUAL ACCOUNTS (9PSB & PALMPAY) -->
        <!-- ========================================== -->
        <div class="tab-pane fade show active" id="tab-virtual" role="tabpanel" aria-labelledby="tab-virtual-btn">
            
            <!-- Live Automation Notification Bar -->
            <div class="p-3 p-md-4 mb-4 rounded-4 d-flex align-items-center gap-3" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25);">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle" style="width: 44px; height: 44px; background: rgba(16, 185, 129, 0.2); color: #10b981;">
                    <i class="fa-solid fa-bolt-lightning fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold small text-success d-flex align-items-center gap-2">
                        <span>Automated Bank Transfer Crediting Active</span>
                        <span class="d-inline-block rounded-circle bg-success" style="width: 9px; height: 9px; animation: pulseDot 1.5s infinite;"></span>
                    </div>
                    <div class="text-muted small mt-1">
                        Transfer any amount to your assigned 9PSB or PalmPay dedicated bank account from any Nigerian bank (OPay, Kuda, GTBank, Zenith, Access, USSD, etc.). Your wallet is automatically credited within 30 seconds.
                    </div>
                </div>
            </div>

            <!-- Spacious 2-Column Accounts Grid -->
            <div class="row g-4 mb-4">
                
                <!-- 9PSB Bank Card (Col 1) -->
                <div class="col-12 col-xl-6" id="cardContainer9psb">
                    <div class="premium-card h-100 p-4 p-md-5 d-flex flex-column justify-content-between position-relative" style="border: 1px solid var(--border-color); background: var(--bg-surface);">
                        @if($account9psb)
                            <div>
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                            <i class="fa-solid fa-building-columns fs-5"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-0 text-main">9PSB Bank</h5>
                                            <span class="text-muted x-small">9Payment Service Bank</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 px-2 py-1 small">No BVN Needed</span>
                                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 small">
                                            <i class="fa-solid fa-circle-dot me-1"></i> Active
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-2">Dedicated Account Number</label>
                                    <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-4 bg-light bg-opacity-10 border border-secondary border-opacity-25">
                                        <span class="fs-2 fw-bolder font-monospace text-main" id="accNumber9psb" style="letter-spacing: 2px;">{{ $account9psb->account_number }}</span>
                                        <button type="button" class="btn btn-primary-custom px-4 py-2 rounded-pill fw-bold" onclick="copyAccount('accNumber9psb', this)">
                                            <i class="fa-regular fa-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3 py-2">
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Account Name</span>
                                        <span class="fw-bold text-main fs-6">{{ $account9psb->account_name }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Bank Name</span>
                                        <span class="fw-bold text-main fs-6">{{ $account9psb->bank_name ?: '9Payment Service Bank (9PSB)' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-top border-secondary border-opacity-10 d-flex align-items-center justify-content-between text-muted x-small">
                                <span><i class="fa-solid fa-check text-success me-1"></i> Permanent dedicated account</span>
                                <span>Zero Deposit Minimum</span>
                            </div>
                        @else
                            <div class="text-center py-4 my-auto">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 64px; height: 64px; background: rgba(99, 102, 241, 0.1); color: var(--primary-color, #6366f1);">
                                    <i class="fa-solid fa-building-columns fs-2"></i>
                                </div>
                                <h4 class="fw-bold mb-2">9PSB Dedicated Account</h4>
                                <p class="text-muted small mb-4 mx-auto" style="max-width: 420px;">
                                    Generate your permanent 9PSB bank account number instantly with 1 click. Zero documents or BVN required.
                                </p>
                                <div class="d-flex justify-content-center mb-4">
                                    <ul class="list-unstyled text-start small text-muted mb-0">
                                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Instant automated activation</li>
                                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> No BVN or KYC upload needed</li>
                                        <li><i class="fa-solid fa-circle-check text-success me-2"></i> Automated instant wallet balance crediting</li>
                                    </ul>
                                </div>
                                <button type="button" id="btnGen9psb" class="btn btn-lg btn-primary-custom rounded-pill px-5 py-3 fw-bold shadow-sm" onclick="generateAccount('9PSB')">
                                    <i class="fa-solid fa-bolt me-2"></i> Generate 9PSB Account
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- PalmPay Bank Card (Col 2) -->
                <div class="col-12 col-xl-6" id="cardContainerPalmpay">
                    <div class="premium-card h-100 p-4 p-md-5 d-flex flex-column justify-content-between position-relative" style="border: 1px solid var(--border-color); background: var(--bg-surface);">
                        @if($accountPalmpay)
                            <div>
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-info bg-opacity-10 text-info p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                            <i class="fa-solid fa-mobile-screen-button fs-5"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-0 text-main">PalmPay</h5>
                                            <span class="text-muted x-small">PalmPay Digital Bank</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25 px-2 py-1 small">CBN Regulated</span>
                                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 small">
                                            <i class="fa-solid fa-circle-dot me-1"></i> Active
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-2">Dedicated Account Number</label>
                                    <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-4 bg-light bg-opacity-10 border border-secondary border-opacity-25">
                                        <span class="fs-2 fw-bolder font-monospace text-main" id="accNumberPalmpay" style="letter-spacing: 2px;">{{ $accountPalmpay->account_number }}</span>
                                        <button type="button" class="btn btn-primary-custom px-4 py-2 rounded-pill fw-bold" onclick="copyAccount('accNumberPalmpay', this)">
                                            <i class="fa-regular fa-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3 py-2">
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Account Name</span>
                                        <span class="fw-bold text-main fs-6">{{ $accountPalmpay->account_name }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-muted small d-block">Bank Name</span>
                                        <span class="fw-bold text-main fs-6">PalmPay</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-top border-secondary border-opacity-10 d-flex align-items-center justify-content-between text-muted x-small">
                                <span><i class="fa-solid fa-shield-halved text-success me-1"></i> Verified & Dedicated to your account</span>
                                <span>Zero Deposit Minimum</span>
                            </div>
                        @else
                            <div>
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-info bg-opacity-10 text-info p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fa-solid fa-mobile-screen-button fs-6"></i>
                                        </div>
                                        <h5 class="fw-bold mb-0 text-main">PalmPay Account</h5>
                                    </div>
                                    <span class="badge bg-secondary bg-opacity-15 text-muted px-2 py-1 small">
                                        <i class="fa-solid fa-lock text-success me-1"></i> CBN KYC Mandated
                                    </span>
                                </div>
                                
                                <p class="text-muted small mb-4">
                                    Under CBN regulations, PalmPay virtual accounts require your 11-digit BVN to verify account ownership.
                                </p>

                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Enter 11-Digit BVN</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-transparent text-muted border-secondary border-opacity-25"><i class="fa-solid fa-id-card"></i></span>
                                        <input type="tel" id="bvnInputPalmpay" maxlength="11" class="form-control border-secondary border-opacity-25 bg-transparent" placeholder="e.g. 22233344455" style="color: var(--text-main); font-size: 1.15rem; font-weight: 600;" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                    <div class="text-muted x-small mt-2 d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-shield-halved text-success"></i>
                                        <span><strong>Bank-Grade Security:</strong> Your BVN is verified directly via encrypted channel and is never stored on our servers.</span>
                                    </div>
                                </div>

                                <button type="button" id="btnGenPalmpay" class="btn btn-lg btn-primary-custom rounded-pill px-5 py-3 fw-bold w-100 shadow-sm" onclick="generateAccount('PALMPAY')">
                                    <i class="fa-solid fa-fingerprint me-2"></i> Generate PalmPay Account
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Full-Width 3-Step How It Works Section -->
            <div class="premium-card p-4 p-md-5">
                <h6 class="fw-bold text-muted small text-uppercase mb-4" style="letter-spacing: 0.5px;">How Dedicated Virtual Account Transfers Work</h6>
                <div class="row g-4">
                    <div class="col-12 col-md-4">
                        <div class="p-4 rounded-4 h-100 border border-secondary border-opacity-15 bg-light bg-opacity-5">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fs-3 fw-bold text-primary-custom font-monospace">01</span>
                                <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                                    <i class="fa-regular fa-copy fs-5"></i>
                                </div>
                            </div>
                            <h6 class="fw-bold text-main mb-1">Copy Your Account Number</h6>
                            <p class="text-muted small mb-0">Copy either your dedicated 9PSB or PalmPay account number displayed above.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-4 rounded-4 h-100 border border-secondary border-opacity-15 bg-light bg-opacity-5">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fs-3 fw-bold text-primary-custom font-monospace">02</span>
                                <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                                    <i class="fa-solid fa-paper-plane fs-5"></i>
                                </div>
                            </div>
                            <h6 class="fw-bold text-main mb-1">Transfer Any Amount</h6>
                            <p class="text-muted small mb-0">Open your bank app (OPay, Kuda, GTBank, Zenith) or dial USSD to transfer any amount.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-4 rounded-4 h-100 border border-secondary border-opacity-15 bg-light bg-opacity-5">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fs-3 fw-bold text-success font-monospace">03</span>
                                <div class="p-2 rounded-circle bg-success bg-opacity-10 text-success">
                                    <i class="fa-solid fa-bolt-lightning fs-5"></i>
                                </div>
                            </div>
                            <h6 class="fw-bold text-main mb-1">Automated Wallet Credit</h6>
                            <p class="text-muted small mb-0">Your wallet balance is credited automatically within 30 seconds. Zero manual submission needed.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- TAB 2: KORAPAY CHECKOUT (CARDS & MOBILE MONEY) -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="tab-korapay" role="tabpanel" aria-labelledby="tab-korapay-btn">
            
            <div class="row g-4">
                
                <!-- Left: Deposit Form (Spacious 7 Cols) -->
                <div class="col-12 col-lg-7">
                    <div class="premium-card p-4 p-md-5 h-100" style="border: 1px solid var(--border-color);">
                        <div class="mb-4">
                            <h4 class="fw-bold mb-1 text-main">Pay via Korapay</h4>
                            <p class="text-muted small mb-0">Instant checkout with Mastercard, Visa, Verve, and African Mobile Money.</p>
                        </div>

                        <!-- Error Alert -->
                        <div id="paymentError" class="alert alert-danger d-none mb-4 rounded-3 border-0 bg-danger text-white"></div>

                        @php
                            $ngnCurrency = \App\Models\Currency::where('code', 'NGN')->first() ?? (object)['exchange_rate' => 1450.0];
                            $currentCurrency = \App\Models\Currency::where('code', Session::get('currency_code', auth()->user()->currency_code ?? 'USD'))->first() ?? \App\Models\Currency::where('is_default', true)->first();
                            $ngnRate = $ngnCurrency->exchange_rate;
                            $currentRate = $currentCurrency->exchange_rate;
                        @endphp

                        <form id="korapayDepositForm" onsubmit="initiateKorapayDeposit(event)">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase">Enter Deposit Amount ({{ Session::get('currency_code', auth()->user()->currency_code ?? 'USD') }})</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-transparent text-muted border-end-0" style="border-color: var(--border-color); font-size: 1.25rem;">{{ get_currency_symbol() }}</span>
                                    <input type="number" id="depositAmount" name="amount" class="form-control border-start-0 ps-0" placeholder="5000" min="1" step="any" required style="background-color: transparent; color: var(--text-main); border-color: var(--border-color); font-size: 1.5rem; font-weight: 700;">
                                </div>
                                <div class="form-text mt-2 text-muted" id="conversionHint" style="font-size: 0.9rem;"></div>
                            </div>

                            <!-- Quick Select Chips -->
                            <div class="mb-4">
                                <label class="form-label text-muted x-small fw-bold text-uppercase mb-2">Quick Select Amount</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @php
                                        $quickAmounts = $currentCurrency->code === 'NGN' ? [2000, 5000, 10000, 20000, 50000] : [10, 25, 50, 100, 200];
                                    @endphp
                                    @foreach($quickAmounts as $amt)
                                        <button type="button" class="btn bg-light bg-opacity-10 text-main flex-fill rounded-pill border-0 fw-bold py-2 shadow-sm" onclick="setAmount({{ $amt }})">
                                            {{ get_currency_symbol() }}{{ number_format($amt) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" id="payKorapayButton" class="btn btn-lg bg-primary-custom text-white rounded-pill fw-bold py-3 shadow-lg border-0 w-100 fs-5">
                                    <i class="fa-solid fa-lock me-2"></i> Proceed to Korapay Checkout
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Information & Payment Channels (5 Cols) -->
                <div class="col-12 col-lg-5">
                    <div class="premium-card p-4 p-md-5 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-color);">
                        <div>
                            <h5 class="fw-bold text-main mb-3">Accepted Channels</h5>
                            
                            <div class="d-flex flex-column gap-3 mb-4">
                                <div class="p-3 rounded-4 bg-light bg-opacity-5 border border-secondary border-opacity-10 d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fa-regular fa-credit-card fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small text-main">Debit & Credit Cards</div>
                                        <div class="text-muted x-small">Visa, Mastercard, and Verve supported</div>
                                    </div>
                                </div>

                                <div class="p-3 rounded-4 bg-light bg-opacity-5 border border-secondary border-opacity-10 d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-mobile-screen-button fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small text-main">African Mobile Money</div>
                                        <div class="text-muted x-small">Ghana (MTN, Vodafone), Kenya (M-Pesa), South Africa</div>
                                    </div>
                                </div>

                                <div class="p-3 rounded-4 bg-light bg-opacity-5 border border-secondary border-opacity-10 d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-shield-halved fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small text-main">256-Bit Bank-Grade Security</div>
                                        <div class="text-muted x-small">PCI-DSS Level 1 compliant secure checkout</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 rounded-3 bg-light bg-opacity-10 text-muted x-small text-center">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Funds are credited immediately upon completion of checkout.
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<style>
@keyframes pulseDot {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.nav-pills .nav-link {
    color: var(--text-muted, #94a3b8);
    transition: all 0.25s ease;
    border: 1px solid transparent;
}
.nav-pills .nav-link:hover {
    color: var(--text-main, #ffffff);
    background: rgba(255, 255, 255, 0.05);
}
.nav-pills .nav-link.active {
    background-color: var(--primary-color, #6366f1) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
}
</style>

@push('scripts')
<script>
    const ngnRate = {{ $ngnRate }};
    const currentRate = {{ $currentRate }};
    const currentCode = "{{ $currentCurrency->code }}";

    // 1-Click Copy with Visual Feedback
    function copyAccount(elementId, btn) {
        const text = document.getElementById(elementId).innerText.trim();
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
            btn.classList.remove('btn-primary-custom');
            btn.classList.add('btn-success');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary-custom');
            }, 2000);
        }).catch(err => {
            console.error('Copy failed:', err);
        });
    }

    // Generate Dedicated Virtual Account (9PSB or PalmPay)
    async function generateAccount(bank) {
        let bvn = null;
        const btn = bank === '9PSB' ? document.getElementById('btnGen9psb') : document.getElementById('btnGenPalmpay');

        if (bank === 'PALMPAY') {
            const bvnInput = document.getElementById('bvnInputPalmpay');
            bvn = bvnInput ? bvnInput.value.trim() : '';
            if (bvn.length !== 11 || !/^\d+$/.test(bvn)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Valid BVN Required',
                    text: 'Please enter a valid 11-digit BVN to generate your PalmPay account.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
        }

        const originalBtnHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Generating...';

        try {
            const response = await fetch("{{ route('wallet.virtual-account.generate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ bank: bank, bvn: bvn })
            });

            const result = await response.json();

            if (response.ok && result.success && result.account) {
                Swal.fire({
                    icon: 'success',
                    title: 'Account Generated!',
                    text: result.message || 'Your dedicated virtual account is now ready.',
                    confirmButtonColor: '#6366f1'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Generation Failed',
                    text: result.message || 'Could not generate virtual account. Please try again.',
                    confirmButtonColor: '#6366f1'
                });
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        } catch (error) {
            console.error('Virtual account generation error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'An error occurred while connecting to the provider.',
                confirmButtonColor: '#6366f1'
            });
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    }

    // Korapay Deposit Logic
    const amountInput = document.getElementById('depositAmount');
    const conversionHint = document.getElementById('conversionHint');
    const payKorapayButton = document.getElementById('payKorapayButton');

    function updateConversion() {
        if (!amountInput) return;
        const amount = parseFloat(amountInput.value) || 0;
        
        if (currentCode === 'NGN') {
            const usdEquiv = amount / currentRate;
            conversionHint.innerHTML = `Equivalent: <span class="text-primary-custom fw-bold">$${usdEquiv.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} USD</span>`;
        } else {
            const ngnEquiv = (amount / currentRate) * ngnRate;
            conversionHint.innerHTML = `Naira Equivalent: <span class="text-primary-custom fw-bold">₦${ngnEquiv.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
        }
    }

    function setAmount(amt) {
        if (!amountInput) return;
        amountInput.value = amt;
        updateConversion();
    }

    if (amountInput) {
        amountInput.addEventListener('input', updateConversion);
        updateConversion();
    }

    async function initiateKorapayDeposit(event) {
        event.preventDefault();
        const amount = parseFloat(amountInput.value) || 0;
        const usdAmount = amount / currentRate;

        if (usdAmount < 1) {
            Swal.fire({
                icon: 'info',
                title: 'Minimum Deposit',
                text: 'The minimum deposit amount is $1.00 (or its equivalent in your currency).',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        const originalBtn = payKorapayButton.innerHTML;
        payKorapayButton.disabled = true;
        payKorapayButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Connecting to Korapay...';

        try {
            const response = await fetch("{{ route('wallet.korapay.initialize') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ amount: amount })
            });

            const result = await response.json();

            if (response.ok && (result.authorization_url || result.checkout_url)) {
                window.location.href = result.authorization_url || result.checkout_url;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Initialization Failed',
                    text: result.error || 'Please try again later.',
                    confirmButtonColor: '#6366f1'
                });
                payKorapayButton.disabled = false;
                payKorapayButton.innerHTML = originalBtn;
            }
        } catch (error) {
            console.error('Korapay error:', error);
            Swal.fire({
                icon: 'error',
                title: 'System Error',
                text: 'An error occurred while connecting to Korapay.',
                confirmButtonColor: '#6366f1'
            });
            payKorapayButton.disabled = false;
            payKorapayButton.innerHTML = originalBtn;
        }
    }
</script>
@endpush
@endsection
