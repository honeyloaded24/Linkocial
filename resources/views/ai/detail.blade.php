@extends('layouts.app')

@section('title', 'AI Order #' . $order->id . ' — Linkocial')

@section('content')
<div class="container py-4">

    <div class="mb-4">
        <a href="{{ route('ai.history') }}" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden p-4 p-md-5">

                <div class="d-flex justify-content-between align-items-center pb-4 border-bottom mb-4">
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold text-uppercase px-2.5 py-1 rounded-pill small mb-2">
                            AI Marketplace Order
                        </span>
                        <h4 class="fw-extrabold text-main mb-0">Order #{{ $order->id }}</h4>
                        <small class="text-muted">{{ $order->created_at->format('M d, Y • h:i A') }}</small>
                    </div>

                    <div>
                        @if($order->status === 'fulfilled')
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold fs-6">
                                <i class="fa-solid fa-circle-check me-1"></i> Fulfilled
                            </span>
                        @elseif($order->status === 'processing')
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2 fw-bold fs-6">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Processing
                            </span>
                        @elseif($order->status === 'refunded')
                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2 fw-bold fs-6">
                                <i class="fa-solid fa-arrow-rotate-left me-1"></i> Refunded
                            </span>
                        @else
                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2 fw-bold fs-6">
                                {{ ucfirst($order->status) }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Product Details -->
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Item Details</h6>
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-brain fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-main mb-0">{{ $order->product_name }}</h6>
                                <small class="text-muted">Quantity: {{ $order->quantity }} unit(s)</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="fw-extrabold text-primary fs-5">{{ money($order->total_price) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Credentials Section -->
                @if($order->delivery_payload)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted text-uppercase small fw-bold mb-0">Delivery & Credentials</h6>
                            <button type="button" id="copyBtn" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fa-regular fa-copy me-1"></i> Copy
                            </button>
                        </div>
                        <pre id="deliveryBox" class="bg-dark text-light p-3 rounded-3 small mb-0" style="white-space: pre-wrap; word-break: break-all; font-family: monospace;">{{ $order->formatted_delivery }}</pre>
                    </div>
                @elseif($order->isPending())
                    <div class="alert alert-info rounded-3 p-3 mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-circle-notch fa-spin fs-4 text-primary me-3"></i>
                        <div>
                            <div class="fw-bold">Activation in Progress</div>
                            <small class="text-muted">Your order is being processed by our automated fulfillment engine. Credentials will appear here as soon as activation completes.</small>
                        </div>
                    </div>
                @endif

                <!-- Instructions -->
                <div class="alert alert-light border rounded-3 p-3 mb-0 small text-muted">
                    <div class="fw-bold text-dark mb-1"><i class="fa-solid fa-shield-halved me-1 text-primary"></i> Need Help with this Order?</div>
                    If you experience any issues accessing your AI service or have questions regarding activation, please open a ticket from your <a href="{{ route('tickets.index') }}" class="text-decoration-underline text-primary">Support Dashboard</a> referencing Order #{{ $order->id }}.
                </div>

            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyBtn = document.getElementById('copyBtn');
    const deliveryBox = document.getElementById('deliveryBox');

    if (copyBtn && deliveryBox) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(deliveryBox.textContent).then(() => {
                copyBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Copy';
                }, 2000);
            });
        });
    }
});
</script>
@endsection
