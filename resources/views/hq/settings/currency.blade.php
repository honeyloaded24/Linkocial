@extends('layouts.app')

@section('header_title', 'HQ: Currency & Exchange Rate Settings')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb & Back navigation -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('hq.settings') }}" class="btn btn-light rounded-pill px-3 shadow-sm border">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to HQ Settings
            </a>
            <div>
                <h4 class="fw-bold mb-0">Currency & Exchange Rate Settings</h4>
                <p class="text-muted small mb-0">
                    Manage the exchange rate used to convert between Nigerian Naira (NGN) and US Dollars (USD) for supported legacy balances, currency conversion operations, and integrations that require currency conversion.
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success bg-success text-white border-0 shadow-sm mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger bg-danger text-white border-0 shadow-sm mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger bg-danger text-white border-0 shadow-sm mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Configuration Card -->
        <div class="col-lg-7">
            <div class="premium-card p-4 shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <span class="fs-4 fw-bold">₦</span>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Authoritative Conversion Rates</h5>
                            <small class="text-muted">Single authoritative baseline for all platform currency calculations</small>
                        </div>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill font-monospace" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-lock me-1"></i> Backend Enforced
                    </span>
                </div>

                <form action="{{ route('hq.settings.currency.update') }}" method="POST" id="rateSettingsForm">
                    @csrf

                    <!-- Field 1: USD to NGN -->
                    <div class="mb-4">
                        <label for="usd_to_ngn_rate" class="form-label fw-bold text-uppercase small text-muted">
                            1 USD equals how many NGN? <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                            <span class="input-group-text bg-light border-secondary text-muted fw-bold">$1 USD =</span>
                            <span class="input-group-text bg-light border-secondary text-success fw-bold">₦</span>
                            <input type="number" 
                                   step="any" 
                                   min="0.0001" 
                                   id="usd_to_ngn_rate" 
                                   name="usd_to_ngn_rate" 
                                   class="form-control form-control-lg border-secondary fw-bold fs-4 text-primary-custom" 
                                   value="{{ old('usd_to_ngn_rate', $usdToNgnRate) }}" 
                                   required>
                        </div>
                        <div class="form-text small text-muted mt-1">
                            The number of Nigerian Naira equivalent to one US Dollar. (Example: <code>1500</code> means $1.00 USD = ₦1,500.00 NGN).
                        </div>
                    </div>

                    <!-- Field 2: NGN to USD (Reverse Rate) -->
                    <div class="mb-4">
                        <label for="ngn_to_usd_rate" class="form-label fw-bold text-uppercase small text-muted">
                            1 NGN equals how many USD?
                        </label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                            <span class="input-group-text bg-light border-secondary text-muted fw-bold">₦1 NGN =</span>
                            <span class="input-group-text bg-light border-secondary text-muted fw-bold">$</span>
                            <input type="number" 
                                   step="any" 
                                   min="0.0000000001" 
                                   id="ngn_to_usd_rate" 
                                   name="ngn_to_usd_rate" 
                                   class="form-control form-control-lg border-secondary font-monospace" 
                                   value="{{ old('ngn_to_usd_rate', number_format($ngnToUsdRate, 10, '.', '')) }}">
                        </div>
                        <div class="form-text small text-muted mt-1">
                            The number of US Dollars equivalent to one Nigerian Naira (Calculated automatically as <code>1 / Rate</code>).
                        </div>
                    </div>

                    <!-- Rule note -->
                    <div class="alert alert-info bg-primary bg-opacity-10 border-0 rounded-4 p-3 mb-4 small">
                        <div class="d-flex gap-2">
                            <i class="fa-solid fa-shield-halved text-primary fs-5 mt-1"></i>
                            <div>
                                <strong class="d-block text-primary mb-1">Financial Safety Guarantee:</strong>
                                Saving a new exchange rate here <strong>will not alter existing NGN wallet balances</strong>. It governs new conversions, provider wholesale rate mapping, and approved legacy balance recoveries.
                            </div>
                        </div>
                    </div>

                    <!-- Meta information -->
                    <div class="p-3 bg-light rounded-4 border mb-4 d-flex justify-content-between align-items-center small">
                        <div>
                            <span class="text-muted d-block">Last Updated:</span>
                            <strong>{{ $lastUpdatedAt ? \Carbon\Carbon::parse($lastUpdatedAt)->format('M d, Y @ H:i:s') : 'System Default' }}</strong>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block">Updated By:</span>
                            <strong>{{ $lastUpdatedBy ?? 'System Initialization' }}</strong>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary-custom px-4 py-3 fw-bold rounded-pill shadow-sm flex-grow-1">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save Exchange Rates
                        </button>
                        <button type="reset" class="btn btn-outline-secondary px-4 py-3 fw-bold rounded-pill" onclick="resetToInitial()">
                            Cancel / Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Live Conversion Matrix & Calculator -->
        <div class="col-lg-5">
            <!-- Dynamic Conversion Matrix -->
            <div class="premium-card p-4 shadow-sm mb-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-calculator text-primary-custom me-2"></i> Live Rate Conversion Matrix</h5>
                <p class="text-muted small mb-3">Live mathematical breakdown at current input rate (<span class="fw-bold text-primary-custom" id="previewRateLabel">₦{{ number_format($usdToNgnRate, 2) }}</span> per $1.00 USD):</p>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th>Original USD</th>
                                <th class="text-end">Converted NGN</th>
                            </tr>
                        </thead>
                        <tbody id="matrixTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <label class="form-label small fw-bold text-muted text-uppercase">Quick Interactive Calculator</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" id="calcUsdInput" class="form-control" value="47" step="any">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₦</span>
                                <input type="text" id="calcNgnOutput" class="form-control fw-bold text-success" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recovery & Reconciliation Overview -->
            <div class="premium-card p-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-rotate-left text-warning me-2"></i> Wallet Recovery Audit</h5>
                    <span class="badge bg-warning bg-opacity-10 text-dark px-3 py-1 rounded-pill small fw-bold">Audit Status</span>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4 text-center">
                            <span class="text-muted d-block small">Total Wallets</span>
                            <span class="fs-4 fw-bold">{{ number_format($totalUsers) }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4 text-center">
                            <span class="text-muted d-block small">Recovered / Migrated</span>
                            <span class="fs-4 fw-bold text-success">{{ number_format($migratedUsersCount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-4 bg-light bg-opacity-50 border small mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Terminal Command:</span>
                        <code class="text-primary fw-bold">wallet:recover-legacy-balances</code>
                    </div>
                    <pre class="bg-dark text-success p-2 rounded small mb-3 font-monospace" style="font-size: 0.75rem;">php artisan wallet:recover-legacy-balances --apply</pre>

                    <!-- Web-based Execution Controls -->
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('hq.settings.currency.run-recovery') }}" class="flex-grow-1">
                            @csrf
                            <input type="hidden" name="recovery_action" value="dry_run">
                            <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill w-100 fw-bold">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Preview Dry-Run
                            </button>
                        </form>
                        <form method="POST" action="{{ route('hq.settings.currency.run-recovery') }}" class="flex-grow-1" onsubmit="return confirm('WARNING: Are you sure you want to execute live balance recovery for all eligible USD accounts at 1 USD = ₦{{ $usdToNgnRate }}?');">
                            @csrf
                            <input type="hidden" name="recovery_action" value="apply">
                            <button type="submit" class="btn btn-sm btn-danger rounded-pill w-100 fw-bold">
                                <i class="fa-solid fa-bolt me-1"></i> Apply Recovery Now
                            </button>
                        </form>
                    </div>
                </div>

                @if(session('recovery_output'))
                <!-- Console Output Modal / Box -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                        <i class="fa-solid fa-terminal me-1"></i> Recovery Execution Output:
                    </label>
                    <pre class="bg-dark text-light p-3 rounded-4 font-monospace small shadow-sm overflow-auto" style="max-height: 250px; font-size: 0.72rem; white-space: pre-wrap;">{{ session('recovery_output') }}</pre>
                </div>
                @endif
            </div>
        </div>

        @if(count($ambiguousUsers) > 0)
        <!-- Ambiguous Accounts Manual Reconciliation Card -->
        <div class="col-12">
            <div class="premium-card p-4 shadow-sm border-danger border-opacity-25">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-danger mb-1"><i class="fa-solid fa-flag me-2"></i> Accounts Requiring Manual Reconciliation</h5>
                        <p class="text-muted small mb-0">These customer wallets have conflicting or non-standard balance history and were flagged to prevent guessing.</p>
                    </div>
                    <span class="badge bg-danger px-3 py-2 rounded-pill">{{ count($ambiguousUsers) }} Action Required</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="small text-muted text-uppercase">
                                <th>User ID</th>
                                <th>Customer</th>
                                <th>Current Stored Balance</th>
                                <th>Audit Notes / Reason</th>
                                <th>Resolution Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ambiguousUsers as $amb)
                            <tr>
                                <td class="fw-bold">#{{ $amb->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $amb->username ?? $amb->name }}</div>
                                    <small class="text-muted">{{ $amb->email }}</small>
                                </td>
                                <td class="fs-5 font-monospace fw-bold">{{ number_format($amb->balance, 2) }}</td>
                                <td class="small text-muted" style="max-width: 300px;">{{ $amb->reconciliation_notes }}</td>
                                <td>
                                    <form action="{{ route('hq.settings.currency.reconcile-user') }}" method="POST" class="d-flex align-items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $amb->id }}">
                                        <select name="action" class="form-select form-select-sm" style="width: 170px;">
                                            <option value="convert_as_usd">Convert as USD (x{{ number_format($usdToNgnRate, 0) }})</option>
                                            <option value="mark_as_ngn">Confirm as NGN (Keep)</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary-custom px-3 rounded-pill" onclick="return confirm('Reconcile this user balance?')">
                                            Apply
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    const usdInput = document.getElementById('usd_to_ngn_rate');
    const ngnInput = document.getElementById('ngn_to_usd_rate');
    const previewRateLabel = document.getElementById('previewRateLabel');
    const matrixTbody = document.getElementById('matrixTableBody');
    const calcUsd = document.getElementById('calcUsdInput');
    const calcNgn = document.getElementById('calcNgnOutput');

    const sampleUsdAmounts = [1, 5, 10, 47, 100, 1000];

    function updateCalculations() {
        const rate = parseFloat(usdInput.value) || 0;
        if (rate > 0) {
            // Update reverse rate if not focused
            if (document.activeElement !== ngnInput) {
                const reverse = (1.0 / rate).toFixed(10);
                ngnInput.value = reverse;
            }

            previewRateLabel.textContent = '₦' + rate.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Update matrix rows
            matrixTbody.innerHTML = '';
            sampleUsdAmounts.forEach(amt => {
                const converted = (amt * rate).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="fw-bold font-monospace">$${amt.toLocaleString()} USD</td>
                    <td class="text-end fw-bold font-monospace text-success">₦${converted} NGN</td>
                `;
                matrixTbody.appendChild(tr);
            });

            // Update interactive calculator
            const testAmt = parseFloat(calcUsd.value) || 0;
            calcNgn.value = '₦' + (testAmt * rate).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    usdInput.addEventListener('input', updateCalculations);
    calcUsd.addEventListener('input', updateCalculations);

    ngnInput.addEventListener('input', function() {
        const reverse = parseFloat(ngnInput.value) || 0;
        if (reverse > 0) {
            const forward = (1.0 / reverse).toFixed(2);
            usdInput.value = forward;
            updateCalculations();
        }
    });

    function resetToInitial() {
        setTimeout(updateCalculations, 50);
    }

    document.addEventListener('DOMContentLoaded', updateCalculations);
</script>
@endpush
@endsection
