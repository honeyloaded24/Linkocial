@php
    $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
    $multiplier = 1 + ($markupRate / 100);
    $price = $product->our_price > 0 ? (float)$product->our_price : ((float)$product->unit_price * $multiplier);
    $isPriceValid = ($price > 0);
    $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
    $productUrl = route('logs.show', $product->id);
@endphp
<div class="log-card p-3 px-4 mb-3 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center flex-grow-1 me-3 min-w-0">
        <!-- Icon -->
        <a href="{{ $productUrl }}" class="text-decoration-none flex-shrink-0" title="{{ $product->name }}">
            {!! $product->getIconHtml(45, 'me-3') !!}
        </a>
        
        <!-- Info -->
        <div class="flex-grow-1 min-w-0">
            <div class="mb-1.5">
                <a href="{{ $productUrl }}" class="fw-bold log-title-link text-decoration-none d-block text-truncate" style="font-size: 0.95rem;" title="{{ $product->name }}">
                    {{ $product->name }}
                </a>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                @if($isPriceValid)
                    <span class="badge bg-dark px-3 py-1.5 rounded-pill fw-bold font-monospace" style="font-size: 0.82rem;">{{ $priceFormatted }}</span>
                @else
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Price Unavailable
                    </span>
                @endif
                <span class="badge px-3 py-1.5 rounded-pill fw-bold font-monospace" style="font-size: 0.82rem; background: #64748b; color: #ffffff;">{{ $product->in_stock }} pcs</span>
            </div>
        </div>
    </div>

    <!-- Buy Bag Button -->
    <div class="flex-shrink-0 ms-2">
        @if($product->in_stock > 0 && $isPriceValid)
        <a href="{{ $productUrl }}" class="btn btn-link text-decoration-none bag-icon p-0 m-0 d-inline-flex align-items-center justify-content-center" title="Buy {{ $product->name }}">
            <i class="fa-solid fa-bag-shopping" style="color: #10b981; font-size: 1.85rem;"></i>
        </a>
        @else
        <button class="btn btn-link text-decoration-none bag-icon p-0 m-0 d-inline-flex align-items-center justify-content-center" disabled style="color: #94a3b8; cursor: not-allowed; opacity: 0.4;" title="{{ !$isPriceValid ? 'Price currently unavailable' : 'Out of stock' }}">
            <i class="fa-solid fa-bag-shopping" style="font-size: 1.85rem;"></i>
        </button>
        @endif
    </div>
</div>
