@extends('layouts.app')

@section('header_title', 'AI TOOLS')

@push('styles')
<style>
    .ai-tools-hero {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        color: #fff;
    }
    .product-card {
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        background: var(--bg-surface, #fff);
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(99,102,241,0.15);
    }
    .product-card-body { flex: 1; padding: 1.25rem; }
    .product-price {
        font-size: 1.4rem;
        font-weight: 800;
        color: #6366f1;
    }
    .badge-instock { background: #d1fae5; color: #065f46; font-size: 0.72rem; }
    .badge-oos     { background: #fee2e2; color: #991b1b; font-size: 0.72rem; }
    .search-box { border-radius: 10px; border: 1.5px solid var(--border-color,#e2e8f0); padding: 0.6rem 1rem; }
    .cat-pill {
        display: inline-block; padding: 0.35rem 1rem; border-radius: 20px;
        border: 1.5px solid var(--border-color,#e2e8f0); font-size: 0.8rem; font-weight: 600;
        cursor: pointer; transition: all 0.15s; text-decoration: none; color: var(--text-muted,#64748b);
    }
    .cat-pill.active, .cat-pill:hover { background: #6366f1; color: #fff; border-color: #6366f1; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    @php
        $myAiOrdersCount = auth()->check() ? \App\Models\AiToolOrder::where('user_id', auth()->id())->count() : 0;
    @endphp

    {{-- Top navigation tabs --}}
    <div class="d-flex align-items-center gap-2 mb-3 p-1 bg-body-tertiary rounded-pill border d-inline-flex">
        <a href="{{ route('ai-tools.index') }}" class="btn btn-primary rounded-pill px-4 btn-sm fw-bold shadow-sm">
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Marketplace
        </a>
        <a href="{{ route('ai-tools.history') }}" class="btn btn-ghost text-muted rounded-pill px-4 btn-sm fw-bold position-relative">
            <i class="fa-solid fa-clock-rotate-left me-1"></i>Order History
            @if($myAiOrdersCount > 0)
                <span class="badge rounded-pill bg-primary text-white ms-1" style="font-size:0.7rem;">{{ $myAiOrdersCount }}</span>
            @endif
        </a>
    </div>

    {{-- Hero --}}
    <div class="ai-tools-hero">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-wand-magic-sparkles fa-2x"></i>
                <div>
                    <h2 class="mb-0 fw-bold">AI TOOLS</h2>
                    <p class="mb-0 opacity-75 small">Premium AI-powered tools & software — delivered instantly</p>
                </div>
            </div>
            @if(auth()->check())
            <a href="{{ route('ai-tools.history') }}" class="btn btn-light rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm text-dark">
                <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                <span>My AI Orders</span>
                @if($myAiOrdersCount > 0)
                    <span class="badge bg-primary text-white rounded-pill">{{ $myAiOrdersCount }}</span>
                @endif
            </a>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Search & Filter Bar --}}
    <form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search AI tools..." class="search-box flex-grow-1" style="max-width:320px;">
        @if(request('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        <button class="btn btn-primary btn-sm px-3" style="border-radius:8px;"><i class="fa fa-search me-1"></i>Search</button>
        @if(request('search') || request('category'))
            <a href="{{ route('ai-tools.index') }}" class="btn btn-outline-secondary btn-sm px-3" style="border-radius:8px;">Clear</a>
        @endif
    </form>

    {{-- Category pills --}}
    @if($categories->count())
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('ai-tools.index', array_diff_key(request()->all(), ['category'=>''])) }}"
           class="cat-pill {{ !request('category') ? 'active' : '' }}">All</a>
        @foreach($categories as $cat)
            <a href="{{ route('ai-tools.index', array_merge(request()->all(), ['category' => $cat->id])) }}"
               class="cat-pill {{ request('category') == $cat->id ? 'active' : '' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>
    @endif

    {{-- Products Grid --}}
    @if($products->count())
        <div class="row g-3">
            @foreach($products as $product)
            @php $pricing = $product->getSellingPriceNgn(1); @endphp
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <div class="product-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge {{ $product->in_stock ? 'badge-instock' : 'badge-oos' }} rounded-pill">
                                {{ $product->in_stock ? 'In Stock' : 'Out of Stock' }}
                            </span>
                            @if($product->is_featured)
                                <span class="badge bg-warning text-dark rounded-pill" style="font-size:0.65rem;">★ Featured</span>
                            @endif
                        </div>
                        <h6 class="fw-bold mb-1" style="font-size:0.9rem;line-height:1.3;">{{ $product->name }}</h6>
                        @if($product->description)
                            <p class="text-muted small mb-2" style="font-size:0.78rem;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $product->description }}</p>
                        @endif
                        @if($product->category)
                            <span class="badge bg-light text-muted border mb-2" style="font-size:0.68rem;">{{ $product->category->name }}</span>
                        @endif
                    </div>
                    <div class="px-3 pb-3">
                        <div class="product-price mb-2">₦{{ number_format($pricing['selling_price'] ?? 0, 2) }}</div>
                        <a href="{{ route('ai-tools.show', $product->id) }}" class="btn btn-primary w-100 btn-sm" style="border-radius:8px;">
                            <i class="fa-solid fa-cart-shopping me-1"></i>Buy Now
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
        @endif

    @else
        <div class="text-center py-5">
            <i class="fa-solid fa-wand-magic-sparkles fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">
                {{ request('search') ? 'No products matching "' . request('search') . '"' : 'No AI Tools available yet' }}
            </h5>
            <p class="text-muted small">Check back soon — new tools are added regularly.</p>
            @if(request('search'))
                <a href="{{ route('ai-tools.index') }}" class="btn btn-outline-primary btn-sm mt-2">View all tools</a>
            @endif
        </div>
    @endif

</div>
@endsection
