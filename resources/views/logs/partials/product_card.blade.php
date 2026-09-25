@php
    $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
    $multiplier = 1 + ($markupRate / 100);
    $price = $product->our_price > 0 ? (float)$product->our_price : ((float)$product->unit_price * $multiplier);
    $isPriceValid = ($price > 0);
    $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
    $productUrl = route('logs.show', $product->id);
@endphp
<div class="log-card p-3 p-md-3.5 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 w-100">
    <!-- Left: Brand Icon + Title + Category/Local Badges -->
    <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
        <a href="{{ $productUrl }}" class="flex-shrink-0 text-decoration-none" title="View {{ $product->name }}">
            {!! $product->getIconHtml(42) !!}
        </a>
        
        <div class="min-w-0 flex-grow-1">
            <div class="mb-1">
                <a href="{{ $productUrl }}" class="product-title-link fw-bold text-truncate d-block" title="{{ $product->name }}">
                    {{ $product->name }}
                </a>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2">
                @if($product->category)
                    <span class="text-muted small fw-medium" style="font-size: 0.76rem;">{{ $product->category->name }}</span>
                    <span class="text-muted opacity-50" style="font-size: 0.65rem;">•</span>
                @endif

                @if($product->type === 'local')
                    <span class="product-badge-type">
                        Instant Local
                    </span>
                @else
                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold" style="font-size: 0.68rem;">
                        Instant Delivery
                    </span>
                @endif

                @if($product->description)
                    <span class="text-muted opacity-50 d-none d-lg-inline" style="font-size: 0.65rem;">•</span>
                    <span class="text-muted small text-truncate d-none d-lg-inline" style="font-size: 0.75rem; max-width: 320px;">
                        {{ strip_tags($product->description) }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Right: Price, Stock & Buy Action Button -->
    <div class="d-flex align-items-center justify-content-between justify-content-sm-end gap-2.5 flex-shrink-0 pt-2 pt-sm-0 border-top border-sm-0 w-100 w-sm-auto" style="border-color: var(--border-color) !important;">
        <!-- Price Badge -->
        @if($isPriceValid)
            <span class="product-badge-price">
                {{ $priceFormatted }}
            </span>
        @else
            <span class="product-badge-unavail">
                <i class="fa-solid fa-circle-exclamation me-1"></i> Unavailable
            </span>
        @endif

        <!-- Stock Badge -->
        @if($product->in_stock > 0)
            <span class="product-badge-stock in-stock text-nowrap">
                <i class="fa-solid fa-boxes-stacked me-1" style="font-size: 0.68rem;"></i> {{ $product->in_stock }} in stock
            </span>
        @else
            <span class="product-badge-stock out-of-stock text-nowrap">
                Out of stock
            </span>
        @endif

        <!-- Buy / Action Trigger -->
        @if($product->in_stock > 0 && $isPriceValid)
            <a href="{{ $productUrl }}" class="btn btn-sm btn-primary-custom rounded-pill px-3.5 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm text-nowrap log-buy-btn" title="Purchase {{ $product->name }}">
                <i class="fa-solid fa-cart-shopping" style="font-size: 0.72rem;"></i>
                <span>Buy</span>
            </a>
        @else
            <a href="{{ $productUrl }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-medium text-nowrap opacity-60" style="font-size: 0.78rem;" title="View product details">
                View
            </a>
        @endif
    </div>
</div>

