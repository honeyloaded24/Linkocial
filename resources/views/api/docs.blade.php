@extends('layouts.app')

@section('header_title', 'API Documentation')

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <div class="col-lg-9">
            <!-- Documentation Card -->
            <div class="premium-card shadow-lg mb-4">
                <div class="p-4 p-md-5 border-bottom border-secondary">
                    <h2 class="fw-bold mb-3" style="font-family: var(--heading-font);">Standard SMM API <span class="text-primary-custom">v1.0</span></h2>
                    <p class="text-muted fs-5 mb-4">Integrate our professional SMM services directly into your own platform. We support the industry-standard SMM protocol for seamless connectivity.</p>
                    
                    <div class="p-3 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-20 d-flex align-items-center">
                        <div class="bg-primary-custom p-3 rounded-3 me-3">
                            <i class="fa-solid fa-link text-white fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Base API Endpoint</small>
                            <code class="text-primary-custom fw-bold fs-5">{{ url('/api/v1') }}</code>
                        </div>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Section: Services -->
                    <div class="mb-5 pb-5 border-bottom border-secondary border-opacity-10">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-info bg-opacity-10 text-info p-3 rounded-4">
                                <i class="fa-solid fa-list-ul fs-4"></i>
                            </div>
                            <h4 class="fw-bold mb-0">1. Service List</h4>
                        </div>
                        <p class="text-muted">Retrieve a full list of available services, including IDs, rates, and minimum/maximum limits.</p>
                        <div class="p-4 rounded-4 border border-secondary shadow-sm" style="background: var(--bg-body);">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">POST / GET Request</span>
                                <span class="badge bg-info text-white">JSON</span>
                            </div>
                            <pre class="mb-0 text-info" style="font-size: 0.9rem;">{
    "key": "YOUR_API_KEY",
    "action": "services"
}</pre>
                        </div>
                    </div>

                    <!-- Section: Add Order -->
                    <div class="mb-5 pb-5 border-bottom border-secondary border-opacity-10">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-4">
                                <i class="fa-solid fa-cart-plus fs-4"></i>
                            </div>
                            <h4 class="fw-bold mb-0">2. Place Order</h4>
                        </div>
                        <p class="text-muted">Submit a new order request to the platform.</p>
                        <div class="p-4 rounded-4 border border-secondary shadow-sm" style="background: var(--bg-body);">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">POST Request</span>
                                <span class="badge bg-warning text-dark">JSON</span>
                            </div>
                            <pre class="mb-0 text-warning" style="font-size: 0.9rem;">{
    "key": "YOUR_API_KEY",
    "action": "add",
    "service": "SERVICE_ID",
    "link": "https://instagram.com/p/...",
    "quantity": "1000"
}</pre>
                        </div>
                    </div>

                    <!-- Section: Status -->
                    <div class="mb-5 pb-5 border-bottom border-secondary border-opacity-10">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-4">
                                <i class="fa-solid fa-magnifying-glass-chart fs-4"></i>
                            </div>
                            <h4 class="fw-bold mb-0">3. Order Status</h4>
                        </div>
                        <p class="text-muted">Check the real-time status and remaining count of any order.</p>
                        <div class="p-4 rounded-4 border border-secondary shadow-sm" style="background: var(--bg-body);">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">POST / GET Request</span>
                                <span class="badge bg-success text-white">JSON</span>
                            </div>
                            <pre class="mb-0 text-success" style="font-size: 0.9rem;">{
    "key": "YOUR_API_KEY",
    "action": "status",
    "order": "ORDER_ID"
}</pre>
                        </div>
                    </div>

                    <!-- Section: Balance -->
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-primary bg-opacity-10 text-primary-custom p-3 rounded-4">
                                <i class="fa-solid fa-wallet fs-4"></i>
                            </div>
                            <h4 class="fw-bold mb-0">4. Check Balance</h4>
                        </div>
                        <p class="text-muted">Retrieve your current account balance in the system's currency.</p>
                        <div class="p-4 rounded-4 border border-secondary shadow-sm" style="background: var(--bg-body);">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">POST / GET Request</span>
                                <span class="badge bg-primary text-white">JSON</span>
                            </div>
                            <pre class="mb-0 text-primary-custom" style="font-size: 0.9rem;">{
    "key": "YOUR_API_KEY",
    "action": "balance"
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Auth Info -->
        <div class="col-lg-3">
            <div class="premium-card p-4 shadow-lg sticky-top border-primary border-opacity-10" style="top: 100px;">
                <h6 class="fw-bold text-muted text-uppercase small mb-4">Authentication</h6>
                
                <div class="mb-4 text-center">
                    <p class="small text-muted mb-4">API Keys are now managed in your secure Account Dashboard.</p>
                    <a href="{{ route('profile.index') }}" class="btn bg-primary-custom text-white w-100 py-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-user-shield me-2"></i> Manage My Key
                    </a>
                </div>

                <div class="p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-20 mb-4">
                    <div class="d-flex gap-2 mb-2">
                        <i class="fa-solid fa-circle-info text-info"></i>
                        <span class="text-info small fw-bold">Developer Tip</span>
                    </div>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Keep your keys in .env files and never share them.</p>
                </div>

                <hr class="my-4 border-secondary opacity-10">

                <div class="text-center">
                    <p class="small text-muted mb-3">Need a custom integration?</p>
                    <a href="{{ route('tickets.create') }}" class="btn btn-outline-secondary btn-sm w-100 rounded-pill fw-bold">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
