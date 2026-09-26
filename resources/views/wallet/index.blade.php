@extends('layouts.app')

@section('header_title', 'Add Funds')

@section('content')
<div class="container-fluid px-3 px-md-4 py-2">
    
    <!-- Top Header & Tabs -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-main">Fund Wallet</h4>
            <p class="text-muted small mb-0">Select your preferred payment channel to add funds.</p>
        </div>
        <div>
            <ul class="nav nav-pills p-1 rounded-pill shadow-sm" style="background: var(--bg-surface); border: 1px solid var(--border-color);" id="walletTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-bold py-2 px-3 small text-nowrap" id="tab-virtual-btn" data-bs-toggle="tab" data-bs-target="#tab-virtual" type="button" role="tab" aria-controls="tab-virtual" aria-selected="true">
                        <i class="fa-solid fa-building-columns me-1"></i> Virtual Account
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold py-2 px-3 small text-nowrap" id="tab-korapay-btn" data-bs-toggle="tab" data-bs-target="#tab-korapay" type="button" role="tab" aria-controls="tab-korapay" aria-selected="false">
                        <i class="fa-solid fa-credit-card me-1"></i> Korapay
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content" id="walletTabsContent">
        
        <!-- ========================================== -->
        <!-- TAB 1: VIRTUAL ACCOUNTS (9PSB & PALMPAY) -->
        <!-- ========================================== -->
        <div class="tab-pane fade show active" id="tab-virtual" role="tabpanel" aria-labelledby="tab-virtual-btn">
            
            <!-- Compact Alert -->
            <div class="p-2 px-3 mb-3 rounded-3 d-flex align-items-center gap-2 small text-success" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-bolt-lightning text-success"></i>
                <span><strong>Instant Auto-Credit:</strong> Transfers to either account below are credited to your balance automatically within seconds.</span>
            </div>

            <!-- 2 Compact Bank Cards -->
            <div class="row g-3">
                
                <!-- 9PSB Bank Card -->
                <div class="col-12 col-md-6" id="cardContainer9psb">
                    <div class="premium-card p-3 p-md-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-color);">
                        @if($account9psb)
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fa-solid fa-building-columns"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-main">9PSB Bank</h6>
                                    </div>
                                    <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 x-small">
                                        <i class="fa-solid fa-circle-dot me-1"></i> Active
                                    </span>
                                </div>

                                <div class="p-3 rounded-3 bg-light bg-opacity-10 border border-secondary border-opacity-25 mb-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted x-small text-uppercase fw-bold">Account Number</div>
                                        <div class="fs-4 fw-bolder font-monospace text-main" id="accNumber9psb" style="letter-spacing: 1px;">{{ $account9psb->account_number }}</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary-custom px-3 py-1 rounded-pill fw-bold" onclick="copyAccount('accNumber9psb', this)">
                                        <i class="fa-regular fa-copy me-1"></i> Copy
                                    </button>
                                </div>

                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Account Name: <strong class="text-main">{{ $account9psb->account_name }}</strong></span>
                                    <span>Bank: <strong class="text-main">9PSB</strong></span>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-3 my-auto">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width: 44px; height: 44px; background: rgba(99, 102, 241, 0.1); color: var(--primary-color, #6366f1);">
                                    <i class="fa-solid fa-building-columns fs-5"></i>
                                </div>
                                <h6 class="fw-bold mb-1">9PSB Account</h6>
                                <p class="text-muted x-small mb-3">No BVN required</p>
                                <button type="button" id="btnGen9psb" class="btn btn-sm btn-primary-custom rounded-pill px-4 py-2 fw-bold" onclick="generateAccount('9PSB')">
                                    <i class="fa-solid fa-bolt me-1"></i> Generate Account
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- PalmPay Bank Card -->
                <div class="col-12 col-md-6" id="cardContainerPalmpay">
                    <div class="premium-card p-3 p-md-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid var(--border-color);">
                        @if($accountPalmpay)
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-main">PalmPay</h6>
                                    </div>
                                    <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 x-small">
                                        <i class="fa-solid fa-circle-dot me-1"></i> Active
                                    </span>
                                </div>

                                <div class="p-3 rounded-3 bg-light bg-opacity-10 border border-secondary border-opacity-25 mb-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted x-small text-uppercase fw-bold">Account Number</div>
                                        <div class="fs-4 fw-bolder font-monospace text-main" id="accNumberPalmpay" style="letter-spacing: 1px;">{{ $accountPalmpay->account_number }}</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary-custom px-3 py-1 rounded-pill fw-bold" onclick="copyAccount('accNumberPalmpay', this)">
                                        <i class="fa-regular fa-copy me-1"></i> Copy
                                    </button>
                                </div>

                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Account Name: <strong class="text-main">{{ $accountPalmpay->account_name }}</strong></span>
                                    <span>Bank: <strong class="text-main">PalmPay</strong></span>
                                </div>
                            </div>
                        @else
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-main">PalmPay Account</h6>
                                    </div>
                                    <span class="badge bg-secondary bg-opacity-15 text-muted px-2 py-1 x-small">BVN Required</span>
                                </div>

                                <div class="mb-3 mt-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent text-muted border-secondary border-opacity-25 py-1"><i class="fa-solid fa-id-card small"></i></span>
                                        <input type="tel" id="bvnInputPalmpay" maxlength="11" class="form-control form-control-sm border-secondary border-opacity-25 bg-transparent" placeholder="Enter 11-digit BVN" style="color: var(--text-main);" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>

                                <button type="button" id="btnGenPalmpay" class="btn btn-sm btn-primary-custom rounded-pill px-4 py-2 fw-bold w-100" onclick="generateAccount('PALMPAY')">
                                    <i class="fa-solid fa-fingerprint me-1"></i> Generate Account
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================== -->
        <!-- TAB 2: KORAPAY CHECKOUT -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="tab-korapay" role="tabpanel" aria-labelledby="tab-korapay-btn">
            <div class="premium-card p-3 p-md-4" style="border: 1px solid var(--border-color); max-width: 600px;">
                
                <h6 class="fw-bold text-main mb-3">Pay with Card or Mobile Money</h6>

                <!-- Error Alert -->
                <div id="paymentError" class="alert alert-danger d-none mb-3 rounded-3 border-0 bg-danger text-white py-2 small"></div>

                @php
                    $ngnCurrency = \App\Models\Currency::where('code', 'NGN')->first() ?? (object)['exchange_rate' => 1450.0];
                    $currentCurrency = \App\Models\Currency::where('code', Session::get('currency_code', auth()->user()->currency_code ?? 'USD'))->first() ?? \App\Models\Currency::where('is_default', true)->first();
                    $ngnRate = $ngnCurrency->exchange_rate;
                    $currentRate = $currentCurrency->exchange_rate;
                @endphp

                <form id="korapayDepositForm" onsubmit="initiateKorapayDeposit(event)">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label text-muted x-small fw-bold text-uppercase">Amount ({{ Session::get('currency_code', auth()->user()->currency_code ?? 'USD') }})</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent text-muted border-end-0" style="border-color: var(--border-color);">{{ get_currency_symbol() }}</span>
                            <input type="number" id="depositAmount" name="amount" class="form-control border-start-0 ps-0 fw-bold" placeholder="5000" min="1" step="any" required style="background-color: transparent; color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="form-text mt-1 text-muted x-small" id="conversionHint"></div>
                    </div>

                    <!-- Quick Select Chips -->
                    <div class="mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                $quickAmounts = $currentCurrency->code === 'NGN' ? [2000, 5000, 10000, 20000] : [10, 25, 50, 100];
                            @endphp
                            @foreach($quickAmounts as $amt)
                                <button type="button" class="btn btn-sm bg-light bg-opacity-10 text-main flex-fill rounded-pill border-0 fw-bold py-1" onclick="setAmount({{ $amt }})">
                                    {{ get_currency_symbol() }}{{ number_format($amt) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" id="payKorapayButton" class="btn btn-primary-custom text-white rounded-pill fw-bold py-2 w-100">
                        <i class="fa-solid fa-lock me-1"></i> Continue to Payment
                    </button>
                </form>

                <div class="mt-3 pt-2 border-top border-secondary border-opacity-10 text-center text-muted x-small">
                    <span><i class="fa-brands fa-cc-visa me-1"></i> Visa</span> &bull; 
                    <span><i class="fa-brands fa-cc-mastercard me-1"></i> Mastercard</span> &bull; 
                    <span><i class="fa-solid fa-credit-card me-1"></i> Verve</span> &bull; 
                    <span><i class="fa-solid fa-mobile-screen-button me-1"></i> Mobile Money</span>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
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

    // 1-Click Copy
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
        });
    }

    // Generate Virtual Account
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
                    text: 'Please enter a valid 11-digit BVN.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
        }

        const originalBtnHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Generating...';

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
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Generation Failed',
                    text: result.message || 'Could not generate virtual account.',
                    confirmButtonColor: '#6366f1'
                });
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not connect to server.',
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
            conversionHint.innerHTML = `Equivalent: <span class="text-primary-custom fw-bold">$${usdEquiv.toFixed(2)} USD</span>`;
        } else {
            const ngnEquiv = (amount / currentRate) * ngnRate;
            conversionHint.innerHTML = `Naira Equivalent: <span class="text-primary-custom fw-bold">₦${ngnEquiv.toFixed(2)}</span>`;
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
                text: 'The minimum deposit amount is $1.00 (or equivalent).',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        const originalBtn = payKorapayButton.innerHTML;
        payKorapayButton.disabled = true;
        payKorapayButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';

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
                    title: 'Failed',
                    text: result.error || 'Please try again later.',
                    confirmButtonColor: '#6366f1'
                });
                payKorapayButton.disabled = false;
                payKorapayButton.innerHTML = originalBtn;
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
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
