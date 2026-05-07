@extends('layouts.app')

@section('header_title', 'Add Funds')

@section('content')
<div class="row justify-content-center px-2">
    <div class="col-12 col-md-10 col-lg-8 col-xl-6">
        <div class="premium-card p-3 p-md-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-custom rounded-circle mb-3" style="width: 64px; height: 64px;">
                    <i class="fa-solid fa-wallet fs-2 text-white"></i>
                </div>
                <h4 class="fw-bold">Fund Your Wallet</h4>
                <p class="text-muted">Current Balance: <strong class="text-primary-custom">{{ money(Auth::user()->balance ?? 0) }}</strong></p>
            </div>

            <!-- Error Message Container -->
            <div id="paymentError" class="alert alert-danger d-none mb-4 rounded-3 border-0 bg-danger text-white"></div>

            <form id="depositForm" onsubmit="initiateDeposit(event)">
                @csrf
                
                <!-- Gateway Selection Dropdown -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Payment Method</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent text-muted border-end-0" style="border-color: var(--border-color);"><i class="fa-solid fa-credit-card"></i></span>
                        <select id="selectedGateway" name="gateway" class="form-select border-start-0 ps-0" onchange="updateConversion()" required style="background-color: transparent; color: var(--text-main); border-color: var(--border-color); font-size: 0.95rem;">
                            <option value="paystack">🇳🇬 Paystack [NGN Card & Transfer]</option>
                            <option value="korapay" selected>🌍 Kora [Local Africa: GHS, KES, NGN, ZAR]</option>
                            <option value="heleket">💳 Crypto [Global: BTC, USDT, ETH]</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Deposit Amount ({{ Session::get('currency_code', auth()->user()->currency_code ?? 'USD') }})</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent text-muted border-end-0" style="border-color: var(--border-color);">{{ get_currency_symbol() }}</span>
                        <input type="number" id="depositAmount" name="amount" class="form-control border-start-0 ps-0" placeholder="10" min="1" required style="background-color: transparent; color: var(--text-main); border-color: var(--border-color);">
                    </div>
                    @php
                        $ngnCurrency = \App\Models\Currency::where('code', 'NGN')->first() ?? (object)['exchange_rate' => 1450.0];
                        $currentCurrency = \App\Models\Currency::where('code', Session::get('currency_code', auth()->user()->currency_code ?? 'USD'))->first() ?? \App\Models\Currency::where('is_default', true)->first();
                        $ngnRate = $ngnCurrency->exchange_rate;
                        $currentRate = $currentCurrency->exchange_rate;
                    @endphp
                    <div class="form-text mt-2" id="conversionHint">
                        @if($currentCurrency->code !== 'NGN')
                            Paystack charge: <span class="fw-bold text-primary-custom" id="ngnEquivalent">₦0.00</span>
                        @endif
                    </div>
                </div>

                <!-- Quick Select Buttons -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @php
                        $amounts = $currentCurrency->code === 'NGN' ? [1000, 5000, 10000] : [10, 50, 100];
                    @endphp
                    @foreach($amounts as $amt)
                        <button type="button" class="btn bg-light bg-opacity-10 text-main flex-fill rounded-pill border-0 fw-bold py-2 shadow-sm" onclick="setAmount({{ $amt }})">{{ money($amt) }}</button>
                    @endforeach
                </div>
                
                <div class="mt-5">
                    <button type="submit" id="payButton" class="btn btn-lg bg-primary-custom text-white rounded-4 fw-bold py-3 shadow-lg border-0 w-100">
                        <i class="fa-solid fa-lock me-2"></i> Continue to Payment
                    </button>
                </div>
            </form>

            <hr class="my-5 opacity-10">

            <div class="payment-instructions">
                <h6 class="fw-bold text-muted small text-uppercase mb-4">Payment Guide & Scenarios</h6>
                
                <div class="d-flex gap-3 mb-4">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold small">Paystack (Recommended for Nigeria)</div>
                        <p class="text-muted small mb-0">Use this if you are in **Nigeria**. Best for Bank Transfers, USSD, Naira Cards, and Fintechs like Opay, Kuda, or Palmpay.</p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-4">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold small">Kora (Best for African Localization)</div>
                        <p class="text-muted small mb-0">Use this for **Ghana, Kenya, South Africa,** and **Nigeria**. Supports **Mobile Money** (MTN, Vodafone) and local African cards.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-brands fa-bitcoin"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold small">Heleket Crypto (Global & Private)</div>
                        <p class="text-muted small mb-0">Use this for **Global** payments or if you prefer privacy. Supports BTC, USDT, and ETH with instant wallet crediting.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const amountInput = document.getElementById('depositAmount');
    const ngnEquivalent = document.getElementById('ngnEquivalent');
    const payButton = document.getElementById('payButton');
    const selectedGatewayInput = document.getElementById('selectedGateway');
    
    const ngnRate = {{ $ngnRate }};
    const currentRate = {{ $currentRate }};
    const currentCode = "{{ $currentCurrency->code }}";

    function updateConversion() {
        const amount = parseFloat(amountInput.value) || 0;
        const gateway = selectedGatewayInput.value;
        
        let displayAmount = "";
        let btnIcon = "fa-lock";

        if (gateway === 'paystack') {
            const paystackNgn = currentCode === 'NGN' ? amount : (amount / currentRate) * ngnRate;
            displayAmount = `₦${paystackNgn.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}`;
        } else if (gateway === 'korapay') {
            const supportedKora = ['NGN', 'GHS', 'KES', 'ZAR'];
            const symbols = {'NGN': '₦', 'GHS': 'GH₵', 'KES': 'KSh', 'ZAR': 'R'};
            let koraAmount = 0;
            let koraSymbol = '₦';
            
            if (supportedKora.includes(currentCode)) {
                koraAmount = amount;
                koraSymbol = symbols[currentCode] || '₦';
            } else {
                koraAmount = (amount / currentRate) * ngnRate;
                koraSymbol = '₦';
            }
            displayAmount = `${koraSymbol}${koraAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            btnIcon = "fa-building-columns";
        } else if (gateway === 'heleket') {
            const heleketUsd = currentCode === 'USD' ? amount : (amount / currentRate);
            displayAmount = `$${heleketUsd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            btnIcon = "fa-bitcoin";
        }

        payButton.innerHTML = `<i class="fa-solid ${btnIcon} me-2"></i> Pay ${displayAmount} Now`;

        // Update the hint
        if (ngnEquivalent) {
            const ngnAmount = currentCode === 'NGN' ? amount : (amount / currentRate) * ngnRate;
            const usdAmount = currentCode === 'USD' ? amount : (amount / currentRate);
            
            if (currentCode === 'NGN') {
                ngnEquivalent.innerHTML = `Crypto Charge: <span class="text-warning">$${usdAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
            } else if (currentCode !== 'USD') {
                ngnEquivalent.innerHTML = `Naira (NGN): <span class="text-primary-custom">₦${ngnAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span> | Crypto (USD): <span class="text-warning">$${usdAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
            }
        }
    }

    function setAmount(amt) {
        amountInput.value = amt;
        updateConversion();
    }

    amountInput.addEventListener('input', updateConversion);
    // Initialize on load
    updateConversion();

    async function initiateDeposit(event) {
        event.preventDefault();
        
        const gateway = selectedGatewayInput.value;
        const amount = amountInput.value;
        
        let url = '/api/payments/deposit';
        if (gateway === 'korapay') url = '/api/payments/korapay';
        if (gateway === 'heleket') url = '/api/payments/heleket';
        
        const originalContent = payButton.innerHTML;
        payButton.disabled = true;
        payButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing...';

        try {
            const response = await fetch(url, {
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
                    background: '#1a1d21',
                    color: '#ffffff'
                });
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        } catch (error) {
            console.error('Payment error:', error);
            Swal.fire({
                icon: 'error',
                title: 'System Error',
                text: 'An error occurred while connecting to the payment gateway.',
                background: '#1a1d21',
                color: '#ffffff'
            });
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    }
</script>
@endpush
@endsection
