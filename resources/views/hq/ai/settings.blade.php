@extends('layouts.app')

@section('title', 'Admin HQ — AI Marketplace Settings')

@section('content')
<div class="container-fluid py-4 px-md-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-sliders text-primary me-2"></i> AI Marketplace Settings</h4>
            <p class="text-muted small mb-0">Configure your AIPlug provider API credentials, webhook verification, and retail pricing markup.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hq.ai.index') }}" class="btn btn-outline-primary rounded-pill px-3">
                <i class="fa-solid fa-robot me-1"></i> Products Catalog
            </a>
            <a href="{{ route('hq.ai.orders') }}" class="btn btn-primary rounded-pill px-3">
                <i class="fa-solid fa-receipt me-1"></i> Orders
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Settings Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h5 class="fw-bold mb-4">
                    <i class="fa-solid fa-key text-primary me-2"></i> API Configuration
                </h5>

                <form method="POST" action="{{ route('hq.ai.settings.save') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Provider Base URL</label>
                        <input type="url" name="aiplug_base_url" class="form-control" value="{{ old('aiplug_base_url', $baseUrl) }}" required>
                        <small class="text-muted">Default: <code>https://resellers.aiplug.store/api/reseller/v1</code></small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">API Key (Bearer Token)</label>
                        <div class="input-group">
                            <input type="password" name="aiplug_api_key" id="apiKeyInput" class="form-control font-monospace" value="{{ old('aiplug_api_key', $apiKey) }}" placeholder="Enter AIPlug API Key">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSecretVisibility('apiKeyInput')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Provided by AIPlug reseller dashboard.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Webhook Signing Secret</label>
                        <div class="input-group">
                            <input type="password" name="aiplug_webhook_secret" id="secretInput" class="form-control font-monospace" value="{{ old('aiplug_webhook_secret', $webhookSecret) }}" placeholder="whsec_...">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSecretVisibility('secretInput')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Used to cryptographically verify <code>X-AIPlug-Signature</code> HMAC-SHA256 headers.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Global Pricing Markup (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.1" min="0" max="500" name="ai_marketplace_markup" class="form-control" value="{{ old('ai_marketplace_markup', $markup) }}" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Markup added to wholesale price in NGN when syncing products (e.g., 20% markup turns ₦1,000 cost into ₦1,200 retail price).</small>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                        <i class="fa-solid fa-save me-1"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Provider Status & Webhook Registration -->
        <div class="col-lg-5">
            <!-- Provider Balance Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fa-solid fa-wallet text-success me-2"></i> Provider Wallet Balance
                </h6>

                @if($providerBalance && ($providerBalance['success'] ?? false))
                    <div class="p-3 bg-success bg-opacity-10 rounded-3 mb-2">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Available Balance</small>
                        <h3 class="fw-extrabold text-success mb-0">{{ money($providerBalance['balanceNgn']) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">({{ number_format($providerBalance['balanceKobo']) }} Kobo)</small>
                    </div>
                    <span class="badge bg-success rounded-pill px-2.5 py-1 small">
                        <i class="fa-solid fa-check me-1"></i> API Connected & Valid
                    </span>
                @else
                    <div class="p-3 bg-light rounded-3 mb-2">
                        <div class="text-muted small">
                            {{ $providerBalance['message'] ?? 'API key not configured yet or provider unavailable.' }}
                        </div>
                    </div>
                    <span class="badge bg-secondary rounded-pill px-2.5 py-1 small">
                        Not Connected
                    </span>
                @endif
            </div>

            <!-- Automatic Webhook Registration -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h6 class="fw-bold mb-3">
                    <i class="fa-solid fa-bolt text-warning me-2"></i> Register Webhook Endpoint
                </h6>
                <p class="text-muted small mb-3">
                    Your server must receive signed order fulfillment updates from AIPlug at your public HTTPS endpoint.
                </p>

                <form method="POST" action="{{ route('hq.ai.settings.register-webhook') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Your Webhook URL</label>
                        <input type="url" name="webhook_url" class="form-control small font-monospace" value="{{ $suggestedWebhookUrl }}" required>
                    </div>

                    <button type="submit" class="btn btn-outline-primary rounded-pill w-100 fw-bold" {{ empty($apiKey) ? 'disabled' : '' }}>
                        <i class="fa-solid fa-paper-plane me-1"></i> Register Webhook with AIPlug
                    </button>
                    @if(empty($apiKey))
                        <small class="text-danger d-block mt-2" style="font-size: 0.75rem;">Save your API Key first to enable automatic webhook registration.</small>
                    @endif
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function toggleSecretVisibility(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endsection
