@extends('layouts.app')

@section('title', 'My AI Orders — Linkocial')
@section('header_title', 'My AI Orders')

@section('content')
<style>
    .ai-top-switcher {
        background: var(--bg-surface, #ffffff);
        border: 1px solid rgba(79, 70, 229, 0.18);
        border-radius: 16px;
        padding: 0.35rem;
    }
    .ai-top-tab {
        border-radius: 12px;
        padding: 0.6rem 1rem;
        font-size: 0.86rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        transition: all 0.18s ease;
        color: var(--text-main, #1e293b);
    }
    .ai-top-tab.active {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
</style>

<div class="container-fluid py-2">

    <!-- Prominent Mobile-First Top Bar: Store vs My AI Orders -->
    <div class="ai-top-switcher d-flex align-items-center gap-2 mb-3 shadow-sm">
        <a href="{{ route('ai.index') }}" class="ai-top-tab flex-fill text-center">
            <i class="fa-solid fa-store text-primary"></i>
            <span>AI Store</span>
        </a>
        <a href="{{ route('ai.history') }}" class="ai-top-tab active flex-fill text-center">
            <i class="fa-solid fa-key text-warning"></i>
            <span>My AI Orders</span>
            <span class="badge rounded-pill bg-warning fw-extrabold" style="font-size: 0.7rem; color: #0f172a !important;">{{ $orders->total() }}</span>
        </a>
    </div>

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('ai.index') }}" class="text-decoration-none">AI Marketplace</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My AI Orders</li>
                </ol>
            </nav>
            <h3 class="fw-extrabold text-main mb-0">My AI Orders & Credentials</h3>
        </div>
        <div class="d-flex gap-2 w-100 w-sm-auto">
            <a href="{{ route('ai.index') }}" class="btn btn-primary rounded-pill px-4 fw-bold w-100 w-sm-auto">
                <i class="fa-solid fa-cart-shopping me-1"></i> Browse AI Store
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Mobile Card View (< 768px) -->
    <div class="d-md-none">
        @forelse($orders as $order)
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <span class="fw-bold text-muted small">Order #{{ $order->id }}</span>
                        <span class="text-muted small d-block" style="font-size: 0.72rem;">
                            {{ $order->created_at->format('M d, Y • H:i') }}
                        </span>
                    </div>
                    <div>
                        @if($order->status === 'fulfilled')
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1 fw-bold">
                                <i class="fa-solid fa-circle-check me-1"></i> Fulfilled
                            </span>
                        @elseif($order->status === 'processing')
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-1 fw-bold">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Processing
                            </span>
                        @elseif($order->status === 'awaiting_manual')
                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2.5 py-1 fw-bold">
                                <i class="fa-solid fa-clock me-1"></i> Awaiting Delivery
                            </span>
                        @elseif($order->status === 'refunded')
                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1 fw-bold">
                                <i class="fa-solid fa-arrow-rotate-left me-1"></i> Refunded
                            </span>
                        @else
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 fw-bold">
                                {{ ucfirst($order->status) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="fw-bold text-main mb-1">{{ $order->product_name }}</h6>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Quantity: <strong>{{ $order->quantity }}</strong></span>
                        <span class="fw-extrabold text-primary fs-6">{{ money($order->total_price) }}</span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    @if($order->delivery_payload)
                        <button type="button"
                                class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-bold flex-grow-1 show-delivery-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#deliveryModal"
                                data-id="{{ $order->id }}"
                                data-product="{{ $order->product_name }}"
                                data-payload="{{ $order->formatted_delivery }}">
                            <i class="fa-solid fa-key me-1"></i> View Credentials
                        </button>
                    @elseif($order->status === 'refunded')
                        <span class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2 disabled flex-grow-1">
                            Refunded to Wallet
                        </span>
                    @else
                        <span class="btn btn-sm btn-outline-warning rounded-pill px-3 py-2 disabled flex-grow-1">
                            <i class="fa-solid fa-clock me-1"></i> Generating Credentials...
                        </span>
                    @endif

                    <a href="{{ route('ai.detail', $order->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-bold">
                        <i class="fa-solid fa-eye me-1"></i> Receipt
                    </a>
                </div>
            </div>
        @empty
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted">
                <i class="fa-solid fa-receipt fs-1 mb-2 text-primary opacity-50"></i>
                <h6 class="fw-bold text-main mb-1">No AI Orders Yet</h6>
                <p class="small mb-3">You haven't placed any AI Marketplace orders yet.</p>
                <a href="{{ route('ai.index') }}" class="btn btn-primary rounded-pill px-4 btn-sm fw-bold">
                    <i class="fa-solid fa-store me-1"></i> Explore AI Store
                </a>
            </div>
        @endforelse

        @if($orders->hasPages())
            <div class="mt-3 d-flex justify-content-center">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- Desktop Table View (>= 768px) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Order ID</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Delivery</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">
                                #{{ $order->id }}
                            </td>
                            <td>
                                <div class="fw-bold text-main">{{ $order->product_name }}</div>
                            </td>
                            <td>{{ $order->quantity }}</td>
                            <td class="fw-bold text-primary">
                                {{ money($order->total_price) }}
                            </td>
                            <td>
                                @if($order->status === 'fulfilled')
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i> Fulfilled
                                    </span>
                                @elseif($order->status === 'processing')
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-1">
                                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Processing
                                    </span>
                                @elseif($order->status === 'awaiting_manual')
                                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2.5 py-1">
                                        <i class="fa-solid fa-clock me-1"></i> Awaiting Delivery
                                    </span>
                                @elseif($order->status === 'refunded')
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1">
                                        <i class="fa-solid fa-arrow-rotate-left me-1"></i> Refunded
                                    </span>
                                @else
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($order->delivery_payload)
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success rounded-pill px-3 show-delivery-btn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deliveryModal"
                                            data-id="{{ $order->id }}"
                                            data-product="{{ $order->product_name }}"
                                            data-payload="{{ $order->formatted_delivery }}">
                                        <i class="fa-solid fa-key me-1"></i> View Credentials
                                    </button>
                                @elseif($order->status === 'refunded')
                                    <span class="text-muted small">Refunded to Wallet</span>
                                @else
                                    <span class="text-muted small">
                                        <i class="fa-solid fa-clock me-1"></i> Generating...
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $order->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('ai.detail', $order->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" title="View Order Receipt">
                                    <i class="fa-solid fa-eye me-1"></i> Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-receipt fs-2 mb-2 d-block"></i>
                                You haven't placed any AI Marketplace orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Delivery Modal -->
<div class="modal fade" id="deliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-key text-success me-2"></i> Account / API Credentials
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-muted small mb-2" id="deliveryModalProduct">Google AI Service</div>
                
                <div class="position-relative">
                    <pre id="deliveryModalPayload" class="bg-dark text-light p-3 rounded-3 small mb-0" style="white-space: pre-wrap; word-break: break-all; max-height: 250px; overflow-y: auto; font-family: monospace;"></pre>
                    <button type="button" id="copyDeliveryBtn" class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 rounded-pill px-3 shadow-sm">
                        <i class="fa-regular fa-copy me-1"></i> Copy
                    </button>
                </div>

                <div class="alert alert-info small mt-3 mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i> Keep your credentials confidential. You can access this anytime from your order history.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deliveryModalProduct = document.getElementById('deliveryModalProduct');
    const deliveryModalPayload = document.getElementById('deliveryModalPayload');
    const copyDeliveryBtn = document.getElementById('copyDeliveryBtn');

    document.querySelectorAll('.show-delivery-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const product = this.getAttribute('data-product');
            const payload = this.getAttribute('data-payload');

            deliveryModalProduct.textContent = product;
            deliveryModalPayload.textContent = payload;
            copyDeliveryBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Copy';
        });
    });

    if (copyDeliveryBtn) {
        copyDeliveryBtn.addEventListener('click', function () {
            const text = deliveryModalPayload.textContent;
            navigator.clipboard.writeText(text).then(() => {
                copyDeliveryBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
                setTimeout(() => {
                    copyDeliveryBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Copy';
                }, 2000);
            });
        });
    }
});
</script>
@endsection
