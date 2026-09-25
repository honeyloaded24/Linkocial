@extends('layouts.app')

@section('header_title', 'Purchase Account')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('logs.index') }}" class="btn btn-sm btn-outline-light border-secondary border-opacity-25 rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.8rem; background: rgba(255,255,255,0.03);">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Marketplace</span>
                </a>
                @if($product->category)
                    <span class="badge rounded-pill fw-medium" style="font-size: 0.72rem; background: rgba(99, 102, 241, 0.12); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.25);">
                        {{ $product->category->name }}
                    </span>
                @endif
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 bg-success bg-opacity-25 text-white py-2 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-circle-check text-success"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 bg-danger bg-opacity-25 text-white py-2 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-circle-exclamation text-danger"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <div class="premium-card p-0 overflow-hidden shadow-sm" style="border-radius: 14px; background: var(--bg-surface, #1e293b); border: 1px solid rgba(255, 255, 255, 0.08);">
                <div class="p-3 text-center" style="background: rgba(30, 41, 59, 0.7); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <h5 class="fw-bold mb-0 text-white" style="font-size: 0.95rem; letter-spacing: 0.3px;">Order Confirmation</h5>
                </div>

                <form action="{{ route('logs.purchase') }}" method="POST">
                    @csrf
                    <div class="p-4">
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div class="d-flex align-items-center mb-3 p-3 rounded-3" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06);">
                            {!! $product->getIconHtml(42, 'me-3') !!}
                            <div class="min-w-0 flex-grow-1">
                                <h6 class="fw-bold text-white mb-1" style="font-size: 0.9rem;">{{ $product->name }}</h6>
                                @if($product->description)
                                    <div class="text-muted d-block" style="font-size: 0.78rem; line-height: 1.4;">
                                        {!! $product->renderDescriptionHtml() !!}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php
                            $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
                            $multiplier = 1 + ($markupRate / 100);
                            $price = $product->our_price > 0 ? (float)$product->our_price : ((float)$product->unit_price * $multiplier);
                            $isPriceValid = ($price > 0);
                            $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
                            $priceNgnRaw = $isPriceValid ? number_format($price, 2, '.', '') : '0.00';
                        @endphp

                        @if(!$isPriceValid)
                            <div class="alert alert-warning border-0 bg-warning bg-opacity-15 text-warning p-2.5 rounded-3 d-flex align-items-center gap-2 mb-3" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <div>
                                    <strong>Pricing Temporarily Unavailable:</strong> The price for this product has not been set yet.
                                </div>
                            </div>
                        @endif

                        <div class="row g-2 mb-3 align-items-center p-2.5 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                            <div class="col-6">
                                <span class="text-muted d-block small" style="font-size: 0.72rem;">Unit Price</span>
                                <span class="fw-bold {{ $isPriceValid ? 'text-success' : 'text-danger' }}" style="font-size: 1.05rem;">
                                    {{ $priceFormatted }}
                                </span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block small" style="font-size: 0.72rem;">Available Stock</span>
                                <span class="badge rounded-pill fw-semibold {{ $product->in_stock > 0 ? 'bg-primary bg-opacity-10 text-primary' : 'bg-danger bg-opacity-10 text-danger' }}" style="font-size: 0.74rem;">
                                    {{ $product->in_stock }} pcs
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold mb-1" style="font-size: 0.78rem;">Quantity</label>
                            <input type="number" name="quantity" id="quantityInput" class="form-control text-center fw-bold" value="1" min="1" max="{{ max(1, $product->in_stock) }}" required style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; font-size: 1rem; border-radius: 8px;" {{ !$isPriceValid || $product->in_stock < 1 ? 'disabled' : '' }}>
                        </div>

                        <div class="p-2.5 px-3 rounded-3 d-flex justify-content-between align-items-center" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25);">
                            <span class="text-success fw-semibold" style="font-size: 0.82rem;">Total Cost</span>
                            <span class="fw-bold text-success" id="totalCostDisplay" style="font-size: 1.15rem;">{{ $priceFormatted }}</span>
                        </div>
                    </div>
                    
                    <div class="p-3 border-top border-secondary border-opacity-25 d-grid">
                        <button type="submit" class="btn btn-primary-custom btn-lg fw-semibold shadow" style="font-size: 0.88rem; border-radius: 10px;" {{ ($product->in_stock < 1 || !$isPriceValid) ? 'disabled' : '' }}>
                            @if(!$isPriceValid)
                                Pricing Unavailable
                            @elseif($product->in_stock < 1)
                                Out of Stock
                            @else
                                <i class="fa-solid fa-lock me-1.5" style="font-size: 0.78rem;"></i> Pay & Receive Credentials Instantly
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const unitPrice = parseFloat('{{ $priceNgnRaw }}');
    
    function updateTotal() {
        let qty = parseInt(document.getElementById('quantityInput').value) || 1;
        let total = unitPrice * qty;
        
        let formatted = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(total);
        document.getElementById('totalCostDisplay').innerText = formatted;
    }

    document.getElementById('quantityInput').addEventListener('input', updateTotal);
</script>
@endpush
