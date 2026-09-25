@php
    $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
    $multiplier = 1 + ($markupRate / 100);
    $price = $product->our_price > 0 ? (float)$product->our_price : ((float)$product->unit_price * $multiplier);
    $isPriceValid = ($price > 0);
    $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
    $productUrl = route('logs.show', $product->id);
@endphp
<div class="log-card p-2.5 px-3 mb-2 d-flex align-items-center justify-content-between position-relative">
    <div class="d-flex align-items-center flex-grow-1 me-3 min-w-0">
        <!-- Product Brand Icon -->
        <a href="{{ $productUrl }}" class="text-decoration-none flex-shrink-0" title="View {{ $product->name }}">
            {!! $product->getIconHtml(38, 'me-3') !!}
        </a>
        
        <!-- Product Info Area -->
        <div class="flex-grow-1 min-w-0">
            <!-- Clickable Title -->
            <div class="mb-1 text-truncate">
                <a href="{{ $productUrl }}" class="product-title-link fw-semibold" title="{{ $product->name }}">
                    {{ $product->name }}
                </a>
            </div>

            <!-- Price and Stock Badges -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                @if($isPriceValid)
                    <span class="product-badge-price">
                        {{ $priceFormatted }}
                    </span>
                @else
                    <span class="product-badge-unavail">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Price Unavailable
                    </span>
                @endif

                @if($product->in_stock > 0)
                    <span class="product-badge-stock in-stock">
                        <i class="fa-solid fa-check me-1" style="font-size: 0.65rem;"></i> {{ $product->in_stock }} in stock
                    </span>
                @else
                    <span class="product-badge-stock out-of-stock">
                        Out of stock
                    </span>
                @endif

                @if($product->type === 'local')
                    <span class="product-badge-type">
                        Instant Local
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Buy / Action Button -->
    <div class="flex-shrink-0 ms-2">
        @if($product->in_stock > 0 && $isPriceValid)
            <a href="{{ $productUrl }}" class="btn btn-sm btn-primary-custom rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm log-buy-btn" title="View details and purchase">
                <i class="fa-solid fa-cart-shopping" style="font-size: 0.72rem;"></i>
                <span>Buy</span>
            </a>
        @else
            <a href="{{ $productUrl }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1 fw-normal opacity-50 text-nowrap" style="font-size: 0.72rem;" title="View product details">
                View
            </a>
        @endif
    </div>
</div>
