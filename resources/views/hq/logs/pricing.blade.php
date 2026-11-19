@extends('layouts.app')

@section('header_title', 'HQ: Logs Marketplace Pricing & Markup Settings')

@section('content')
<div class="container-fluid">
    <!-- Header / Navigation Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('hq.logs.index') }}" class="btn btn-light rounded-pill px-3 shadow-sm border">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Logs
            </a>
            <div>
                <h4 class="fw-bold mb-0">Logs Marketplace Pricing & Markup Settings</h4>
                <p class="text-muted small mb-0">
                    Configure automated wholesale currency conversion, profit markups, and rounding policies for digital accounts and external API logs.
                </p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill font-monospace">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Logs Marketplace
            </span>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="alert alert-success bg-success text-white border-0 shadow-sm rounded-3 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger bg-danger text-white border-0 shadow-sm rounded-3 mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger bg-danger text-white border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Key Metrics & Status Bar -->
    <div class="row g-3 mb-4">
        <!-- Metric 1: Active Fix Rate -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted text-uppercase fw-bold">Active Fix Rate</span>
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2">Logs Isolated</span>
                </div>
                <div class="fs-4 fw-bold text-warning font-monospace" id="metricFixRateDisplay">
                    $1 = ₦{{ number_format($settings->getFixRate(), 2) }}
                </div>
                <small class="text-muted d-block mt-1">Wholesale exchange rate for API logs</small>
            </div>
        </div>

        <!-- Metric 2: Active Markup -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 border-primary">
                <span class="small text-muted text-uppercase fw-bold d-block mb-1">Active Markup</span>
                <div class="fs-4 fw-bold text-primary font-monospace">
                    @if($settings->getPricingMode() === 'percentage')
                        +{{ $settings->getPercentageMarkup() }}%
                    @elseif($settings->getPricingMode() === 'fixed')
                        +₦{{ number_format($settings->getFixedMarkup(), 2) }}
                    @else
                        +{{ $settings->getPercentageMarkup() }}% & +₦{{ number_format($settings->getFixedMarkup(), 2) }}
                    @endif
                </div>
                <small class="text-muted d-block mt-1 text-capitalize">{{ str_replace('_', ' ', $settings->getPricingMode()) }} Mode</small>
            </div>
        </div>

        <!-- Metric 3: Provider Currency & Mode -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 border-info">
                <span class="small text-muted text-uppercase fw-bold d-block mb-1">Provider Currency</span>
                <div class="fs-4 fw-bold text-info font-monospace">
                    {{ $settings->getCurrency() }} ({{ $settings->getCurrency() === 'USD' ? '$' : '₦' }})
                </div>
                <small class="text-muted d-block mt-1">
                    {{ $settings->getCurrency() === 'NGN' ? 'Wholesale Denominated in NGN' : 'Wholesale USD Converted to NGN' }}
                </small>
            </div>
        </div>

        <!-- Metric 4: Inventory Split -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 border-success">
                <span class="small text-muted text-uppercase fw-bold d-block mb-1">Catalog Counts</span>
                <div class="fs-4 fw-bold text-success font-monospace">
                    {{ number_format($counts['all'] ?? 0) }} <span class="fs-6 text-muted">Total</span>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ number_format($counts['api'] ?? 0) }} API | {{ number_format($counts['local'] ?? 0) }} Local
                </small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Configuration Form Column -->
        <div class="col-lg-7">
            <div class="premium-card p-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-boxes-stacked fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Logs Marketplace Pricing Rules</h5>
                            <small class="text-muted">Configure currency conversion, profit markups, and rounding policies</small>
                        </div>
                    </div>
                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill font-monospace" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-shield-halved me-1"></i> Logs-Isolated
                    </span>
                </div>

                <form action="{{ route('hq.logs.pricing.update') }}" method="POST" id="pricingConfigForm">
                    @csrf

                    <!-- Field 1: Active Fix Rate (FX Rate) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Active Fix Rate (1 USD in NGN) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg shadow-sm rounded-3">
                            <span class="input-group-text bg-light border-secondary text-muted fw-bold">$1 USD =</span>
                            <span class="input-group-text bg-light border-secondary text-success fw-bold">₦</span>
                            <input type="number" 
                                   step="any" 
                                   min="0.0001" 
                                   id="fixRateInput" 
                                   name="fix_rate" 
                                   class="form-control form-control-lg border-secondary fw-bold text-warning font-monospace" 
                                   value="{{ old('fix_rate', $settings->getFixRate()) }}" 
                                   required>
                        </div>
                        <div class="form-text small">Used when converting wholesale prices from foreign currency provider feeds into NGN.</div>
                    </div>

                    <!-- Row 2: Provider Currency & Pricing Mode -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Provider Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="currencySelect" class="form-select form-select-lg" required>
                                <option value="NGN" {{ old('currency', $settings->getCurrency()) === 'NGN' ? 'selected' : '' }}>NGN (₦ - Nigerian Naira)</option>
                                <option value="USD" {{ old('currency', $settings->getCurrency()) === 'USD' ? 'selected' : '' }}>USD ($ - US Dollar)</option>
                            </select>
                            <div class="form-text small">Specify the base wholesale currency for imported API products.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Pricing Mode <span class="text-danger">*</span></label>
                            <select name="pricing_mode" id="pricingModeSelect" class="form-select form-select-lg" required>
                                <option value="percentage" {{ old('pricing_mode', $settings->getPricingMode()) === 'percentage' ? 'selected' : '' }}>
                                    Percentage Markup (Recommended)
                                </option>
                                <option value="fixed" {{ old('pricing_mode', $settings->getPricingMode()) === 'fixed' ? 'selected' : '' }}>
                                    Fixed Markup (₦ Flat Fee)
                                </option>
                                <option value="combined" {{ old('pricing_mode', $settings->getPricingMode()) === 'combined' ? 'selected' : '' }}>
                                    Combined (Percentage + Fixed Flat)
                                </option>
                            </select>
                            <div class="form-text small">Strategy for computing final retail prices on API products.</div>
                        </div>
                    </div>

                    <!-- Row 3: Markup Inputs -->
                    <div class="row mb-3">
                        <!-- Percentage Markup -->
                        <div class="col-md-6" id="percentageMarkupGroup">
                            <label class="form-label fw-bold small text-muted text-uppercase">Percentage Markup (%) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3">
                                <span class="input-group-text bg-light border-secondary text-muted fw-bold">+</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       max="5000" 
                                       id="percentageMarkupInput" 
                                       name="percentage_markup" 
                                       class="form-control form-control-lg border-secondary fw-bold text-primary-custom" 
                                       value="{{ old('percentage_markup', $settings->getPercentageMarkup()) }}" 
                                       required>
                                <span class="input-group-text bg-light border-secondary text-muted fw-bold">%</span>
                            </div>
                            <div class="form-text small">Percentage added to converted wholesale cost (e.g. <code>20%</code>).</div>
                        </div>

                        <!-- Fixed Markup -->
                        <div class="col-md-6" id="fixedMarkupGroup">
                            <label class="form-label fw-bold small text-muted text-uppercase">Fixed Markup (₦) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3">
                                <span class="input-group-text bg-light border-secondary text-success fw-bold">₦</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       id="fixedMarkupInput" 
                                       name="fixed_markup" 
                                       class="form-control form-control-lg border-secondary fw-bold" 
                                       value="{{ old('fixed_markup', $settings->getFixedMarkup()) }}" 
                                       required>
                            </div>
                            <div class="form-text small">Flat Naira fee added per digital account item.</div>
                        </div>
                    </div>

                    <!-- Row 4: Safeguards -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Minimum Selling Price (₦)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">₦</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       id="minSellingPriceInput" 
                                       name="min_selling_price" 
                                       class="form-control" 
                                       placeholder="Optional (e.g. 500)" 
                                       value="{{ old('min_selling_price', $settings->getMinSellingPrice()) }}">
                            </div>
                            <div class="form-text small">Hard price floor for any log product in marketplace.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Minimum Profit Margin (₦)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">₦</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       id="minProfitInput" 
                                       name="min_profit" 
                                       class="form-control" 
                                       placeholder="Optional (e.g. 50)" 
                                       value="{{ old('min_profit', $settings->getMinProfit()) }}">
                            </div>
                            <div class="form-text small">Minimum profit margin earned above wholesale cost.</div>
                        </div>
                    </div>

                    <!-- Row 5: Rounding Policy -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Rounding Policy <span class="text-danger">*</span></label>
                        <select name="rounding_rule" id="roundingRuleSelect" class="form-select" required>
                            <option value="2_decimal" {{ old('rounding_rule', $settings->getRoundingRule()) === '2_decimal' ? 'selected' : '' }}>
                                Standard 2 Decimals (e.g. ₦1,250.50) — High Precision
                            </option>
                            <option value="whole_naira" {{ old('rounding_rule', $settings->getRoundingRule()) === 'whole_naira' ? 'selected' : '' }}>
                                Round Up to Whole Naira (e.g. ₦1,251.00) — Clean Pricing
                            </option>
                            <option value="nearest_10" {{ old('rounding_rule', $settings->getRoundingRule()) === 'nearest_10' ? 'selected' : '' }}>
                                Round Up to Nearest ₦10 (e.g. ₦1,260.00) — Commercial Standard
                            </option>
                        </select>
                        <div class="form-text small">Determines how customer selling prices in NGN are presented.</div>
                    </div>

                    <!-- Protection Notice -->
                    <div class="alert alert-info bg-warning bg-opacity-10 border-0 rounded-4 p-3 mb-4 small text-main">
                        <div class="d-flex gap-2">
                            <i class="fa-solid fa-shield-halved text-warning fs-5 mt-1"></i>
                            <div>
                                <strong class="d-block text-main mb-1">Local Product Protection Guarantee:</strong>
                                These rules only apply to wholesale pricing on <strong>external API products</strong>. <strong>Local products</strong> remain strictly protected and keep their manually configured retail prices without unwanted modification or bulk alteration.
                            </div>
                        </div>
                    </div>

                    <!-- Save Action -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-warning px-4 py-3 fw-bold rounded-pill shadow-sm flex-grow-1">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save Logs Marketplace Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Math & Interactive Verification Column -->
        <div class="col-lg-5">
            <div class="premium-card p-4 shadow-sm h-100">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-calculator text-warning me-2"></i> Live Pricing Simulator</h5>
                <p class="text-muted small mb-3">
                    Calculated in real-time using active fix rate (<strong class="text-warning font-monospace" id="simulatorRateLabel">₦{{ number_format($settings->getFixRate(), 2) }}</strong>) and current form inputs:
                </p>

                <!-- Example Calculation Matrix -->
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th>Wholesale Cost</th>
                                <th>Cost (NGN)</th>
                                <th class="text-end">Selling Price</th>
                                <th class="text-end">Profit</th>
                            </tr>
                        </thead>
                        <tbody id="exampleMatrixBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Custom Test Calculator -->
                <div class="p-3 bg-light rounded-4 border mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">Test Any Logs Wholesale Rate</label>
                    <div class="row g-2 align-items-center">
                        <div class="col-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" id="testCurrencySymbol">₦</span>
                                <input type="number" id="testRateInput" class="form-control font-monospace" value="1200.00" step="10" min="0">
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₦</span>
                                <input type="text" id="testNgnOutput" class="form-control fw-bold text-success font-monospace" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" id="testBreakdownText">
                        Breakdown: Cost ₦1,200.00 + Profit = Selling Price
                    </div>
                </div>

                <!-- Sync Controls Card -->
                <div class="p-4 rounded-4 bg-light bg-opacity-75 border">
                    <h6 class="fw-bold mb-2"><i class="fa-solid fa-rotate text-warning me-2"></i> Marketplace Controls</h6>
                    <p class="text-muted small mb-3">
                        Manage catalog products or trigger synchronization with Fadded provider API.
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('hq.logs.index') }}" class="btn btn-outline-warning rounded-pill w-100 fw-bold py-2 shadow-sm text-start text-dark">
                            <i class="fa-solid fa-boxes-stacked me-2"></i> Manage Marketplace Products
                        </a>
                        <form action="{{ route('hq.logs.sync') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary-custom rounded-pill w-100 fw-bold py-2 shadow-sm text-start">
                                <i class="fa-solid fa-bolt me-2"></i> Sync API Products from Fadded Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const fixRateInput = document.getElementById('fixRateInput');
    const currencySelect = document.getElementById('currencySelect');
    const modeSelect = document.getElementById('pricingModeSelect');
    const pctInput = document.getElementById('percentageMarkupInput');
    const fixedInput = document.getElementById('fixedMarkupInput');
    const minProfitInput = document.getElementById('minProfitInput');
    const minSellingInput = document.getElementById('minSellingPriceInput');
    const roundingSelect = document.getElementById('roundingRuleSelect');

    const matrixBody = document.getElementById('exampleMatrixBody');
    const testRateInput = document.getElementById('testRateInput');
    const testNgnOutput = document.getElementById('testNgnOutput');
    const testBreakdownText = document.getElementById('testBreakdownText');
    const testCurrencySymbol = document.getElementById('testCurrencySymbol');
    const simulatorRateLabel = document.getElementById('simulatorRateLabel');

    const sampleNgnRates = [500.0, 1000.0, 2000.0, 5000.0, 10000.0];
    const sampleUsdRates = [1.00, 2.50, 5.00, 10.00, 20.00];

    function calculatePrice(rawRate) {
        const isUsd = currencySelect ? (currencySelect.value === 'USD') : false;
        const fx = fixRateInput ? (parseFloat(fixRateInput.value) || 1500) : 1500;
        const costNgn = isUsd ? (rawRate * fx) : rawRate;

        const mode = modeSelect ? modeSelect.value : 'percentage';
        const pct = pctInput ? (parseFloat(pctInput.value) || 0) : 0;
        const fixed = fixedInput ? (parseFloat(fixedInput.value) || 0) : 0;
        const minProfit = minProfitInput ? (parseFloat(minProfitInput.value) || 0) : 0;
        const minSelling = minSellingInput ? (parseFloat(minSellingInput.value) || 0) : 0;
        const rounding = roundingSelect ? roundingSelect.value : '2_decimal';

        let rawPrice = costNgn;
        if (mode === 'fixed') {
            rawPrice = costNgn + fixed;
        } else if (mode === 'combined') {
            rawPrice = costNgn + (costNgn * pct / 100.0) + fixed;
        } else {
            rawPrice = costNgn + (costNgn * pct / 100.0);
        }

        if (minProfit > 0 && (rawPrice - costNgn) < minProfit) {
            rawPrice = costNgn + minProfit;
        }

        if (minSelling > 0 && rawPrice < minSelling) {
            rawPrice = minSelling;
        }

        // Never sell below cost
        if (rawPrice < costNgn) {
            rawPrice = costNgn;
        }

        let finalPrice = rawPrice;
        if (rounding === 'whole_naira') {
            finalPrice = Math.ceil(rawPrice);
        } else if (rounding === 'nearest_10') {
            finalPrice = Math.ceil(rawPrice / 10.0) * 10;
        } else {
            finalPrice = Math.round(rawPrice * 100.0) / 100.0;
        }

        const profitNgn = Math.max(0, finalPrice - costNgn);
        return {
            costNgn: costNgn,
            finalPrice: finalPrice,
            profitNgn: profitNgn
        };
    }

    function refreshSimulator() {
        if (!matrixBody) return;
        matrixBody.innerHTML = '';

        const isUsd = currencySelect ? (currencySelect.value === 'USD') : false;
        const rates = isUsd ? sampleUsdRates : sampleNgnRates;
        const symbol = isUsd ? '$' : '₦';

        if (testCurrencySymbol) testCurrencySymbol.innerText = symbol;
        if (simulatorRateLabel && fixRateInput) {
            const fx = parseFloat(fixRateInput.value) || 1500;
            simulatorRateLabel.innerText = '₦' + fx.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        rates.forEach(rate => {
            const res = calculatePrice(rate);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-monospace small fw-bold">${symbol}${rate.toFixed(2)}</td>
                <td class="font-monospace small text-muted">₦${res.costNgn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="font-monospace fw-bold text-end text-warning">₦${res.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="font-monospace small text-end text-success">+₦${res.profitNgn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            `;
            matrixBody.appendChild(tr);
        });

        // Test Input calculation
        const testVal = parseFloat(testRateInput.value) || 0;
        const testRes = calculatePrice(testVal);
        testNgnOutput.value = '₦' + testRes.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        testBreakdownText.innerHTML = `Cost: <strong>₦${testRes.costNgn.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong> + Profit: <strong class="text-success">₦${testRes.profitNgn.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong> = <strong>₦${testRes.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>`;
    }

    [fixRateInput, currencySelect, modeSelect, pctInput, fixedInput, minProfitInput, minSellingInput, roundingSelect].forEach(el => {
        if (el) el.addEventListener('input', refreshSimulator);
        if (el && el.tagName === 'SELECT') el.addEventListener('change', refreshSimulator);
    });
    if (testRateInput) testRateInput.addEventListener('input', refreshSimulator);

    document.addEventListener('DOMContentLoaded', refreshSimulator);
</script>
@endpush
@endsection
