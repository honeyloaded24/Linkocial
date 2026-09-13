@php
    $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
    $multiplier = 1 + ($markupRate / 100);
    $price = $product->our_price > 0 ? (float)$product->our_price : ((float)$product->unit_price * $multiplier);
    $isPriceValid = ($price > 0);
    $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
@endphp
<div class="log-card p-3 mb-3 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center flex-grow-1 me-3">
        <!-- Icon -->
        <a href="{{ route('logs.show', $product->id) }}" class="text-decoration-none flex-shrink-0" title="{{ $product->name }}">
            {!! $product->getIconHtml(45, 'me-3') !!}
        </a>
        
        <!-- Info -->
        <div class="flex-grow-1">
            <div class="mb-1 d-flex align-items-center flex-wrap gap-2">
                <a href="{{ route('logs.show', $product->id) }}" class="fw-bold log-title-link text-primary text-decoration-none" style="font-size: 0.95rem;" title="{{ $product->name }}">
                    {{ $product->name }}
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($isPriceValid)
                    <span class="badge bg-dark px-3 py-1 rounded-pill fw-bold fs-6">{{ $priceFormatted }}</span>
                @else
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-1 rounded-pill fw-bold" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Price Unavailable
                    </span>
                @endif
                <span class="badge bg-secondary px-3 py-1 rounded-pill fw-bold fs-6">{{ $product->in_stock }} pcs</span>
            </div>
        </div>
    </div>

    <!-- Buy Button -->
    <div>
        @if($product->in_stock > 0 && $isPriceValid)
        <a href="{{ route('logs.show', $product->id) }}" class="btn btn-link text-decoration-none bag-icon p-0 m-0" title="Buy Now">
            <i class="fa-solid fa-bag-shopping fa-2x"></i>
        </a>
        @else
        <button class="btn btn-link text-decoration-none bag-icon p-0 m-0" disabled style="color: #6c757d; cursor: not-allowed; opacity: 0.4;" title="{{ !$isPriceValid ? 'Price currently unavailable' : 'Out of stock' }}">
            <i class="fa-solid fa-bag-shopping fa-2x"></i>
        </button>
        @endif
    </div>
</div>
