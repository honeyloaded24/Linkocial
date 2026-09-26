@php
    $price = $product->getRetailPrice();
    $isPriceValid = ($price > 0);
    $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
    $productUrl = route('logs.show', $product->id);
@endphp
<div class="log-card p-2.5 p-sm-3 px-3 px-sm-4 mb-3 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-start align-items-sm-center flex-grow-1 me-2 me-sm-3 min-w-0">
        <!-- Icon -->
        <a href="{{ $productUrl }}" class="text-decoration-none flex-shrink-0 mt-0.5 mt-sm-0" title="{{ $product->name }}">
            {!! $product->getIconHtml(42, 'me-2.5 me-sm-3') !!}
        </a>
        
        <!-- Info -->
        <div class="flex-grow-1 min-w-0">
            <div class="mb-1">
                <a href="{{ $productUrl }}" class="fw-bold log-title-link text-decoration-none d-block" style="font-size: 0.92rem; line-height: 1.38; white-space: normal; word-break: break-word; overflow-wrap: anywhere;" title="{{ $product->name }}">
                    {{ $product->name }}
                </a>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-1.5 gap-sm-2">
                @if($isPriceValid)
                    <span class="badge bg-dark px-2.5 px-sm-3 py-1 py-sm-1.5 rounded-pill fw-bold font-monospace" style="font-size: 0.78rem;">{{ $priceFormatted }}</span>
                @else
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2.5 px-sm-3 py-1 py-sm-1.5 rounded-pill fw-bold" style="font-size: 0.76rem;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Price Unavailable
                    </span>
                @endif
                <span class="badge px-2.5 px-sm-3 py-1 py-sm-1.5 rounded-pill fw-bold font-monospace" style="font-size: 0.78rem; background: #64748b; color: #ffffff;">{{ $product->in_stock }} pcs</span>
            </div>
        </div>
    </div>

    <!-- Buy Bag Button -->
    <div class="flex-shrink-0 ms-2 align-self-start align-self-sm-center mt-1 mt-sm-0">
        @if($product->in_stock > 0 && $isPriceValid)
        <a href="{{ $productUrl }}" class="btn btn-link text-decoration-none bag-icon p-0 m-0 d-inline-flex align-items-center justify-content-center" title="Buy {{ $product->name }}">
            <i class="fa-solid fa-bag-shopping" style="color: #10b981; font-size: 1.75rem;"></i>
        </a>
        @else
        <button class="btn btn-link text-decoration-none bag-icon p-0 m-0 d-inline-flex align-items-center justify-content-center" disabled style="color: #94a3b8; cursor: not-allowed; opacity: 0.4;" title="{{ !$isPriceValid ? 'Price currently unavailable' : 'Out of stock' }}">
            <i class="fa-solid fa-bag-shopping" style="font-size: 1.75rem;"></i>
        </button>
        @endif
    </div>
</div>
