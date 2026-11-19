@extends('layouts.app')

@section('header_title', $product->name)

@push('styles')
<style>
    .product-detail-card {
        border: 1px solid var(--border-color,#e2e8f0);
        border-radius: 16px;
        background: var(--bg-surface,#fff);
        padding: 2rem;
    }
    .price-display {
        font-size: 2rem;
        font-weight: 900;
        color: #6366f1;
    }
    .info-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color,#e2e8f0); font-size: 0.88rem; }
    .info-row:last-child { border-bottom: none; }
    .tier-table { font-size: 0.82rem; }
    .confirm-modal-price { font-size: 1.5rem; font-weight: 800; color: #6366f1; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <div class="mb-3">
        <a href="{{ route('ai-tools.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i>Back to AI TOOLS
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-4">
        {{-- Product details --}}
        <div class="col-lg-7">
            <div class="product-detail-card">
                <div class="d-flex align-items-center gap-2 mb-1">
                    @if($product->category)
                        <span class="badge bg-light text-main border">{{ $product->category->name }}</span>
                    @endif
                    <span class="badge {{ $product->in_stock ? 'bg-success' : 'bg-danger' }} bg-opacity-10 {{ $product->in_stock ? 'text-success' : 'text-danger' }}">
                        {{ $product->in_stock ? '● In Stock' : '● Out of Stock' }}
                    </span>
                </div>
                <h2 class="fw-bold mb-1">{{ $product->name }}</h2>

                @if($product->description)
                    <p class="text-muted mb-3">{{ $product->description }}</p>
                @endif

                <div class="price-display mb-1">₦{{ number_format($pricing['selling_price'] ?? 0, 2) }}</div>
                <p class="text-muted small mb-3">Price is in Nigerian Naira (NGN)</p>

                {{-- Product info --}}
                <div class="mb-3">
                    @if($product->max_quantity > 1)
                    <div class="info-row">
                        <span class="text-muted">Max Quantity</span>
                        <span class="fw-semibold">{{ $product->max_quantity }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="text-muted">Fulfillment</span>
                        <span class="fw-semibold">{{ $product->fulfillment === 'on_demand' ? 'On-demand delivery' : 'From stock' }}</span>
                    </div>
                    @if($product->fulfillment === 'stock' && $product->stock !== null)
                    <div class="info-row">
                        <span class="text-muted">Available Units</span>
                        <span class="fw-semibold">{{ $product->stock }}</span>
                    </div>
                    @endif
                </div>

                {{-- Price tiers --}}
                @if($product->price_tiers && count($product->price_tiers) > 1)
                <div class="mb-3">
                    <h6 class="fw-bold mb-2 small text-muted text-uppercase">Volume Pricing</h6>
                    <div class="table-responsive">
                        <table class="table table-sm tier-table">
                            <thead><tr><th>Min Qty</th><th>Price per unit</th></tr></thead>
                            <tbody>
                                @foreach($product->price_tiers as $tier)
                                @php $tierPricing = $product->getSellingPriceNgn((int)($tier['min_quantity'] ?? 1)); @endphp
                                <tr>
                                    <td>{{ $tier['min_quantity'] ?? 1 }}+</td>
                                    <td class="fw-bold text-primary">₦{{ number_format($tierPricing['selling_price'] ?? 0, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Purchase form --}}
        <div class="col-lg-5">
            <div class="product-detail-card">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Purchase</h5>

                @if(auth()->user()->balance < ($pricing['selling_price'] ?? 0))
                    <div class="alert alert-warning small">
                        <i class="fa fa-wallet me-1"></i>
                        Your wallet balance (₦{{ number_format(auth()->user()->balance, 2) }}) is below the product price.
                        <a href="{{ route('dashboard') }}" class="alert-link">Fund Wallet</a>
                    </div>
                @endif

                @if($product->isAvailableForPurchase())
                <form id="purchaseForm" action="{{ route('ai-tools.purchase') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if($product->max_quantity > 1)
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Quantity</label>
                        <input type="number" name="quantity" id="qtyInput" class="form-control" value="1" min="1" max="{{ $product->max_quantity }}" required>
                    </div>
                    <div class="mb-3 p-3 rounded" style="background:var(--bg-muted,#f8fafc);border:1px solid var(--border-color,#e2e8f0);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Total</span>
                            <span class="fw-bold text-primary" id="totalDisplay">₦{{ number_format($pricing['selling_price'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                    @else
                    <input type="hidden" name="quantity" value="1">
                    <div class="mb-3 p-3 rounded" style="background:var(--bg-muted,#f8fafc);border:1px solid var(--border-color,#e2e8f0);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Total</span>
                            <span class="fw-bold text-primary">₦{{ number_format($pricing['selling_price'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3 p-2 rounded border small text-muted">
                        <i class="fa fa-wallet me-1"></i>Wallet Balance:
                        <strong class="text-main">₦{{ number_format(auth()->user()->balance, 2) }}</strong>
                    </div>

                    <button type="button" class="btn btn-primary w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#confirmModal"
                        {{ auth()->user()->balance < ($pricing['selling_price'] ?? 0) ? 'disabled' : '' }}>
                        <i class="fa-solid fa-lock me-1"></i>Confirm Purchase
                    </button>
                </form>
                @else
                    <div class="text-center py-3">
                        <i class="fa-solid fa-ban fa-2x text-danger mb-2"></i>
                        <p class="text-danger fw-semibold mb-0">This product is currently unavailable.</p>
                    </div>
                @endif

                <hr class="my-3">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('ai-tools.history') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-history me-1"></i>My Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirm Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Confirm Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-1">You are purchasing:</p>
                <h6 class="fw-bold mb-1">{{ $product->name }}</h6>
                <div class="confirm-modal-price mb-1" id="confirmPrice">₦{{ number_format($pricing['selling_price'] ?? 0, 2) }}</div>
                <p class="text-muted small">This amount will be deducted from your wallet.</p>
                <div class="alert alert-info small mb-0">
                    <i class="fa fa-info-circle me-1"></i>Credentials are delivered instantly or within a few minutes.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="document.getElementById('purchaseForm').submit()">
                    <i class="fa-solid fa-check me-1"></i>Confirm & Pay
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var unitPrice = {{ $pricing['selling_price'] ?? 0 }};
    var qtyInput  = document.getElementById('qtyInput');
    var totalDisp = document.getElementById('totalDisplay');
    var confPrice = document.getElementById('confirmPrice');

    if (!qtyInput) return;

    function fmt(n) {
        return '₦' + n.toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    qtyInput.addEventListener('input', function() {
        var qty   = parseInt(this.value) || 1;
        var total = unitPrice * qty;
        if (totalDisp) totalDisp.textContent = fmt(total);
        if (confPrice) confPrice.textContent  = fmt(total);
    });
})();
</script>
@endpush
