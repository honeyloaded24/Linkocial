@extends('layouts.app')

@section('header_title', $product->name)

@push('styles')
<style>
    .log-show-card {
        background: var(--bg-surface, #ffffff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .log-show-header {
        background: var(--bg-body, #f8fafc);
        border-bottom: 1.5px solid var(--border-color, #e2e8f0);
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        padding: 14px 20px;
    }
    .log-desc-body {
        font-size: 0.88rem;
        line-height: 1.7;
        color: var(--text-main, #1e293b);
        word-break: break-word;
    }
    .log-desc-body a {
        color: var(--primary-color, #2563eb) !important;
        text-decoration: underline !important;
        font-weight: 600 !important;
    }
    .log-desc-body a:hover {
        color: var(--primary-hover, #1d4ed8) !important;
    }
    [data-theme="dark"] .log-show-card {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .log-show-header {
        background: rgba(15, 23, 42, 0.7);
        border-color: #334155;
    }
    [data-theme="dark"] .log-desc-body {
        color: #f1f5f9;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="col-12 col-xl-11 col-xxl-10 mx-auto">
        
        <!-- Top Back Navigation & Breadcrumb -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <a href="{{ route('logs.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 0.82rem;">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Marketplace</span>
            </a>

            @if($product->category)
                <span class="badge rounded-pill fw-semibold px-3 py-1.5" style="font-size: 0.75rem; background: rgba(99, 102, 241, 0.12); color: var(--primary-color, #6366f1); border: 1px solid rgba(99, 102, 241, 0.25);">
                    <i class="fa-solid fa-layer-group me-1"></i> {{ $product->category->name }}
                </span>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 py-2.5 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.88rem;">
                <i class="fa-solid fa-circle-check text-success fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 py-2.5 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.88rem;">
                <i class="fa-solid fa-circle-exclamation text-danger fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @php
            $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
            $multiplier = 1 + ($markupRate / 100);
            $price = $product->our_price > 0 ? (float)$product->our_price : ((float)$product->unit_price * $multiplier);
            $isPriceValid = ($price > 0);
            $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
            $priceNgnRaw = $isPriceValid ? number_format($price, 2, '.', '') : '0.00';
            $userBalance = auth()->check() ? (float)auth()->user()->balance : 0;
        @endphp

        <!-- 2-Column Spacious Layout (Eliminates compressed center box and empty side spaces) -->
        <div class="row g-4 align-items-start">
            
            <!-- Left Column: Product Details & Full Description -->
            <div class="col-12 col-lg-7 col-xl-8">
                
                <!-- Main Product Title Card -->
                <div class="log-show-card p-4 mb-4">
                    <div class="d-flex align-items-start gap-3.5 mb-2">
                        {!! $product->getIconHtml(52, 'flex-shrink-0') !!}
                        <div class="flex-grow-1 min-w-0">
                            <h4 class="fw-bold mb-1.5" style="color: var(--text-main, #0f172a); font-size: 1.2rem; line-height: 1.4;">
                                {{ $product->name }}
                            </h4>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                @if($product->category)
                                    <span class="text-muted small fw-medium" style="font-size: 0.78rem;">{{ $product->category->name }}</span>
                                    <span class="text-muted opacity-50">•</span>
                                @endif
                                @if($product->type === 'local')
                                    <span class="badge bg-warning bg-opacity-15 text-warning fw-bold text-uppercase px-2.5 py-1" style="font-size: 0.7rem; letter-spacing: 0.3px;">Instant Local</span>
                                @else
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold text-uppercase px-2.5 py-1" style="font-size: 0.7rem; letter-spacing: 0.3px;">Automated Delivery</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Description & Guidelines Card -->
                <div class="log-show-card overflow-hidden mb-4">
                    <div class="log-show-header d-flex align-items-center justify-content-between">
                        <span class="fw-bold" style="font-size: 0.9rem; color: var(--text-main, #0f172a);">
                            <i class="fa-solid fa-file-lines me-2 text-primary"></i> Product Description & Instructions
                        </span>
                    </div>
                    <div class="p-4 log-desc-body">
                        @if($product->description)
                            {!! $product->renderDescriptionHtml() !!}
                        @else
                            <span class="text-muted fst-italic">No special description provided for this product.</span>
                        @endif
                    </div>
                </div>

                <!-- Warranty & Delivery Guarantee Banner -->
                <div class="p-3.5 px-4 rounded-3 d-flex align-items-center gap-3 mb-4" style="background: rgba(16, 185, 129, 0.08); border: 1.5px solid rgba(16, 185, 129, 0.25);">
                    <i class="fa-solid fa-shield-check text-success fs-3 flex-shrink-0"></i>
                    <div style="font-size: 0.82rem; line-height: 1.5; color: var(--text-main, #0f172a);">
                        <strong class="d-block text-success mb-0.5">Instant Automated Fulfillment</strong>
                        Login credentials, recovery keys, and attachments are delivered directly to your screen immediately after payment confirmation.
                    </div>
                </div>

            </div>

            <!-- Right Column: Order Summary & Checkout Box (Sticky) -->
            <div class="col-12 col-lg-5 col-xl-4" style="position: sticky; top: 20px;">
                <div class="log-show-card overflow-hidden">
                    <div class="p-3.5 text-center text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                        <h5 class="fw-bold mb-0" style="font-size: 1rem; letter-spacing: 0.3px;">Order Summary</h5>
                    </div>

                    <form action="{{ route('logs.purchase') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div class="p-4">
                            <!-- Price & Stock Row -->
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded-3" style="background: var(--bg-body, #f8fafc); border: 1px solid var(--border-color, #e2e8f0);">
                                <div>
                                    <span class="text-muted d-block small" style="font-size: 0.74rem;">Unit Price</span>
                                    <span class="fw-bold {{ $isPriceValid ? 'text-success' : 'text-danger' }}" style="font-size: 1.15rem;">
                                        {{ $priceFormatted }}
                                    </span>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block small" style="font-size: 0.74rem;">Available Stock</span>
                                    @if($product->in_stock > 0)
                                        <span class="badge rounded-pill fw-bold" style="font-size: 0.76rem; background: #64748b; color: #ffffff;">
                                            {{ $product->in_stock }} pcs
                                        </span>
                                    @else
                                        <span class="badge rounded-pill fw-bold bg-danger bg-opacity-10 text-danger" style="font-size: 0.76rem;">
                                            Out of stock
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if(!$isPriceValid)
                                <div class="alert alert-warning border-0 bg-warning bg-opacity-15 text-warning p-2.5 rounded-3 d-flex align-items-center gap-2 mb-3" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <div>
                                        <strong>Pricing Unavailable:</strong> Price not yet configured for this item.
                                    </div>
                                </div>
                            @endif

                            <!-- Quantity Selector -->
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-semibold mb-1" style="font-size: 0.78rem;">Quantity</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary" type="button" onclick="adjustQty(-1)" {{ !$isPriceValid || $product->in_stock < 1 ? 'disabled' : '' }}>
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantity" id="quantityInput" class="form-control text-center fw-bold" value="1" min="1" max="{{ max(1, $product->in_stock) }}" required style="font-size: 1.05rem;" {{ !$isPriceValid || $product->in_stock < 1 ? 'disabled' : '' }}>
                                    <button class="btn btn-outline-secondary" type="button" onclick="adjustQty(1)" {{ !$isPriceValid || $product->in_stock < 1 ? 'disabled' : '' }}>
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Total Cost Box -->
                            <div class="p-3 rounded-3 d-flex justify-content-between align-items-center mb-3" style="background: rgba(16, 185, 129, 0.08); border: 1.5px solid rgba(16, 185, 129, 0.25);">
                                <span class="text-success fw-bold" style="font-size: 0.85rem;">Total Cost</span>
                                <span class="fw-bold text-success" id="totalCostDisplay" style="font-size: 1.25rem;">{{ $priceFormatted }}</span>
                            </div>

                            <!-- Wallet Balance Info -->
                            @auth
                                <div class="d-flex justify-content-between align-items-center px-1 mb-2 text-muted small" style="font-size: 0.76rem;">
                                    <span>Your Balance:</span>
                                    <span class="fw-bold" style="color: var(--text-main);">₦{{ number_format($userBalance, 2) }}</span>
                                </div>
                            @endauth
                        </div>

                        <!-- Submit Button -->
                        <div class="p-3 border-top d-grid" style="border-color: var(--border-color, #e2e8f0) !important;">
                            <button type="submit" class="btn btn-primary-custom btn-lg fw-bold shadow-sm py-2.5" style="font-size: 0.92rem; border-radius: 12px;" {{ ($product->in_stock < 1 || !$isPriceValid) ? 'disabled' : '' }}>
                                @if(!$isPriceValid)
                                    Pricing Unavailable
                                @elseif($product->in_stock < 1)
                                    Out of Stock
                                @else
                                    <i class="fa-solid fa-lock me-1.5"></i> Pay & Receive Credentials
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const unitPrice = parseFloat('{{ $priceNgnRaw }}');
    const maxStock = parseInt('{{ max(1, $product->in_stock) }}');
    
    function updateTotal() {
        let input = document.getElementById('quantityInput');
        let qty = parseInt(input.value) || 1;
        if (qty < 1) qty = 1;
        if (qty > maxStock) qty = maxStock;
        input.value = qty;

        let total = unitPrice * qty;
        let formatted = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(total);
        document.getElementById('totalCostDisplay').innerText = formatted;
    }

    function adjustQty(delta) {
        let input = document.getElementById('quantityInput');
        let current = parseInt(input.value) || 1;
        let next = current + delta;
        if (next >= 1 && next <= maxStock) {
            input.value = next;
            updateTotal();
        }
    }

    document.getElementById('quantityInput').addEventListener('input', updateTotal);
</script>
@endpush
