@php
    $price = $product->getRetailPrice();
    $isPriceValid = ($price > 0);
    $priceFormatted = $isPriceValid ? ('₦' . number_format($price, 2)) : 'Price Unavailable';
    $productUrl = route('logs.show', $product->id);
    $isHot = isset($isHotCard) ? $isHotCard : (isset($hotPickIds) && in_array($product->id, $hotPickIds));
@endphp
<div class="col">
    <div class="log-card h-100 p-3 d-flex flex-column justify-content-between position-relative {{ $isHot ? 'border-hot-pick' : '' }}">
        <div>
            <!-- Top Meta: Category & Badges -->
            <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                <span class="badge bg-light text-muted border text-truncate" style="font-size: 0.68rem; max-width: 140px;">
                    {{ $product->category->name ?? 'General' }}
                </span>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    @if($isHot)
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-0.5 rounded-pill fw-bold" style="font-size: 0.68rem;">
                            <i class="fa-solid fa-fire text-danger me-0.5"></i> HOT PICK
                        </span>
                    @endif
                    @if($product->in_stock > 0)
                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-0.5 rounded-pill fw-semibold font-monospace" style="font-size: 0.68rem;">
                            {{ $product->in_stock }} pcs
                        </span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-muted border px-2 py-0.5 rounded-pill fw-semibold" style="font-size: 0.68rem;">
                            Out of stock
                        </span>
                    @endif
                </div>
            </div>

            <!-- Middle: Icon and Product Title -->
            <div class="d-flex align-items-start gap-2.5 mb-3">
                <a href="{{ $productUrl }}" class="flex-shrink-0 text-decoration-none" title="{{ $product->name }}">
                    {!! $product->getIconHtml(38, 'rounded-3 shadow-2xs') !!}
                </a>
                <div class="flex-grow-1 min-w-0">
                    <a href="{{ $productUrl }}" class="fw-bold log-title-link text-decoration-none d-block" style="font-size: 0.9rem; line-height: 1.38;" title="{{ $product->name }}">
                        {{ $product->name }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Bottom: Price and Buy Action -->
        <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-2 mt-auto">
            <div class="min-w-0">
                <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600; letter-spacing: 0.5px;">Price</div>
                @if($isPriceValid)
                    <div class="fw-bold text-success font-monospace text-truncate" style="font-size: 1.05rem; line-height: 1.2;">
                        {{ $priceFormatted }}
                    </div>
                @else
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger py-1 px-2 rounded fw-bold" style="font-size: 0.72rem;">
                        Unavailable
                    </span>
                @endif
            </div>

            <div class="flex-shrink-0">
                @if($product->in_stock > 0 && $isPriceValid)
                    <a href="{{ $productUrl }}" class="btn btn-sm btn-primary-custom px-3 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm text-nowrap" title="Buy {{ $product->name }}">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span>Buy</span>
                    </a>
                @else
                    <button class="btn btn-sm btn-light border px-3 py-1.5 rounded-pill text-muted fw-semibold text-nowrap" disabled title="{{ !$isPriceValid ? 'Price currently unavailable' : 'Out of stock' }}">
                        <i class="fa-solid fa-ban me-1"></i> Sold Out
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
