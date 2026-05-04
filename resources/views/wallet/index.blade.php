@extends('layouts.app')

@section('header_title', 'Add Funds')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="premium-card p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-custom rounded-circle mb-3" style="width: 64px; height: 64px;">
                    <i class="fa-solid fa-wallet fs-2 text-white"></i>
                </div>
                <h4 class="fw-bold">Fund Your Wallet</h4>
                <p class="text-muted">Current Balance: <strong class="text-primary-custom">₦ {{ number_format(Auth::user()->balance ?? 0, 2) }}</strong></p>
            </div>

            <!-- Error Message Container -->
            <div id="paymentError" class="alert alert-danger d-none mb-4 rounded-3 border-0 bg-danger text-white"></div>

            <form id="depositForm" onsubmit="initiateDeposit(event)">
                @csrf
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Deposit Amount (NGN)</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent text-muted border-end-0" style="border-color: var(--border-color);">₦</span>
                        <input type="number" id="depositAmount" name="amount" class="form-control border-start-0 ps-0" placeholder="5000" min="100" required style="background-color: transparent; color: var(--text-main); border-color: var(--border-color);">
                    </div>
                    <div class="form-text mt-2">Minimum deposit: ₦100</div>
                </div>

                <!-- Quick Select Buttons -->
                <div class="d-flex gap-2 mb-4 justify-content-between">
                    <button type="button" class="btn bg-light bg-opacity-10 text-main flex-fill rounded-pill border-0 fw-bold py-2 shadow-sm" onclick="setAmount(1000)">₦1,000</button>
                    <button type="button" class="btn bg-light bg-opacity-10 text-main flex-fill rounded-pill border-0 fw-bold py-2 shadow-sm" onclick="setAmount(5000)">₦5,000</button>
                    <button type="button" class="btn bg-light bg-opacity-10 text-main flex-fill rounded-pill border-0 fw-bold py-2 shadow-sm" onclick="setAmount(10000)">₦10,000</button>
                </div>

                <div class="d-grid mt-5">
                    <button type="submit" id="payButton" class="btn bg-primary-custom text-white btn-lg fw-bold rounded-pill shadow-lg border-0 py-3">
                        <i class="fa-solid fa-lock me-2"></i> Pay with Paystack
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function setAmount(amount) {
        document.getElementById('depositAmount').value = amount;
    }

    async function initiateDeposit(event) {
        event.preventDefault();
        
        const amount = document.getElementById('depositAmount').value;
        const payButton = document.getElementById('payButton');
        const errorBox = document.getElementById('paymentError');
        const csrfToken = document.querySelector('input[name="_token"]').value;

        // Reset state
        errorBox.classList.add('d-none');
        payButton.disabled = true;
        payButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Initializing...';

        try {
            const response = await fetch('/api/payments/deposit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ amount: amount })
            });

            const data = await response.json();

            if (response.ok && data.authorization_url) {
                // Redirect to Paystack Checkout URL
                window.location.href = data.authorization_url;
            } else {
                throw new Error(data.error || 'Failed to initialize payment.');
            }
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('d-none');
            payButton.disabled = false;
            payButton.innerHTML = '<i class="fa-solid fa-lock me-2"></i> Pay with Paystack';
        }
    }
</script>
@endpush
@endsection
