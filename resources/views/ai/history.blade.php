@extends('layouts.app')

@section('title', 'My AI Orders — Linkocial')

@section('content')
<div class="container py-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('ai.index') }}" class="text-decoration-none">AI Marketplace</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Order History</li>
                </ol>
            </nav>
            <h3 class="fw-extrabold text-main mb-0">My AI Orders</h3>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('ai.index') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-cart-shopping me-1"></i> Browse AI Services
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
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
                                @if($order->provider_order_id)
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Ref: {{ $order->provider_order_id }}</small>
                                @endif
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
                                    <span class="badge bg-light text-dark rounded-pill px-2.5 py-1">
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
                                <a href="{{ route('ai.detail', $order->id) }}" class="btn btn-sm btn-light rounded-pill px-3" title="View Order Receipt">
                                    <i class="fa-solid fa-eye"></i> Details
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
            <div class="card-footer bg-white border-0 py-3">
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
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
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

    copyDeliveryBtn.addEventListener('click', function () {
        const text = deliveryModalPayload.textContent;
        navigator.clipboard.writeText(text).then(() => {
            copyDeliveryBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
            setTimeout(() => {
                copyDeliveryBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Copy';
            }, 2000);
        });
    });
});
</script>
@endsection
