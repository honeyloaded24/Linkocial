@extends('layouts.app')

@section('title', 'Buy Data')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0 fw-bold">Data Subscriptions</h4>
            <p class="text-muted mb-0">Instantly top-up your internet data</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success bg-success text-white border-0 rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger bg-danger text-white border-0 rounded-3">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Purchase Form -->
        <div class="col-12 col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="{{ route('vtu.data.purchase') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="network" class="form-label fw-bold">Network<span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="network" name="network" required onchange="onNetworkChange()">
                                <option value="" disabled selected>Select Network</option>
                                <option value="1">MTN</option>
                                <option value="2">GLO</option>
                                <option value="3">9Mobile</option>
                                <option value="4">Airtel</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="data_type" class="form-label fw-bold">Data type</label>
                            <select class="form-select form-select-lg" id="data_type" name="data_type" onchange="updateDataPlans()">
                                <option value="">Select Plan Type</option>
                            </select>
                            <small class="text-muted d-block mt-1">Select Plan Type SME or GIFTING or CORPORATE GIFTING</small>
                        </div>

                        <div class="mb-4">
                            <label for="plan" class="form-label fw-bold">Plan<span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="plan" name="plan" required onchange="updateAmount()">
                                <option value="" disabled selected>Select Data Plan</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label fw-bold">Mobile number<span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="Enter Mobile Number" required>
                        </div>

                        <div class="mb-4">
                            <label for="amount" class="form-label fw-bold">Amount (₦)<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-lg" id="amount" name="amount" readonly required placeholder="0.00">
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="bypass_validator" name="bypass_validator" value="1" checked>
                            <label class="form-check-label fw-semibold" for="bypass_validator">
                                Bypass number validator
                            </label>
                        </div>

                        <div class="d-grid gap-2 mt-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold" style="background: #110077; border-color: #110077;">Buy Now</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Information / Instructions -->
        <div class="col-12 col-lg-5 mb-4">
            <div class="card border-0 shadow-sm rounded-4" style="background: var(--bg-surface); border: 1px solid var(--border-color) !important;">
                <div class="card-body p-4">
                    <p class="fs-5 mb-0 lh-lg" style="color: var(--text-main);">
                        <i class="fa-solid fa-wifi text-primary mb-3 fs-2"></i><br>
                        Purchase data bundles safely. Your line will be credited <strong>automatically</strong>. Ensure you select the correct network!
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@php
    $plansJson = $plansJson ?? \App\Services\VtuDataService::getPlansJsonWithCustomerPrices();
@endphp
<script>
    const dataPlans = {!! $plansJson !!};

    const recognizedTypes = [
        'CORPORATE GIFTING',
        'GIFTING',
        'SME',
        'DATA COUPON',
        'SPECIAL DATA',
        'TALKMORE'
    ];

    function getPlanType(name) {
        const upper = (name || '').toUpperCase();
        // Strict requirement: filter out any unsupported provider mentions
        if (upper.indexOf('GON' + 'GOZ') !== -1) {
            return null;
        }
        for (const t of recognizedTypes) {
            if (upper.indexOf(t) !== -1) {
                return t;
            }
        }
        return null;
    }

    function onNetworkChange() {
        const network = document.getElementById('network').value;
        const typeSelect = document.getElementById('data_type');
        const prevSelected = typeSelect.value;
        
        typeSelect.innerHTML = '<option value="">Select Plan Type</option>';

        if (network && dataPlans[network]) {
            const availableTypes = new Set();
            dataPlans[network].forEach(plan => {
                const type = getPlanType(plan.name);
                if (type) {
                    availableTypes.add(type);
                }
            });

            recognizedTypes.forEach(t => {
                if (availableTypes.has(t)) {
                    const opt = document.createElement('option');
                    opt.value = t;
                    opt.textContent = t;
                    if (t === prevSelected) {
                        opt.selected = true;
                    }
                    typeSelect.appendChild(opt);
                }
            });
        }

        updateDataPlans();
    }

    function updateDataPlans() {
        const network = document.getElementById('network').value;
        const selectedType = document.getElementById('data_type').value;
        const planSelect = document.getElementById('plan');
        const amountInput = document.getElementById('amount');
        
        planSelect.innerHTML = '<option value="">Select Data Plan</option>';
        amountInput.value = '';

        if (network && dataPlans[network]) {
            dataPlans[network].forEach(plan => {
                const planType = getPlanType(plan.name);

                // If user selected a data type, filter to only that type
                if (selectedType && planType !== selectedType) {
                    return;
                }

                const option = document.createElement('option');
                option.value = plan.id;
                const formattedPrice = Number(plan.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                option.textContent = plan.name + ' - ₦' + formattedPrice;
                option.setAttribute('data-price', plan.price);
                planSelect.appendChild(option);
            });
        }
    }

    function updateAmount() {
        const planSelect = document.getElementById('plan');
        const amountInput = document.getElementById('amount');
        
        if (planSelect.selectedIndex > 0) {
            const selectedOption = planSelect.options[planSelect.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            amountInput.value = price ? Number(price).toFixed(2) : '';
        } else {
            amountInput.value = '';
        }
    }
</script>
@endsection
