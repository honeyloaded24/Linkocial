@extends('layouts.app')

@section('header_title', 'API Documentation')

@section('content')
<div class="container-fluid py-2">
    <!-- Header Hero Banner -->
    <div class="premium-card p-4 p-md-5 mb-4 shadow-sm border-0" style="background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.12) 0%, rgba(17, 24, 39, 0.95) 100%);">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 text-primary-custom fw-bold small mb-3 border border-primary border-opacity-20">
                    <i class="fa-solid fa-bolt"></i> Public Reseller API v1.0
                </div>
                <h1 class="fw-bold mb-3" style="font-family: var(--heading-font); letter-spacing: -0.5px;">Standard SMM Reseller API</h1>
                <p class="text-muted fs-6 mb-4" style="max-width: 680px;">
                    Integrate Linkocial services directly into your own website, application, or custom panel. Our API is fully compliant with the global SMM reseller standard.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-circle-check me-1"></i> Publicly Accessible
                    </span>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20 px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-code-compare me-1"></i> SMM Standard v1 & v2 Compatible
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-naira-sign me-1"></i> Denominated in NGN
                    </span>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="p-4 rounded-4 border border-secondary border-opacity-20 shadow-sm" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(10px);">
                    <small class="text-muted d-block text-uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Primary API Base URL</small>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control form-control-sm font-monospace text-primary-custom fw-bold" id="primaryApiUrl" value="{{ url('/api/v1') }}" readonly style="background: var(--bg-body); border-color: var(--border-color);">
                        <button class="btn btn-outline-primary btn-sm" type="button" onclick="copyToClipboard('primaryApiUrl', this)" title="Copy URL">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                    <small class="text-muted d-block text-uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Alternative Endpoints</small>
                    <code class="d-block small text-muted mb-1">{{ url('/api') }}</code>
                    <code class="d-block small text-muted">{{ url('/v1') }}</code>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Documentation Body -->
        <div class="col-lg-8 col-xl-9">
            <!-- Quick Start / Integration Guide -->
            <div class="premium-card p-4 p-md-4 mb-4 border-0 shadow-sm">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-puzzle-piece text-primary"></i> Upstream Panel & Integration Setup
                </h5>
                <p class="text-muted small mb-3">
                    If you are connecting Linkocial as an upstream provider inside your panel or application dashboard, configure the provider settings as follows:
                </p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0" style="border-color: var(--border-color);">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th style="width: 30%;">Panel Setting Field</th>
                                <th>Value to Enter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold small">API URL / Domain</td>
                                <td><code class="text-primary fw-bold">{{ url('/api/v1') }}</code> <span class="text-muted small">(or <code>{{ url('/api') }}</code>)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold small">API Key</td>
                                <td>
                                    @auth
                                        @if(Auth::user()->api_key)
                                            <span class="badge bg-success bg-opacity-10 text-success">Active Key Linked</span>
                                            <span class="text-muted small ms-1">(Found in your profile)</span>
                                        @else
                                            <a href="{{ route('profile.index') }}" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill">Generate Key in Profile</a>
                                        @endif
                                    @else
                                        <span class="text-muted small">Your secret API key generated from <a href="{{ route('login') }}">Linkocial Profile</a></span>
                                    @endauth
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold small">API Type / Protocol</td>
                                <td><span class="badge bg-secondary">Standard SMM</span> or <span class="badge bg-secondary">Custom</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold small">HTTP Method</td>
                                <td><code>POST</code> <span class="text-muted small">(supports both JSON and x-www-form-urlencoded)</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- API Endpoints Card -->
            <div class="premium-card border-0 shadow-sm mb-4">
                <div class="p-4 border-bottom" style="border-color: var(--border-color) !important;">
                    <ul class="nav nav-pills gap-2 flex-wrap" id="apiTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active rounded-pill px-3 py-1.5 fw-bold small" id="services-tab" data-bs-toggle="tab" data-bs-target="#tab-services" type="button">
                                <i class="fa-solid fa-list-ul me-1"></i> 1. Service List
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-3 py-1.5 fw-bold small" id="add-tab" data-bs-toggle="tab" data-bs-target="#tab-add" type="button">
                                <i class="fa-solid fa-cart-plus me-1"></i> 2. Place Order
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-3 py-1.5 fw-bold small" id="status-tab" data-bs-toggle="tab" data-bs-target="#tab-status" type="button">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> 3. Order Status
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-3 py-1.5 fw-bold small" id="orders-tab" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button">
                                <i class="fa-solid fa-layer-group me-1"></i> 4. Multiple Orders
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-3 py-1.5 fw-bold small" id="balance-tab" data-bs-toggle="tab" data-bs-target="#tab-balance" type="button">
                                <i class="fa-solid fa-wallet me-1"></i> 5. User Balance
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="p-4 p-md-5">
                    <div class="tab-content" id="apiTabsContent">
                        <!-- 1. Service List -->
                        <div class="tab-pane fade show active" id="tab-services" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h4 class="fw-bold mb-1">Service List</h4>
                                    <p class="text-muted small mb-0">Retrieve all available SMM services with IDs, categories, rates per 1,000, and limits.</p>
                                </div>
                                <span class="badge bg-primary px-3 py-1.5 rounded-pill">action: services</span>
                            </div>

                            <div class="p-3 rounded-4 mb-4" style="background: var(--bg-body); border: 1px solid var(--border-color);">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Request Parameters</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <thead>
                                            <tr class="text-muted border-bottom" style="border-color: var(--border-color) !important;">
                                                <th>Parameter</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>key</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Your unique API Key</td>
                                            </tr>
                                            <tr>
                                                <td><code>action</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Must be <code>services</code></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Request (POST/GET)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-info mb-0" style="font-size: 0.82rem;"><code>{
  "key": "YOUR_API_KEY",
  "action": "services"
}</code></pre>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Response (JSON Array)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-success mb-0" style="font-size: 0.82rem;"><code>[
  {
    "service": 101,
    "name": "Instagram Followers [High Quality]",
    "type": "Default",
    "category": "Instagram Followers",
    "rate": "1500.00",
    "min": "50",
    "max": "50000",
    "dripfeed": false,
    "refill": false,
    "cancel": false,
    "description": "Instant delivery, real accounts."
  }
]</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Place Order -->
                        <div class="tab-pane fade" id="tab-add" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h4 class="fw-bold mb-1">Place Order</h4>
                                    <p class="text-muted small mb-0">Create and dispatch a new order on behalf of your API account.</p>
                                </div>
                                <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill">action: add</span>
                            </div>

                            <div class="p-3 rounded-4 mb-4" style="background: var(--bg-body); border: 1px solid var(--border-color);">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Request Parameters</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <thead>
                                            <tr class="text-muted border-bottom" style="border-color: var(--border-color) !important;">
                                                <th>Parameter</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>key</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Your unique API Key</td>
                                            </tr>
                                            <tr>
                                                <td><code>action</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Must be <code>add</code></td>
                                            </tr>
                                            <tr>
                                                <td><code>service</code></td>
                                                <td>integer</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>The Service ID from the Service List</td>
                                            </tr>
                                            <tr>
                                                <td><code>link</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Target profile or post link</td>
                                            </tr>
                                            <tr>
                                                <td><code>quantity</code></td>
                                                <td>integer</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Number of units to deliver</td>
                                            </tr>
                                            <tr>
                                                <td><code>runs</code></td>
                                                <td>integer</td>
                                                <td><span class="badge bg-secondary">Optional</span></td>
                                                <td>Drip-feed runs count</td>
                                            </tr>
                                            <tr>
                                                <td><code>interval</code></td>
                                                <td>integer</td>
                                                <td><span class="badge bg-secondary">Optional</span></td>
                                                <td>Drip-feed interval in minutes</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Request (POST)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-warning mb-0" style="font-size: 0.82rem;"><code>{
  "key": "YOUR_API_KEY",
  "action": "add",
  "service": 101,
  "link": "https://instagram.com/example",
  "quantity": 1000
}</code></pre>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Response (Success / Error)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-success mb-2" style="font-size: 0.82rem;"><code>// Success:
{
  "order": 14589
}</code></pre>
                                        <pre class="text-danger mb-0" style="font-size: 0.82rem;"><code>// Error:
{
  "error": "Insufficient wallet balance."
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Single Order Status -->
                        <div class="tab-pane fade" id="tab-status" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h4 class="fw-bold mb-1">Check Order Status</h4>
                                    <p class="text-muted small mb-0">Check the real-time processing status, charge, and remaining count of an individual order.</p>
                                </div>
                                <span class="badge bg-success px-3 py-1.5 rounded-pill">action: status</span>
                            </div>

                            <div class="p-3 rounded-4 mb-4" style="background: var(--bg-body); border: 1px solid var(--border-color);">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Request Parameters</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <thead>
                                            <tr class="text-muted border-bottom" style="border-color: var(--border-color) !important;">
                                                <th>Parameter</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>key</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Your unique API Key</td>
                                            </tr>
                                            <tr>
                                                <td><code>action</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Must be <code>status</code></td>
                                            </tr>
                                            <tr>
                                                <td><code>order</code></td>
                                                <td>integer</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>The order ID returned when placing the order</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Request (POST/GET)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-info mb-0" style="font-size: 0.82rem;"><code>{
  "key": "YOUR_API_KEY",
  "action": "status",
  "order": 14589
}</code></pre>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Response (JSON)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-success mb-0" style="font-size: 0.82rem;"><code>{
  "charge": "1500.0000",
  "start_count": "1420",
  "status": "Completed",
  "remains": "0",
  "currency": "NGN"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Multiple Orders Status -->
                        <div class="tab-pane fade" id="tab-orders" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h4 class="fw-bold mb-1">Multiple Orders Status (Batch)</h4>
                                    <p class="text-muted small mb-0">Check the status of up to 100 orders in a single API call.</p>
                                </div>
                                <span class="badge bg-info text-white px-3 py-1.5 rounded-pill">action: orders / status</span>
                            </div>

                            <div class="p-3 rounded-4 mb-4" style="background: var(--bg-body); border: 1px solid var(--border-color);">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Request Parameters</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <thead>
                                            <tr class="text-muted border-bottom" style="border-color: var(--border-color) !important;">
                                                <th>Parameter</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>key</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Your unique API Key</td>
                                            </tr>
                                            <tr>
                                                <td><code>action</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td><code>orders</code> or <code>status</code></td>
                                            </tr>
                                            <tr>
                                                <td><code>orders</code></td>
                                                <td>string | array</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Comma-separated order IDs: <code>14589,14590,14591</code></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Request</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-info mb-0" style="font-size: 0.82rem;"><code>{
  "key": "YOUR_API_KEY",
  "action": "status",
  "orders": "14589,14590"
}</code></pre>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Response (Key-Value Map)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-success mb-0" style="font-size: 0.82rem;"><code>{
  "14589": {
    "charge": "1500.0000",
    "start_count": "1420",
    "status": "Completed",
    "remains": "0",
    "currency": "NGN"
  },
  "14590": {
    "charge": "3000.0000",
    "start_count": "0",
    "status": "In_progress",
    "remains": "2000",
    "currency": "NGN"
  }
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. User Balance -->
                        <div class="tab-pane fade" id="tab-balance" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h4 class="fw-bold mb-1">Check User Balance</h4>
                                    <p class="text-muted small mb-0">Query your current account wallet balance and platform currency code.</p>
                                </div>
                                <span class="badge bg-primary px-3 py-1.5 rounded-pill">action: balance</span>
                            </div>

                            <div class="p-3 rounded-4 mb-4" style="background: var(--bg-body); border: 1px solid var(--border-color);">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Request Parameters</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless small mb-0">
                                        <thead>
                                            <tr class="text-muted border-bottom" style="border-color: var(--border-color) !important;">
                                                <th>Parameter</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>key</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Your unique API Key</td>
                                            </tr>
                                            <tr>
                                                <td><code>action</code></td>
                                                <td>string</td>
                                                <td><span class="badge bg-danger">Required</span></td>
                                                <td>Must be <code>balance</code></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Request</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-info mb-0" style="font-size: 0.82rem;"><code>{
  "key": "YOUR_API_KEY",
  "action": "balance"
}</code></pre>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Example Response (JSON)</h6>
                                    <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                                        <pre class="text-success mb-0" style="font-size: 0.82rem;"><code>{
  "balance": "45250.00",
  "currency": "NGN"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Code Examples Across Languages -->
            <div class="premium-card p-4 p-md-5 border-0 shadow-sm mb-4">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-terminal text-primary"></i> Multi-Language Code Snippets
                </h5>
                <p class="text-muted small mb-4">Copy-paste ready snippets to quickly connect from your applications.</p>

                <ul class="nav nav-pills gap-2 mb-3" id="codeLangTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active rounded-pill px-3 py-1 fw-bold small" data-bs-toggle="tab" data-bs-target="#code-curl" type="button">cURL</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill px-3 py-1 fw-bold small" data-bs-toggle="tab" data-bs-target="#code-python" type="button">Python</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill px-3 py-1 fw-bold small" data-bs-toggle="tab" data-bs-target="#code-php" type="button">PHP</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill px-3 py-1 fw-bold small" data-bs-toggle="tab" data-bs-target="#code-js" type="button">JavaScript / Node.js</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="code-curl">
                        <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                            <pre class="text-light mb-0" style="font-size: 0.85rem;"><code>curl -X POST "{{ url('/api/v1') }}" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "YOUR_API_KEY",
    "action": "balance"
  }'</code></pre>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="code-python">
                        <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                            <pre class="text-light mb-0" style="font-size: 0.85rem;"><code>import requests

url = "{{ url('/api/v1') }}"
payload = {
    "key": "YOUR_API_KEY",
    "action": "services"
}

response = requests.post(url, json=payload)
print(response.json())</code></pre>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="code-php">
                        <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                            <pre class="text-light mb-0" style="font-size: 0.85rem;"><code>&lt;?php
$ch = curl_init('{{ url('/api/v1') }}');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'key' => 'YOUR_API_KEY',
    'action' => 'balance'
]));

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);</code></pre>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="code-js">
                        <div class="p-3 rounded-4" style="background: #0d1117; border: 1px solid rgba(255,255,255,0.08);">
                            <pre class="text-light mb-0" style="font-size: 0.85rem;"><code>const response = await fetch('{{ url('/api/v1') }}', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    key: 'YOUR_API_KEY',
    action: 'balance'
  })
});

const data = await response.json();
console.log(data);</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Standard Response Codes -->
            <div class="premium-card p-4 p-md-5 border-0 shadow-sm">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-primary"></i> HTTP Status & Error Codes
                </h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" style="border-color: var(--border-color);">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th style="width: 15%;">Status Code</th>
                                <th style="width: 25%;">Meaning</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success">200 OK</span></td>
                                <td class="fw-bold">Success</td>
                                <td class="text-muted small">Request executed successfully; output in JSON format.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">400 Bad Request</span></td>
                                <td class="fw-bold">Client Error</td>
                                <td class="text-muted small">Missing parameters, unsupported action, or invalid order specifications.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">401 Unauthorized</span></td>
                                <td class="fw-bold">Invalid Key</td>
                                <td class="text-muted small">API key is missing, malformed, or doesn't match an active user.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">403 Forbidden</span></td>
                                <td class="fw-bold">Access Denied</td>
                                <td class="text-muted small">Account is suspended or email has not yet been verified.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-secondary">404 Not Found</span></td>
                                <td class="fw-bold">Resource Not Found</td>
                                <td class="text-muted small">The requested order ID does not exist or belongs to another user.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info text-white">422 Unprocessable</span></td>
                                <td class="fw-bold">Validation Error</td>
                                <td class="text-muted small">Quantity outside service min/max boundaries or invalid URL format.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">429 Rate Limit</span></td>
                                <td class="fw-bold">Too Many Requests</td>
                                <td class="text-muted small">Exceeded 300 requests per minute limit. Wait a few seconds before retrying.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar (API Key Management & Quick Help) -->
        <div class="col-lg-4 col-xl-3">
            <div class="sticky-top" style="top: 100px;">
                <!-- API Key Card -->
                <div class="premium-card p-4 shadow-sm border-0 mb-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Your API Credentials</h6>

                    @auth
                        @if(Auth::user()->api_key)
                            <div class="p-3 rounded-4 mb-3" style="background: var(--bg-body); border: 1px solid var(--border-color);">
                                <small class="text-muted d-block fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Active API Key</small>
                                <div class="input-group">
                                    <input type="password" id="userApiKeyInput" class="form-control form-control-sm font-monospace" value="{{ Auth::user()->api_key }}" readonly style="background: transparent; color: var(--text-main); border: 0;">
                                    <button class="btn btn-sm btn-link text-muted p-1" type="button" onclick="toggleApiKeyVisibility('userApiKeyInput', this)">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary-custom btn-sm fw-bold py-2 shadow-sm" type="button" onclick="copyApiKey('userApiKeyInput', this)">
                                    <i class="fa-regular fa-copy me-1"></i> Copy API Key
                                </button>
                                <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary btn-sm py-2 fw-bold">
                                    <i class="fa-solid fa-key me-1"></i> Regenerate / Manage Key
                                </a>
                            </div>
                        @else
                            <div class="alert alert-warning small rounded-3 p-3 mb-3 border-0">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> You have not generated an API key yet.
                            </div>
                            <a href="{{ route('profile.index') }}" class="btn btn-primary-custom w-100 py-2 fw-bold shadow-sm">
                                <i class="fa-solid fa-key me-1"></i> Generate API Key
                            </a>
                        @endif
                    @else
                        <div class="p-3 rounded-4 text-center mb-3 bg-primary bg-opacity-10 border border-primary border-opacity-20">
                            <i class="fa-solid fa-lock text-primary fs-3 mb-2"></i>
                            <h6 class="fw-bold mb-1">Authentication Required</h6>
                            <p class="text-muted small mb-3">Sign in to retrieve your private API key and start automating.</p>
                            <div class="d-grid gap-2">
                                <a href="{{ route('login') }}" class="btn btn-primary-custom btn-sm fw-bold py-2">Log In</a>
                                <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm fw-bold py-2">Create Account</a>
                            </div>
                        </div>
                    @endauth
                </div>

                <!-- Upstream Notice Card -->
                <div class="premium-card p-4 shadow-sm border-0 mb-4 bg-info bg-opacity-10 border border-info border-opacity-20">
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <i class="fa-solid fa-circle-info text-info fs-5"></i>
                        <h6 class="fw-bold mb-0 text-info">Need Direct Support?</h6>
                    </div>
                    <p class="text-muted small mb-3">
                        If an external website or downstream client needs assistance connecting to Linkocial, they can reach our engineering team.
                    </p>
                    <a href="{{ route('info.contact') }}" class="btn btn-sm btn-outline-info w-100 rounded-pill fw-bold">
                        <i class="fa-solid fa-headset me-1"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId, btn) {
    const input = document.getElementById(elementId);
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check text-success"></i>';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
    });
}

function copyApiKey(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
    });
}

function toggleApiKeyVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
}
</script>
@endsection
