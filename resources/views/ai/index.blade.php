@extends('layouts.app')

@section('title', 'AI Marketplace — Google AI & Premium Models')

@section('content')
<div class="container py-4">

    <!-- Hero Header -->
    <div class="premium-card p-4 p-md-5 mb-4 position-relative overflow-hidden border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(147, 51, 234, 0.08) 100%); border-radius: 20px;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-primary bg-opacity-10 text-primary fw-bold small mb-3">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Google AI & Premium LLM Services</span>
                </div>
                <h2 class="fw-extrabold text-main mb-2" style="font-size: 2.1rem; letter-spacing: -0.5px;">
                    AI Marketplace
                </h2>
                <p class="text-muted mb-4 lead" style="font-size: 1.05rem;">
                    Instant access to pre-activated Google AI subscriptions, Gemini Pro, cloud compute credits, and AI developer services in Nigerian Naira (₦).
                </p>
                
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('ai.history') }}" class="btn btn-outline-primary rounded-pill px-4 py-2">
                        <i class="fa-solid fa-receipt me-1"></i> My AI Orders
                    </a>
                    <a href="{{ route('wallet.index') }}" class="btn btn-primary rounded-pill px-4 py-2">
                        <i class="fa-solid fa-wallet me-1"></i> Wallet: {{ money(Auth::user()->balance) }}
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <div class="rounded-circle p-4 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 150px; height: 150px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff;">
                    <i class="fa-solid fa-brain" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search & Filters -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form method="GET" action="{{ route('ai.index') }}" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search Google AI, Gemini, Claude, OpenAI..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="all">All AI Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->category }}" {{ $category === $cat->category ? 'selected' : '' }}>
                            {{ $cat->category }} ({{ $cat->count }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary rounded-pill">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="row g-3">
        @forelse($products as $product)
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 transition-hover d-flex flex-column" style="overflow: hidden; border: 1px solid rgba(0,0,0,0.06) !important;">
                    
                    <div class="p-3 pb-0 d-flex justify-content-between align-items-start">
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold text-uppercase small px-2 py-1 rounded-pill" style="font-size: 0.68rem;">
                            {{ $product->category }}
                        </span>

                        @if($product->in_stock)
                            @if($product->available_stock !== null)
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold small px-2 py-1 rounded-pill" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-boxes-stacked me-1"></i> {{ number_format($product->available_stock) }} left
                                </span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold small px-2 py-1 rounded-pill" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-check me-1"></i> In Stock
                                </span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold small px-2 py-1 rounded-pill" style="font-size: 0.68rem;">
                                <i class="fa-solid fa-xmark me-1"></i> 0 left · Sold Out
                            </span>
                        @endif
                    </div>

                    <div class="p-3 text-center">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 60px; height: 60px; background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                            <i class="{{ $product->icon ?: 'fa-solid fa-wand-magic-sparkles' }} fs-3"></i>
                        </div>
                        <h6 class="fw-bold text-main mb-1 text-truncate" title="{{ $product->name }}">
                            {{ $product->name }}
                        </h6>
                        <p class="text-muted small mb-2" style="min-height: 40px; font-size: 0.8rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $product->description ?: 'Instant access credentials and verified API account.' }}
                        </p>
                    </div>

                    <div class="mt-auto p-3 pt-0 border-top pt-3 bg-light bg-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">PRICE (NGN)</small>
                                <span class="fw-extrabold text-primary fs-5">
                                    {{ money($product->effective_selling_price) }}
                                </span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-bolt text-warning"></i> {{ ucfirst($product->delivery_type) }}
                                </span>
                                <small class="fw-bold {{ $product->in_stock ? 'text-success' : 'text-danger' }}" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-layer-group me-1"></i>
                                    @if(!$product->in_stock)
                                        0 in stock
                                    @elseif($product->available_stock !== null)
                                        {{ number_format($product->available_stock) }} in stock
                                    @else
                                        Unlimited in stock
                                    @endif
                                </small>
                            </div>
                        </div>

                        <button type="button" 
                                class="btn btn-primary w-100 rounded-pill fw-bold buy-modal-trigger"
                                data-bs-toggle="modal" 
                                data-bs-target="#buyModal"
                                data-id="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-price="{{ $product->effective_selling_price }}"
                                data-stock="{{ $product->in_stock ? 1 : 0 }}"
                                data-available-stock="{{ $product->available_stock !== null ? $product->available_stock : '' }}"
                                {{ !$product->in_stock ? 'disabled' : '' }}>
                            <i class="fa-solid fa-cart-shopping me-1"></i> {{ $product->in_stock ? 'Order Now' : 'Out of Stock' }}
                        </button>
                    </div>

                </div>
            </div>
        @empty
            <div class="col-12 py-5 text-center">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center p-4 bg-light text-muted mb-3">
                    <i class="fa-solid fa-brain fs-1"></i>
                </div>
                <h5 class="fw-bold">No AI Services Found</h5>
                <p class="text-muted small">No AI services match your search or the catalog is currently being updated.</p>
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('hq.ai.settings') }}" class="btn btn-primary rounded-pill btn-sm">
                        <i class="fa-solid fa-rotate me-1"></i> Sync AI Catalog in Admin HQ
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-center">
        {{ $products->links() }}
    </div>

</div>

<!-- Purchase Modal -->
<div class="modal fade" id="buyModal" tabindex="-1" aria-labelledby="buyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="buyModalLabel">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i> Confirm AI Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="buyForm" method="POST" action="">
                @csrf
                <div class="modal-body py-4">
                    <div class="p-3 rounded-3 bg-light mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-main" id="modalProductName">Google AI Service</div>
                            <div class="text-muted small">Instant activation via portal delivery</div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2.5 py-1.5 rounded-pill" id="modalStockBadge">
                            In Stock
                        </span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Quantity</label>
                        <select name="quantity" id="modalQuantity" class="form-select">
                            <option value="1" selected>1 Unit</option>
                            <option value="2">2 Units</option>
                            <option value="3">3 Units</option>
                            <option value="5">5 Units</option>
                            <option value="10">10 Units</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Unit Price</span>
                        <span class="fw-bold" id="modalUnitPrice">₦0.00</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Total Cost</span>
                        <span class="fw-extrabold text-primary fs-5" id="modalTotalCost">₦0.00</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 mt-2">
                        <span class="text-muted">Your Wallet Balance</span>
                        <span class="fw-bold text-dark">{{ money(Auth::user()->balance) }}</span>
                    </div>

                    <div id="balanceAlert" class="alert alert-warning small d-none mt-3 mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Your wallet balance is insufficient for this purchase.
                        <a href="{{ route('wallet.index') }}" class="fw-bold text-decoration-underline ms-1">Fund Wallet</a>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="modalSubmitBtn" class="btn btn-primary rounded-pill px-4 fw-bold">
                        Confirm Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buyModal = document.getElementById('buyModal');
    const buyForm = document.getElementById('buyForm');
    const modalProductName = document.getElementById('modalProductName');
    const modalStockBadge = document.getElementById('modalStockBadge');
    const modalUnitPrice = document.getElementById('modalUnitPrice');
    const modalTotalCost = document.getElementById('modalTotalCost');
    const modalQuantity = document.getElementById('modalQuantity');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const balanceAlert = document.getElementById('balanceAlert');

    const userBalance = {{ (float) Auth::user()->balance }};
    let currentPrice = 0;

    document.querySelectorAll('.buy-modal-trigger').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const availableStockRaw = this.getAttribute('data-available-stock');
            const availableStock = availableStockRaw !== '' && availableStockRaw !== null ? parseInt(availableStockRaw, 10) : null;
            currentPrice = parseFloat(this.getAttribute('data-price')) || 0;

            buyForm.action = `/ai-marketplace/product/${id}/buy`;
            modalProductName.textContent = name;
            modalQuantity.value = 1;

            if (modalStockBadge) {
                modalStockBadge.textContent = availableStock !== null ? `${availableStock.toLocaleString()} left in stock` : 'In Stock';
            }

            Array.from(modalQuantity.options).forEach(opt => {
                const val = parseInt(opt.value, 10);
                opt.disabled = (availableStock !== null && val > availableStock);
            });

            updateTotals();
        });
    });

    modalQuantity.addEventListener('change', updateTotals);

    function updateTotals() {
        const qty = parseInt(modalQuantity.value) || 1;
        const total = currentPrice * qty;

        modalUnitPrice.textContent = '₦' + currentPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        modalTotalCost.textContent = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (total > userBalance) {
            balanceAlert.classList.remove('d-none');
            modalSubmitBtn.disabled = true;
        } else {
            balanceAlert.classList.add('d-none');
            modalSubmitBtn.disabled = false;
        }
    }
});
</script>
@endsection
