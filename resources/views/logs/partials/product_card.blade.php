@php
    $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
    $multiplier = 1 + ($markupRate / 100);
    $price = $product->our_price > 0 ? (float)$product->our_price : ((float)$product->unit_price * $multiplier);
    $priceFormatted = '₦' . number_format($price, 2);
@endphp
<div class="log-card p-3 mb-3 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
        <!-- Icon -->
        {!! $product->getIconHtml(45, 'me-3') !!}
        
        <!-- Info -->
        <div>
            <div class="fw-bold text-primary mb-1 d-flex align-items-center flex-wrap gap-2" style="font-size: 0.95rem;">
                <span>{{ $product->name }}</span>
                @if($product->type === 'local')
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill" style="font-size: 0.65rem;">
                        <i class="fa-solid fa-bolt me-1"></i> Direct Stock
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark px-3 py-1 rounded-pill fw-bold fs-6">{{ $priceFormatted }}</span>
                <span class="badge bg-secondary px-3 py-1 rounded-pill fw-bold fs-6">{{ $product->in_stock }} pcs</span>
            </div>
        </div>
    </div>

    <!-- Buy Button -->
    <div>
        @if($product->in_stock > 0)
        <a href="{{ route('logs.show', $product->id) }}" class="btn btn-link text-decoration-none bag-icon p-0 m-0">
            <i class="fa-solid fa-bag-shopping fa-2x"></i>
        </a>
        @else
        <button class="btn btn-link text-decoration-none bag-icon p-0 m-0" disabled style="color: #6c757d; cursor: not-allowed;" title="Out of stock">
            <i class="fa-solid fa-bag-shopping fa-2x"></i>
        </button>
        @endif
    </div>
</div>
