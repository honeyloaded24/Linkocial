@extends('layouts.app')

@section('header_title', 'HQ: Provider Pricing & Markup Settings')

@section('content')
<div class="container-fluid">
    <!-- Header / Navigation Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('hq.providers') }}" class="btn btn-light rounded-pill px-3 shadow-sm border">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Providers
            </a>
            <div>
                <h4 class="fw-bold mb-0">Provider Pricing & Markup Settings</h4>
                <p class="text-muted small mb-0">
                    Manage automated wholesale price conversion, configurable profit markups, and live price synchronization for Momopanel services.
                </p>
            </div>
        </div>

        <!-- Provider Switcher -->
        @if(count($providers) > 1)
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted fw-bold text-uppercase mb-0">Provider:</label>
            <select class="form-select form-select-sm shadow-sm rounded-pill" style="min-width: 180px;" onchange="window.location.href='{{ route('hq.providers.pricing') }}?provider_id=' + this.value">
                @foreach($providers as $p)
                    <option value="{{ $p->id }}" {{ $selectedProvider && $selectedProvider->id == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} {{ $p->name === 'Momopanel' ? '(Primary)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
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

    @if(!$selectedProvider)
        <div class="alert alert-warning p-4 rounded-4 shadow-sm">
            <h5 class="fw-bold mb-2"><i class="fa-solid fa-triangle-exclamation me-2"></i> No SMM Provider Found</h5>
            <p class="mb-0">Please create and configure the Momopanel provider first in <a href="{{ route('hq.providers') }}" class="fw-bold">API Providers</a>.</p>
        </div>
    @else

    <!-- Key Metrics & Status Bar -->
    <div class="row g-3 mb-4">
        <!-- Metric 1: Active Exchange Rate -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted text-uppercase fw-bold">Active FX Rate</span>
                    <a href="{{ route('hq.settings.currency') }}" class="btn btn-sm btn-link p-0 text-decoration-none small" title="Configure in Currency Settings">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                </div>
                <div class="fs-4 fw-bold text-success font-monospace">
                    $1 = ₦{{ number_format($usdToNgnRate, 2) }}
                </div>
                <small class="text-muted d-block mt-1">Reused from HQ Exchange Rates</small>
            </div>
        </div>

        <!-- Metric 2: Pricing Mode -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 border-primary">
                <span class="small text-muted text-uppercase fw-bold d-block mb-1">Active Markup</span>
                <div class="fs-4 fw-bold text-primary font-monospace">
                    @if($selectedProvider->getPricingMode() === 'percentage')
                        +{{ $selectedProvider->getPercentageMarkup() }}%
                    @elseif($selectedProvider->getPricingMode() === 'fixed_per_k')
                        +₦{{ number_format($selectedProvider->getFixedMarkupPerK(), 2) }}/k
                    @else
                        +{{ $selectedProvider->getPercentageMarkup() }}% & +₦{{ number_format($selectedProvider->getFixedMarkupPerK(), 2) }}
                    @endif
                </div>
                <small class="text-muted d-block mt-1 text-capitalize">{{ str_replace('_', ' ', $selectedProvider->getPricingMode()) }} Mode</small>
            </div>
        </div>

        <!-- Metric 3: Catalog Mappings -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 border-info">
                <span class="small text-muted text-uppercase fw-bold d-block mb-1">Mapped Services</span>
                <div class="fs-4 fw-bold text-info font-monospace">
                    {{ number_format($activeMappedCount) }} <span class="fs-6 text-muted">/ {{ number_format($mappedCount) }} total</span>
                </div>
                <small class="text-muted d-block mt-1">Synchronized to customer catalog</small>
            </div>
        </div>

        <!-- Metric 4: Sync Status -->
        <div class="col-md-3">
            <div class="premium-card p-3 shadow-sm h-100 border-start border-4 {{ $selectedProvider->last_sync_status === 'success' ? 'border-success' : ($selectedProvider->last_sync_status === 'failed' ? 'border-danger' : 'border-secondary') }}">
                <span class="small text-muted text-uppercase fw-bold d-block mb-1">Last Synchronization</span>
                <div class="d-flex align-items-center gap-2">
                    @if($selectedProvider->last_sync_status === 'success')
                        <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-2">Synced</span>
                    @elseif($selectedProvider->last_sync_status === 'failed')
                        <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-2">Failed</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-25 text-muted rounded-pill px-2">Not Run</span>
                    @endif
                    <span class="small fw-bold">
                        {{ ($selectedProvider->last_synced_at instanceof \Carbon\Carbon) ? $selectedProvider->last_synced_at->diffForHumans() : (!empty($selectedProvider->last_synced_at) ? \Carbon\Carbon::parse($selectedProvider->last_synced_at)->diffForHumans() : 'Never') }}
                    </span>
                </div>
                <small class="text-muted text-truncate d-block mt-1" title="{{ $selectedProvider->last_sync_message ?? 'Ready to synchronize' }}">
                    {{ $selectedProvider->last_sync_message ?? 'Ready to synchronize' }}
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
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-sliders fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">{{ $selectedProvider->name }} Pricing Rules</h5>
                            <small class="text-muted">Configure wholesale currency conversion and profit markup policies</small>
                        </div>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill font-monospace" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-shield-halved me-1"></i> Provider-Isolated
                    </span>
                </div>

                <form action="{{ route('hq.providers.pricing.update') }}" method="POST" id="pricingConfigForm">
                    @csrf
                    <input type="hidden" name="provider_id" value="{{ $selectedProvider->id }}">

                    <!-- Field 1: Provider Wholesale Currency -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Provider Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="currencySelect" class="form-select form-select-lg" required>
                                <option value="USD" {{ old('currency', $selectedProvider->getCurrency()) === 'USD' ? 'selected' : '' }}>USD ($ - US Dollar)</option>
                                <option value="NGN" {{ old('currency', $selectedProvider->getCurrency()) === 'NGN' ? 'selected' : '' }}>NGN (₦ - Nigerian Naira)</option>
                            </select>
                            <div class="form-text small">Momopanel rates are denominated in USD per 1,000 units.</div>
                        </div>

                        <!-- Field 2: Pricing Mode -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Pricing Mode <span class="text-danger">*</span></label>
                            <select name="pricing_mode" id="pricingModeSelect" class="form-select form-select-lg" required>
                                <option value="percentage" {{ old('pricing_mode', $selectedProvider->getPricingMode()) === 'percentage' ? 'selected' : '' }}>
                                    Percentage Markup (Recommended)
                                </option>
                                <option value="fixed_per_k" {{ old('pricing_mode', $selectedProvider->getPricingMode()) === 'fixed_per_k' ? 'selected' : '' }}>
                                    Fixed Markup per 1,000 units
                                </option>
                                <option value="combined" {{ old('pricing_mode', $selectedProvider->getPricingMode()) === 'combined' ? 'selected' : '' }}>
                                    Combined (Percentage + Fixed per 1k)
                                </option>
                            </select>
                            <div class="form-text small">Select the mathematical calculation strategy.</div>
                        </div>
                    </div>

                    <!-- Markup Inputs Row -->
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
                                       value="{{ old('percentage_markup', $selectedProvider->getPercentageMarkup()) }}" 
                                       required>
                                <span class="input-group-text bg-light border-secondary text-muted fw-bold">%</span>
                            </div>
                            <div class="form-text small">Percentage added to converted provider cost (e.g. <code>30%</code>).</div>
                        </div>

                        <!-- Fixed Markup per 1,000 -->
                        <div class="col-md-6" id="fixedMarkupGroup">
                            <label class="form-label fw-bold small text-muted text-uppercase">Fixed Markup per 1,000 units (₦) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3">
                                <span class="input-group-text bg-light border-secondary text-success fw-bold">₦</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       id="fixedMarkupInput" 
                                       name="fixed_markup_per_k" 
                                       class="form-control form-control-lg border-secondary fw-bold" 
                                       value="{{ old('fixed_markup_per_k', $selectedProvider->getFixedMarkupPerK()) }}" 
                                       required>
                                <span class="input-group-text bg-light border-secondary text-muted small">/1k</span>
                            </div>
                            <div class="form-text small">Fixed Naira added per 1,000 units (e.g. <code>₦100</code>).</div>
                        </div>
                    </div>

                    <!-- Safeguards Row: Min Price & Min Profit -->
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
                                       placeholder="Optional (e.g. 50)" 
                                       value="{{ old('min_selling_price', $selectedProvider->getMinSellingPrice()) }}">
                            </div>
                            <div class="form-text small">Absolute price floor for any service from this provider.</div>
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
                                       placeholder="Optional (e.g. 20)" 
                                       value="{{ old('min_profit', $selectedProvider->getMinProfit()) }}">
                            </div>
                            <div class="form-text small">Minimum profit earned per 1,000 units above provider cost.</div>
                        </div>
                    </div>

                    <!-- Rounding Policy -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Rounding Policy <span class="text-danger">*</span></label>
                        <select name="rounding_rule" id="roundingRuleSelect" class="form-select" required>
                            <option value="2_decimal" {{ old('rounding_rule', $selectedProvider->getRoundingRule()) === '2_decimal' ? 'selected' : '' }}>
                                Standard 2 Decimals (e.g. ₦2,015.50) — High Precision
                            </option>
                            <option value="whole_naira" {{ old('rounding_rule', $selectedProvider->getRoundingRule()) === 'whole_naira' ? 'selected' : '' }}>
                                Round Up to Whole Naira (e.g. ₦2,016.00) — Clean Pricing
                            </option>
                            <option value="nearest_10" {{ old('rounding_rule', $selectedProvider->getRoundingRule()) === 'nearest_10' ? 'selected' : '' }}>
                                Round Up to Nearest ₦10 (e.g. ₦2,020.00) — Commercial Standard
                            </option>
                        </select>
                        <div class="form-text small">Determines how fractional customer selling prices in NGN are presented.</div>
                    </div>

                    <!-- Protection Notice -->
                    <div class="alert alert-info bg-primary bg-opacity-10 border-0 rounded-4 p-3 mb-4 small">
                        <div class="d-flex gap-2">
                            <i class="fa-solid fa-shield-halved text-primary fs-5 mt-1"></i>
                            <div>
                                <strong class="d-block text-primary mb-1">Financial Integrity Guarantee:</strong>
                                Saving markup rules here <strong>will never sell services at a loss</strong>. A hard floor prevents selling prices from dropping below converted provider cost. Existing orders and wallet balances remain completely unaffected.
                            </div>
                        </div>
                    </div>

                    <!-- Save Action -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary-custom px-4 py-3 fw-bold rounded-pill shadow-sm flex-grow-1">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save Markup Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Math & Interactive Verification Column -->
        <div class="col-lg-5">
            <div class="premium-card p-4 shadow-sm h-100">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-calculator text-primary-custom me-2"></i> Live Pricing Simulator</h5>
                <p class="text-muted small mb-3">
                    Calculated in real-time using current exchange rate (<strong class="text-success font-monospace">₦{{ number_format($usdToNgnRate, 2) }}</strong>) and form parameters:
                </p>

                <!-- Example Calculation Matrix -->
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th>Provider Rate</th>
                                <th>Converted Cost</th>
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
                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">Test Any Provider Wholesale Rate</label>
                    <div class="row g-2 align-items-center">
                        <div class="col-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" id="testUsdInput" class="form-control font-monospace" value="2.50" step="0.01" min="0">
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
                        Breakdown: Cost ₦3,875.00 + Markup = Selling Price
                    </div>
                </div>

                <!-- Sync Controls Card -->
                <div class="p-4 rounded-4 bg-light bg-opacity-75 border">
                    <h6 class="fw-bold mb-2"><i class="fa-solid fa-arrows-rotate text-primary me-2"></i> Price Synchronization Controls</h6>
                    <p class="text-muted small mb-3">
                        Fetch current provider wholesale prices, preview recalculations against the catalog, and apply approved updates safely.
                    </p>

                    <div class="d-flex flex-column gap-2">
                        <!-- Preview Button -->
                        <form action="{{ route('hq.providers.pricing.preview') }}" method="POST" id="previewSyncForm">
                            @csrf
                            <input type="hidden" name="provider_id" value="{{ $selectedProvider->id }}">
                            <button type="submit" class="btn btn-outline-primary rounded-pill w-100 fw-bold py-2 shadow-sm">
                                <i class="fa-solid fa-magnifying-glass me-2"></i> Preview Live Price Recalculation
                            </button>
                        </form>

                        <!-- Execute Sync Button -->
                        <form action="{{ route('hq.providers.pricing.sync') }}" method="POST" id="executeSyncForm" onsubmit="return confirm('CONFIRMATION: Are you sure you want to update live catalog selling prices for {{ $selectedProvider->name }}? This will synchronize latest rates and recalculate selling prices based on your saved markup.');">
                            @csrf
                            <input type="hidden" name="provider_id" value="{{ $selectedProvider->id }}">
                            <button type="submit" class="btn btn-primary-custom rounded-pill w-100 fw-bold py-2 shadow-sm">
                                <i class="fa-solid fa-bolt me-2"></i> Apply Price Updates to Catalog Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Preview Audit Section (Displayed when a preview was requested) -->
    @if(session('sync_preview'))
    @php
        $preview = session('sync_preview');
        $items = $preview['items'] ?? [];
    @endphp
    <div class="row g-4 mb-4" id="previewResultsSection">
        <div class="col-12">
            <div class="premium-card p-4 shadow-sm border-primary border-opacity-25">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <h5 class="fw-bold text-primary mb-1">
                            <i class="fa-solid fa-clipboard-check me-2"></i> Live Price Synchronization Preview
                        </h5>
                        <p class="text-muted small mb-0">
                            Audit of recalculated prices fetched live from {{ $preview['provider_name'] ?? 'Provider' }} at exchange rate <strong>₦{{ number_format($preview['exchange_rate'] ?? $usdToNgnRate, 2) }}</strong>. Database has NOT been updated yet.
                        </p>
                    </div>

                    <!-- Direct Apply Button from Preview -->
                    <form action="{{ route('hq.providers.pricing.sync') }}" method="POST" onsubmit="return confirm('Apply these recalculated prices to the catalog?');">
                        @csrf
                        <input type="hidden" name="provider_id" value="{{ $selectedProvider->id }}">
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                            <i class="fa-solid fa-check me-1"></i> Approve & Apply Price Updates
                        </button>
                    </form>
                </div>

                <!-- Preview Statistics Chips -->
                <div class="row g-2 mb-3">
                    <div class="col-md-2 col-6">
                        <div class="p-2 bg-light rounded-3 text-center border">
                            <small class="text-muted d-block">Total Services</small>
                            <span class="fs-5 fw-bold font-monospace">{{ number_format($preview['total_services'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2 bg-light rounded-3 text-center border">
                            <small class="text-muted d-block">Price Increases</small>
                            <span class="fs-5 fw-bold font-monospace text-danger">{{ number_format($preview['price_increases'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2 bg-light rounded-3 text-center border">
                            <small class="text-muted d-block">Price Decreases</small>
                            <span class="fs-5 fw-bold font-monospace text-success">{{ number_format($preview['price_decreases'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2 bg-light rounded-3 text-center border">
                            <small class="text-muted d-block">Unchanged</small>
                            <span class="fs-5 fw-bold font-monospace text-muted">{{ number_format($preview['unchanged'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2 bg-light rounded-3 text-center border">
                            <small class="text-muted d-block">New Services</small>
                            <span class="fs-5 fw-bold font-monospace text-info">{{ number_format($preview['new_services'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2 bg-light rounded-3 text-center border">
                            <small class="text-muted d-block">Applied FX Rate</small>
                            <span class="fs-5 fw-bold font-monospace text-primary">₦{{ number_format($preview['exchange_rate'] ?? $usdToNgnRate, 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Search Input for Preview Table -->
                <div class="mb-3">
                    <input type="text" id="previewTableFilter" class="form-control form-control-sm" placeholder="Filter preview by service name, ID, or category...">
                </div>

                <!-- Preview Table -->
                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0" id="previewTable">
                        <thead class="table-light sticky-top">
                            <tr class="small text-muted text-uppercase">
                                <th>Provider ID</th>
                                <th>Service Name</th>
                                <th>Category</th>
                                <th>USD Rate</th>
                                <th>Cost (NGN)</th>
                                <th>Old Selling</th>
                                <th>New Selling</th>
                                <th>Change</th>
                                <th>Est. Profit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td class="font-monospace fw-bold text-muted small">#{{ $item['provider_service_id'] }}</td>
                                <td style="max-width: 250px;">
                                    <span class="fw-bold text-truncate d-block" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                    <small class="text-muted">{{ $item['type'] }} | Min: {{ $item['min'] }} Max: {{ $item['max'] }}</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $item['category'] }}</span></td>
                                <td class="font-monospace small">${{ number_format($item['provider_rate_usd'], 4) }}</td>
                                <td class="font-monospace small text-muted">₦{{ number_format($item['cost_ngn'], 2) }}</td>
                                <td class="font-monospace small">
                                    {{ $item['old_selling_price'] !== null ? '₦' . number_format($item['old_selling_price'], 2) : '—' }}
                                </td>
                                <td class="font-monospace fw-bold fs-6 text-primary-custom">
                                    ₦{{ number_format($item['new_selling_price'], 2) }}
                                </td>
                                <td>
                                    @if($item['status'] === 'increase')
                                        <span class="badge bg-danger bg-opacity-10 text-danger font-monospace">+₦{{ number_format($item['diff_ngn'], 2) }}</span>
                                    @elseif($item['status'] === 'decrease')
                                        <span class="badge bg-success bg-opacity-10 text-success font-monospace">-₦{{ number_format(abs($item['diff_ngn']), 2) }}</span>
                                    @elseif($item['status'] === 'unchanged')
                                        <span class="badge bg-secondary bg-opacity-10 text-muted">Same</span>
                                    @else
                                        <span class="badge bg-info bg-opacity-10 text-info">New Service</span>
                                    @endif
                                </td>
                                <td class="font-monospace small text-success fw-bold">
                                    ₦{{ number_format($item['profit_ngn'], 2) }}
                                    <small class="text-muted d-block">({{ $item['profit_margin'] }}%)</small>
                                </td>
                                <td>
                                    @if($item['is_existing'])
                                        <span class="badge bg-primary bg-opacity-10 text-primary">Mapped</span>
                                    @else
                                        <span class="badge bg-info bg-opacity-10 text-info">Unmapped</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif
</div>

@push('scripts')
<script>
    const fxRate = {{ $usdToNgnRate }};
    const modeSelect = document.getElementById('pricingModeSelect');
    const pctInput = document.getElementById('percentageMarkupInput');
    const fixedInput = document.getElementById('fixedMarkupInput');
    const minProfitInput = document.getElementById('minProfitInput');
    const minSellingInput = document.getElementById('minSellingPriceInput');
    const roundingSelect = document.getElementById('roundingRuleSelect');
    const currencySelect = document.getElementById('currencySelect');

    const matrixBody = document.getElementById('exampleMatrixBody');
    const testUsd = document.getElementById('testUsdInput');
    const testNgn = document.getElementById('testNgnOutput');
    const testBreakdown = document.getElementById('testBreakdownText');

    const sampleRates = [0.50, 1.00, 2.50, 5.00, 10.00];

    function calculatePrice(usdRate) {
        const isUsd = currencySelect ? (currencySelect.value === 'USD') : true;
        const costNgn = isUsd ? (usdRate * fxRate) : usdRate;

        const mode = modeSelect ? modeSelect.value : 'percentage';
        const pct = pctInput ? (parseFloat(pctInput.value) || 0) : 30;
        const fixed = fixedInput ? (parseFloat(fixedInput.value) || 0) : 0;
        const minProfit = minProfitInput ? (parseFloat(minProfitInput.value) || 0) : 0;
        const minSelling = minSellingInput ? (parseFloat(minSellingInput.value) || 0) : 0;
        const rounding = roundingSelect ? roundingSelect.value : '2_decimal';

        let rawPrice = costNgn;
        if (mode === 'fixed_per_k') {
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

        sampleRates.forEach(rate => {
            const res = calculatePrice(rate);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-monospace small fw-bold">$${rate.toFixed(2)}</td>
                <td class="font-monospace small text-muted">₦${res.costNgn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="font-monospace fw-bold text-end text-primary-custom">₦${res.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="font-monospace small text-end text-success">+₦${res.profitNgn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            `;
            matrixBody.appendChild(tr);
        });

        // Test Input calculation
        const testVal = parseFloat(testUsd.value) || 0;
        const testRes = calculatePrice(testVal);
        testNgn.value = '₦' + testRes.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        testBreakdown.innerHTML = `Cost: <strong>₦${testRes.costNgn.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong> + Profit: <strong class="text-success">₦${testRes.profitNgn.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong> = <strong>₦${testRes.finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>`;
    }

    // Attach listeners
    [modeSelect, pctInput, fixedInput, minProfitInput, minSellingInput, roundingSelect, currencySelect].forEach(el => {
        if (el) el.addEventListener('input', refreshSimulator);
    });
    if (testUsd) testUsd.addEventListener('input', refreshSimulator);

    // Filter table in preview
    const tableFilter = document.getElementById('previewTableFilter');
    if (tableFilter) {
        tableFilter.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#previewTable tbody tr');
            rows.forEach(r => {
                const text = r.innerText.toLowerCase();
                r.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', refreshSimulator);
</script>
@endpush
@endsection
