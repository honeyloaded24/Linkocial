@extends('layouts.app')

@section('header_title', 'Add Funds')

@section('content')
<div class="row justify-content-center px-2">
    <div class="col-12 col-lg-10 col-xl-8">
        <!-- Wallet Header & Balance Summary -->
        <div class="premium-card p-4 p-md-5 mb-4 text-center position-relative overflow-hidden">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 64px; height: 64px; background: rgba(99, 102, 241, 0.15); color: var(--primary-color, #6366f1);">
                <i class="fa-solid fa-wallet fs-2"></i>
            </div>
            <h4 class="fw-bold mb-1">Fund Your Wallet</h4>
            <p class="text-muted small mb-3">Choose your preferred deposit channel. Every deposit powers all services instantly.</p>
            
            <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill bg-light bg-opacity-10 border border-secondary border-opacity-25">
                <span class="text-muted small fw-medium">Available Balance:</span>
                <span class="fs-5 fw-bold text-primary-custom">{{ money(Auth::user()->balance ?? 0) }}</span>
            </div>

            <div class="mt-3">
                <a href="{{ route('wallet.history') }}" class="text-decoration-none small text-muted hover-primary">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> View Transaction History
                </a>
            </div>
        </div>

        <!-- 2-Tab Navigation -->
        <ul class="nav nav-pills nav-fill p-1 rounded-4 mb-4 shadow-sm" style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--border-color);" id="walletTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-3 fw-bold py-3 text-nowrap" id="tab-virtual-btn" data-bs-toggle="tab" data-bs-target="#tab-virtual" type="button" role="tab" aria-controls="tab-virtual" aria-selected="true">
                    <i class="fa-solid fa-building-columns me-2"></i> Virtual Account <span class="badge bg-success bg-opacity-25 text-success ms-1 d-none d-sm-inline-block">Auto-Credit</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-3 fw-bold py-3 text-nowrap" id="tab-korapay-btn" data-bs-toggle="tab" data-bs-target="#tab-korapay" type="button" role="tab" aria-controls="tab-korapay" aria-selected="false">
                    <i class="fa-solid fa-credit-card me-2"></i> Korapay <span class="badge bg-primary bg-opacity-25 text-primary ms-1 d-none d-sm-inline-block">Cards & MoMo</span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="walletTabsContent">
            
            <!-- TAB 1: VIRTUAL ACCOUNTS (BILLSTACK) -->
            <div class="tab-pane fade show active" id="tab-virtual" role="tabpanel" aria-labelledby="tab-virtual-btn">
                
                <!-- Live Pulse Automation Notice -->
                <div class="p-3 mb-4 rounded-4 d-flex align-items-center gap-3" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25);">
                    <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; background: rgba(16, 185, 129, 0.2); color: #10b981;">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold small text-success d-flex align-items-center gap-2">
                            <span>Instant Automated Crediting Active</span>
                            <span class="d-inline-block rounded-circle bg-success" style="width: 8px; height: 8px; animation: pulseDot 1.5s infinite;"></span>
                        </div>
                        <div class="text-muted x-small">
                            Transfer any amount to your personal virtual account from any bank app or USSD (OPay, Kuda, GTBank, Zenith, etc.). Your wallet is credited automatically within seconds.
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- 9PSB Bank Card -->
                    <div class="col-12 col-md-6" id="cardContainer9psb">
                        <div class="premium-card h-100 p-4 d-flex flex-column justify-content-between position-relative" style="border: 1px solid var(--border-color); background: linear-gradient(145deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));">
                            @if($account9psb)
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="badge rounded-pill bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 px-2 py-1 small fw-bold">9PSB Bank</span>
                                        </div>
                                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 small">
                                            <i class="fa-solid fa-circle-dot me-1"></i> Active
                                        </span>
                                    </div>
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Account Number</div>
                                    <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3 bg-light bg-opacity-10 border border-secondary border-opacity-25 mb-3">
                                        <span class="fs-4 fw-bolder font-monospace text-main" id="accNumber9psb">{{ $account9psb->account_number }}</span>
                                        <button type="button" class="btn btn-sm btn-primary-custom px-3 py-1 rounded-pill" onclick="copyAccount('accNumber9psb', this)">
                                            <i class="fa-regular fa-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small">Account Name:</span>
                                        <div class="fw-bold small text-main">{{ $account9psb->account_name }}</div>
                                    </div>
                                    <div class="mb-0">
                                        <span class="text-muted small">Bank Name:</span>
                                        <div class="fw-bold small text-main">{{ $account9psb->bank_name ?: '9Payment Service Bank (9PSB)' }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-top border-secondary border-opacity-10 text-muted x-small">
                                    <i class="fa-solid fa-check text-success me-1"></i> Dedicated to your account. No BVN needed.
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 52px; height: 52px; background: rgba(99, 102, 241, 0.1); color: var(--primary-color, #6366f1);">
                                        <i class="fa-solid fa-building-columns fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold mb-1">9PSB Dedicated Account</h5>
                                    <p class="text-muted small mb-3">Instant automated account number with zero BVN requirement.</p>
                                    <button type="button" id="btnGen9psb" class="btn btn-primary-custom rounded-pill px-4 py-2 fw-bold w-100" onclick="generateAccount('9PSB')">
                                        <i class="fa-solid fa-bolt me-1"></i> Generate 9PSB Account
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- PalmPay Bank Card -->
                    <div class="col-12 col-md-6" id="cardContainerPalmpay">
                        <div class="premium-card h-100 p-4 d-flex flex-column justify-content-between position-relative" style="border: 1px solid var(--border-color); background: linear-gradient(145deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));">
                            @if($accountPalmpay)
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="badge rounded-pill bg-info bg-opacity-15 text-info border border-info border-opacity-25 px-2 py-1 small fw-bold">PalmPay</span>
                                        </div>
                                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 small">
                                            <i class="fa-solid fa-circle-dot me-1"></i> Active
                                        </span>
                                    </div>
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Account Number</div>
                                    <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3 bg-light bg-opacity-10 border border-secondary border-opacity-25 mb-3">
                                        <span class="fs-4 fw-bolder font-monospace text-main" id="accNumberPalmpay">{{ $accountPalmpay->account_number }}</span>
                                        <button type="button" class="btn btn-sm btn-primary-custom px-3 py-1 rounded-pill" onclick="copyAccount('accNumberPalmpay', this)">
                                            <i class="fa-regular fa-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small">Account Name:</span>
                                        <div class="fw-bold small text-main">{{ $accountPalmpay->account_name }}</div>
                                    </div>
                                    <div class="mb-0">
                                        <span class="text-muted small">Bank Name:</span>
                                        <div class="fw-bold small text-main">PalmPay</div>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-top border-secondary border-opacity-10 text-muted x-small">
                                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Verified & Dedicated to your account.
                                </div>
                            @else
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge rounded-pill bg-info bg-opacity-15 text-info border border-info border-opacity-25 px-2 py-1 small fw-bold">PalmPay Account</span>
                                        <span class="text-muted x-small"><i class="fa-solid fa-lock text-success me-1"></i> CBN Regulated</span>
                                    </div>
                                    <h6 class="fw-bold mb-1">Generate PalmPay Account</h6>
                                    <p class="text-muted x-small mb-3">As mandated by CBN regulations, PalmPay accounts require an 11-digit BVN to verify account ownership.</p>
                                    
                                    <div class="mb-3">
                                        <label class="form-label text-muted x-small fw-bold text-uppercase">11-Digit BVN</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent text-muted border-secondary border-opacity-25"><i class="fa-solid fa-id-card"></i></span>
                                            <input type="tel" id="bvnInputPalmpay" maxlength="11" class="form-control border-secondary border-opacity-25 bg-transparent" placeholder="Enter your 11-digit BVN" style="color: var(--text-main);" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        </div>
                                        <div class="text-muted x-small mt-1">
                                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Secure KYC: Your BVN is verified securely and never saved.
                                        </div>
                                    </div>

                                    <button type="button" id="btnGenPalmpay" class="btn btn-primary-custom rounded-pill px-4 py-2 fw-bold w-100" onclick="generateAccount('PALMPAY')">
                                        <i class="fa-solid fa-fingerprint me-1"></i> Generate PalmPay Account
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 3-Step Bank Transfer Guide -->
                <div class="premium-card p-4">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">How Virtual Account Funding Works</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10 text-primary fw-bold" style="width: 36px; height: 36px;">1</div>
                                <div>
                                    <div class="fw-bold small text-main">Copy Account Number</div>
                                    <div class="text-muted x-small">Click copy on your dedicated 9PSB or PalmPay account details above.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10 text-primary fw-bold" style="width: 36px; height: 36px;">2</div>
                                <div>
                                    <div class="fw-bold small text-main">Make Bank Transfer</div>
                                    <div class="text-muted x-small">Transfer any amount from OPay, Kuda, Zenith, GTB, or USSD banking.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10 text-success fw-bold" style="width: 36px; height: 36px;"><i class="fa-solid fa-check"></i></div>
                                <div>
                                    <div class="fw-bold small text-main">Automated Wallet Credit</div>
                                    <div class="text-muted x-small">Your wallet is credited automatically in seconds. No manual confirmation needed.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- TAB 2: KORAPAY (CARDS, MOBILE MONEY & BANK CHECKOUT) -->
            <div class="tab-pane fade" id="tab-korapay" role="tabpanel" aria-labelledby="tab-korapay-btn">
                <div class="premium-card p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <h5 class="fw-bold mb-1">Pay with Korapay</h5>
                        <p class="text-muted small">Instant payment via Debit/Credit Cards, Mobile Money, and African bank checkout.</p>
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
                            <label class="form-label fw-bold text-muted small text-uppercase">Deposit Amount ({{ Session::get('currency_code', auth()->user()->currency_code ?? 'USD') }})</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-transparent text-muted border-end-0" style="border-color: var(--border-color);">{{ get_currency_symbol() }}</span>
                                <input type="number" id="depositAmount" name="amount" class="form-control border-start-0 ps-0" placeholder="5000" min="1" step="any" required style="background-color: transparent; color: var(--text-main); border-color: var(--border-color); font-size: 1.25rem; font-weight: 600;">
                            </div>
                            <div class="form-text mt-2 text-muted small" id="conversionHint"></div>
                        </div>

                        <!-- Quick Select Buttons -->
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            @php
                                $minLocalAmount = ceil(1 * $currentCurrency->exchange_rate);
                                $quickAmounts = $currentCurrency->code === 'NGN' ? [2000, 5000, 10000, 20000] : [10, 25, 50, 100];
                            @endphp
                            @foreach($quickAmounts as $amt)
                                <button type="button" class="btn bg-light bg-opacity-10 text-main flex-fill rounded-pill border-0 fw-bold py-2 shadow-sm" onclick="setAmount({{ $amt }})">
                                    {{ get_currency_symbol() }}{{ number_format($amt) }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <button type="submit" id="payKorapayButton" class="btn btn-lg bg-primary-custom text-white rounded-4 fw-bold py-3 shadow-lg border-0 w-100">
                                <i class="fa-solid fa-lock me-2"></i> Pay with Korapay
                            </button>
                        </div>
                    </form>

                    <!-- Supported Channels -->
                    <div class="mt-4 pt-4 border-top border-secondary border-opacity-15 text-center">
                        <div class="text-muted x-small text-uppercase fw-bold mb-2">Supported Payment Channels</div>
                        <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 text-muted small">
                            <span><i class="fa-brands fa-cc-visa text-primary me-1"></i> Visa</span>
                            <span><i class="fa-brands fa-cc-mastercard text-warning me-1"></i> Mastercard</span>
                            <span><i class="fa-solid fa-credit-card text-success me-1"></i> Verve</span>
                            <span><i class="fa-solid fa-mobile-screen-button text-info me-1"></i> Mobile Money (GH, KE, ZA)</span>
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
.hover-primary:hover {
    color: var(--primary-color, #6366f1) !important;
}
.nav-pills .nav-link {
    color: var(--text-muted, #94a3b8);
    transition: all 0.2s ease;
}
.nav-pills .nav-link.active {
    background-color: var(--primary-color, #6366f1) !important;
    color: #ffffff !important;
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
