@extends('layouts.app')

@section('header_title', 'AI TOOLS — Provider Settings')

@section('content')
<div class="container-fluid px-4 py-3" style="max-width:860px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fa fa-gear me-2 text-primary"></i>AI TOOLS — Provider Settings</h4>
            <p class="text-muted small mb-0">Configure API keys, exchange rate and pricing markup</p>
        </div>
        <a href="{{ route('hq.ai-tools.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- API Connection Status --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h6 class="fw-bold mb-1">API Connection</h6>
                    @if($apiConfigured)
                        <span class="badge bg-success bg-opacity-15 text-success">At least one API key configured</span>
                        @if($maskedKey) <span class="badge bg-secondary">Alaric configured</span> @endif
                        @if($maskedPixelSubKey) <span class="badge bg-secondary">PixelSub configured</span> @endif
                        <span class="text-muted small ms-2">Last sync: {{ $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Never' }}</span>
                    @else
                        <span class="badge bg-warning bg-opacity-15 text-warning">No API keys configured</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="testConnection()">
                        <i class="fa fa-plug me-1"></i>Test Connection
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="checkBalance()">
                        <i class="fa fa-wallet me-1"></i>Check Balance
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="syncProducts()">
                        <i class="fa fa-sync me-1"></i>Sync Products
                    </button>
                </div>
            </div>
            <div id="connectionResult" class="mt-2"></div>
        </div>
    </div>

    {{-- Settings Form --}}
    <form action="{{ route('hq.ai-tools.settings.update') }}" method="POST">
        @csrf

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold">API Credentials</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Alaric API Key</label>
                    @if($maskedKey)
                        <div class="alert alert-secondary small mb-2">
                            Current key: <code>{{ $maskedKey }}</code> — leave blank to keep the current key.
                        </div>
                    @endif
                    <input type="password" name="alaric_api_key" class="form-control"
                        placeholder="{{ $apiConfigured ? 'Enter new key to replace current key' : 'Enter your Alaric Store API key' }}"
                        autocomplete="new-password">
                    <div class="form-text">The API key is stored securely and never exposed to customers.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">PixelSub API Key</label>
                    @if($maskedPixelSubKey)
                        <div class="alert alert-secondary small mb-2">
                            Current key: <code>{{ $maskedPixelSubKey }}</code> — leave blank to keep the current key.
                        </div>
                    @endif
                    <input type="password" name="pixelsub_api_key" class="form-control"
                        placeholder="{{ $pixelSubConfigured ? 'Enter new key to replace current key' : 'Enter PixelSub API key' }}"
                        autocomplete="new-password">
                    <div class="form-text">Stored securely and used server-side only. PixelSub product prices are in USD.</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold">Currency & Pricing</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">USDT → NGN Exchange Rate</label>
                    <div class="input-group">
                        <span class="input-group-text">₦</span>
                        <input type="number" name="usdt_to_ngn_rate" class="form-control"
                            value="{{ old('usdt_to_ngn_rate', $exchangeRate) }}"
                            step="0.01" min="1" required>
                        <span class="input-group-text">per 1 USDT</span>
                    </div>
                    <div class="form-text">This rate is used to convert Alaric's USDT provider cost to NGN before markup.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Global Markup Percentage (%)</label>
                    <div class="input-group">
                        <input type="number" name="markup_ai_tools" class="form-control"
                            value="{{ old('markup_ai_tools', $markupPercent) }}"
                            step="0.01" min="0" max="9999" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Applied on top of the NGN provider cost. Individual product overrides take precedence over this setting.</div>
                </div>

                <div class="p-3 rounded border mb-0" style="background:var(--bg-muted,#f8fafc);">
                    <h6 class="small fw-bold mb-2">Pricing Example</h6>
                    <div class="small text-muted">
                        Provider Cost: <strong>$3.00 USDT</strong><br>
                        NGN Cost: $3.00 × ₦<span id="exRate">{{ $exchangeRate }}</span> = ₦<span id="exNgn">{{ number_format(3 * $exchangeRate, 2) }}</span><br>
                        Customer Price ({{ $markupPercent }}% markup): ₦<span id="exSell">{{ number_format(3 * $exchangeRate * (1 + $markupPercent/100), 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('hq.ai-tools.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary fw-bold"><i class="fa fa-save me-1"></i>Save Settings</button>
        </div>
    </form>
</div>

{{-- Balance Modal --}}
<div class="modal fade" id="balanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Provider Wallet Balance</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="balanceContent"><div class="text-center"><div class="spinner-border text-primary"></div></div></div>
        </div>
    </div>
</div>

{{-- Sync Toast --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="syncToast" class="toast"><div class="toast-header"><strong class="me-auto">AI TOOLS</strong><button class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body" id="syncToastBody"></div></div>
</div>
@endsection

@push('scripts')
<script>
// Live pricing example update
document.querySelector('[name=usdt_to_ngn_rate]')?.addEventListener('input', function() {
    var rate = parseFloat(this.value) || 0;
    var markup = parseFloat(document.querySelector('[name=markup_ai_tools]')?.value) || 0;
    document.getElementById('exRate').textContent = rate.toFixed(2);
    document.getElementById('exNgn').textContent  = (3 * rate).toLocaleString('en-NG',{minimumFractionDigits:2});
    document.getElementById('exSell').textContent = (3 * rate * (1 + markup/100)).toLocaleString('en-NG',{minimumFractionDigits:2});
});
document.querySelector('[name=markup_ai_tools]')?.addEventListener('input', function() {
    var rate = parseFloat(document.querySelector('[name=usdt_to_ngn_rate]')?.value) || 0;
    var markup = parseFloat(this.value) || 0;
    document.getElementById('exSell').textContent = (3 * rate * (1 + markup/100)).toLocaleString('en-NG',{minimumFractionDigits:2});
});

function testConnection() {
    var el = document.getElementById('connectionResult');
    el.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Testing...';
    fetch('{{ route("hq.ai-tools.test-connection") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' }
    }).then(r => r.json()).then(d => {
        el.innerHTML = d.success
            ? '<div class="alert alert-success py-2 small mb-0"><i class="fa fa-check me-1"></i>' + d.message + '</div>'
            : '<div class="alert alert-danger py-2 small mb-0"><i class="fa fa-times me-1"></i>' + d.message + '</div>';
    }).catch(() => { el.innerHTML = '<div class="alert alert-danger py-2 small mb-0">Request failed.</div>'; });
}

function checkBalance() {
    var modal = new bootstrap.Modal(document.getElementById('balanceModal'));
    document.getElementById('balanceContent').innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
    modal.show();
    fetch('{{ route("hq.ai-tools.balance") }}', { headers: { Accept: 'application/json' } })
    .then(r => r.json()).then(d => {
        if (d.success) {
            var data = d.data || {};
            var html = '<table class="table table-sm small"><tbody>';
            Object.entries(data).forEach(([k, v]) => {
                html += '<tr><td class="text-muted">' + k + '</td><td class="fw-bold">' + v + '</td></tr>';
            });
            html += '</tbody></table>';
            document.getElementById('balanceContent').innerHTML = html;
        } else {
            document.getElementById('balanceContent').innerHTML = '<div class="alert alert-danger">' + (d.error_message || 'Failed.') + '</div>';
        }
    }).catch(() => { document.getElementById('balanceContent').innerHTML = '<div class="alert alert-danger">Request failed.</div>'; });
}

function syncProducts() {
    if (!confirm('Force sync AI Tools products from provider?')) return;
    fetch('{{ route("hq.ai-tools.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' }
    }).then(r => r.json()).then(d => {
        document.getElementById('syncToastBody').textContent = d.message || (d.success ? 'Sync complete.' : 'Sync failed.');
        new bootstrap.Toast(document.getElementById('syncToast')).show();
    }).catch(() => alert('Sync failed.'));
}
</script>
@endpush


