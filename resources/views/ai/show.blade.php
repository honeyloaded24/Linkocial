@extends('layouts.app')

@section('title', $product->name . ' — AI Marketplace')

@section('content')
<div class="container py-4">

    <div class="mb-4">
        <a href="{{ route('ai.index') }}" class="text-decoration-none text-muted small fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to AI Marketplace
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Product Details -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold text-uppercase small px-3 py-1.5 rounded-pill">
                        {{ $product->category }}
                    </span>

                    @if($product->in_stock)
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold small px-3 py-1.5 rounded-pill">
                            <i class="fa-solid fa-check me-1"></i> In Stock
                        </span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold small px-3 py-1.5 rounded-pill">
                            Sold Out
                        </span>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 68px; height: 68px; background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                        <i class="{{ $product->icon ?: 'fa-solid fa-wand-magic-sparkles' }} fs-2"></i>
                    </div>
                    <div>
                        <h3 class="fw-extrabold text-main mb-1">{{ $product->name }}</h3>
                        <span class="text-muted small">
                            <i class="fa-solid fa-bolt text-warning me-1"></i> {{ ucfirst($product->delivery_type) }} Portal Delivery
                        </span>
                    </div>
                </div>

                <h6 class="fw-bold text-uppercase text-muted small mb-2">Service Description</h6>
                <p class="text-muted mb-4" style="line-height: 1.7;">
                    {{ $product->description ?: 'Instant access credentials and verified AI service subscription delivered directly to your Linkocial order portal.' }}
                </p>

                <div class="row g-3 mt-auto pt-3 border-top">
                    <div class="col-6 col-sm-4">
                        <div class="p-3 rounded-3 bg-light">
                            <small class="text-muted d-block" style="font-size: 0.72rem;">UNIT PRICE</small>
                            <span class="fw-extrabold text-primary fs-5">{{ money($product->effective_selling_price) }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="p-3 rounded-3 bg-light">
                            <small class="text-muted d-block" style="font-size: 0.72rem;">DELIVERY MODE</small>
                            <span class="fw-bold text-dark">{{ ucfirst($product->delivery_type) }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 rounded-3 bg-light">
                            <small class="text-muted d-block" style="font-size: 0.72rem;">AVAILABILITY</small>
                            <span class="fw-bold {{ $product->in_stock ? 'text-success' : 'text-danger' }}">
                                {{ $product->in_stock ? 'Ready for Order' : 'Out of Stock' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3">
                    <i class="fa-solid fa-cart-shopping text-primary me-2"></i> Complete Your Order
                </h5>

                <form method="POST" action="{{ route('ai.buy', $product->id) }}" id="productBuyForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Quantity</label>
                        <select name="quantity" id="showQuantity" class="form-select">
                            <option value="1" selected>1 Unit</option>
                            <option value="2">2 Units</option>
                            <option value="3">3 Units</option>
                            <option value="5">5 Units</option>
                            <option value="10">10 Units</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Unit Price</span>
                        <span class="fw-bold" id="showUnitPrice">{{ money($product->effective_selling_price) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Total Cost</span>
                        <span class="fw-extrabold text-primary fs-5" id="showTotalCost">{{ money($product->effective_selling_price) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 mt-2 mb-3">
                        <span class="text-muted">Your Wallet Balance</span>
                        <span class="fw-bold text-dark">{{ money($user->balance ?? 0) }}</span>
                    </div>

                    <div id="showBalanceAlert" class="alert alert-warning small {{ (($user->balance ?? 0) < $product->effective_selling_price) ? '' : 'd-none' }} mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Your wallet balance is insufficient for this purchase.
                        <a href="{{ route('wallet.index') }}" class="fw-bold text-decoration-underline ms-1">Fund Wallet</a>
                    </div>

                    <button type="submit"
                            id="showSubmitBtn"
                            class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold"
                            {{ (!$product->in_stock || ($user->balance ?? 0) < $product->effective_selling_price) ? 'disabled' : '' }}>
                        <i class="fa-solid fa-bolt me-1"></i>
                        {{ $product->in_stock ? 'Purchase Now' : 'Out of Stock' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const qtySelect = document.getElementById('showQuantity');
    const totalEl = document.getElementById('showTotalCost');
    const submitBtn = document.getElementById('showSubmitBtn');
    const alertEl = document.getElementById('showBalanceAlert');

    const unitPrice = {{ (float) $product->effective_selling_price }};
    const userBalance = {{ (float) ($user->balance ?? 0) }};
    const inStock = {{ $product->in_stock ? 'true' : 'false' }};

    if (qtySelect) {
        qtySelect.addEventListener('change', function () {
            const qty = parseInt(this.value) || 1;
            const total = unitPrice * qty;
            totalEl.textContent = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (total > userBalance) {
                alertEl.classList.remove('d-none');
                submitBtn.disabled = true;
            } else {
                alertEl.classList.add('d-none');
                submitBtn.disabled = !inStock;
            }
        });
    }
});
</script>
@endsection
