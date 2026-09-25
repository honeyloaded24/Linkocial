@extends('layouts.app')

@section('header_title', 'Logs & Accounts')

@push('styles')
<style>
    .marketplace-hero {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(20, 184, 166, 0.04) 100%);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
    }
    .cat-tabs-container {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 6px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .cat-tabs-container::-webkit-scrollbar {
        display: none;
    }
    .cat-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--text-main, #1e293b);
        background: var(--bg-surface, #ffffff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        transition: all 0.18s ease;
        white-space: nowrap;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .cat-tab-btn:hover {
        color: var(--primary-color, #6366f1);
        border-color: var(--primary-color, #6366f1);
        background: var(--bg-surface, #ffffff);
        transform: translateY(-1px);
    }
    .cat-tab-btn.active {
        color: #ffffff !important;
        background: var(--primary-color, #6366f1) !important;
        border-color: var(--primary-color, #6366f1) !important;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.28);
    }
    .cat-tab-btn .badge-count {
        font-size: 0.7rem;
        padding: 2px 7px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.07);
        color: inherit;
    }
    .cat-tab-btn.active .badge-count {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }
    .category-section-header {
        background: var(--bg-surface, #ffffff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 12px 18px;
    }
    .category-section-title {
        color: var(--text-main, #0f172a);
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.4px;
    }
    .category-section-body {
        background: var(--bg-body, #f8fafc);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-top: none;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
        padding: 14px;
    }
    .log-card {
        background: var(--bg-surface, #ffffff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        width: 100%;
    }
    .log-card:hover {
        border-color: var(--primary-color, #6366f1);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.12);
    }
    .product-title-link {
        color: var(--text-main, #0f172a);
        text-decoration: none;
        font-size: 0.92rem;
        font-weight: 700;
        transition: color 0.15s ease;
        display: inline-block;
        max-width: 100%;
    }
    .product-title-link:hover {
        color: var(--primary-color, #6366f1);
        text-decoration: underline;
        cursor: pointer;
    }
    .product-badge-price {
        font-size: 0.84rem;
        font-weight: 800;
        padding: 4px 11px;
        border-radius: 8px;
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.3);
        letter-spacing: 0.2px;
        white-space: nowrap;
    }
    .product-badge-stock {
        font-size: 0.74rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 8px;
        white-space: nowrap;
    }
    .product-badge-stock.in-stock {
        background: rgba(99, 102, 241, 0.09);
        color: #4f46e5;
        border: 1px solid rgba(99, 102, 241, 0.22);
    }
    .product-badge-stock.out-of-stock,
    .product-badge-unavail {
        font-size: 0.74rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 8px;
        background: rgba(239, 68, 68, 0.09);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.22);
        white-space: nowrap;
    }
    .product-badge-type {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 8px;
        border-radius: 6px;
        background: rgba(245, 158, 11, 0.12);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.25);
        white-space: nowrap;
    }
    .log-buy-btn {
        font-size: 0.82rem;
        font-weight: 700;
        padding: 6px 18px;
        border-radius: 20px;
    }
    .search-input-box {
        background: var(--bg-surface, #ffffff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 10px;
        color: var(--text-main, #0f172a);
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    .search-input-box:focus {
        background: var(--bg-surface, #ffffff);
        border-color: var(--primary-color, #6366f1);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        color: var(--text-main, #0f172a);
    }
    .cat-select-dropdown {
        background-color: var(--bg-surface, #ffffff);
        color: var(--text-main, #0f172a);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 9px 14px;
        cursor: pointer;
    }
    .cat-select-dropdown:focus {
        border-color: var(--primary-color, #6366f1);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        outline: none;
    }

    /* Dark Mode Overrides */
    [data-theme="dark"] .cat-tab-btn {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
    }
    [data-theme="dark"] .cat-tab-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }
    [data-theme="dark"] .cat-tab-btn .badge-count {
        background: rgba(255, 255, 255, 0.12);
        color: #e2e8f0;
    }
    [data-theme="dark"] .category-section-header {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .category-section-title {
        color: #f8fafc;
    }
    [data-theme="dark"] .category-section-body {
        background: rgba(15, 23, 42, 0.6);
        border-color: #334155;
    }
    [data-theme="dark"] .log-card {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .log-card:hover {
        border-color: rgba(99, 102, 241, 0.5);
    }
    [data-theme="dark"] .product-title-link {
        color: #f8fafc;
    }
    [data-theme="dark"] .product-badge-price {
        color: #10b981;
    }
    [data-theme="dark"] .product-badge-stock.in-stock {
        color: #a5b4fc;
        background: rgba(99, 102, 241, 0.15);
    }
    [data-theme="dark"] .product-badge-stock.out-of-stock,
    [data-theme="dark"] .product-badge-unavail {
        color: #f87171;
    }
    [data-theme="dark"] .product-badge-type {
        color: #fbbf24;
    }
    [data-theme="dark"] .search-input-box,
    [data-theme="dark"] .cat-select-dropdown {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-2">
    
    @if(session('success'))
        <div class="alert alert-success border-0 py-2 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
            <i class="fa-solid fa-circle-check text-success"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 py-2 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
            <i class="fa-solid fa-circle-exclamation text-danger"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- Top Search & Actions Bar -->
    <div class="row mb-3 g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-12 col-md-6 col-lg-5">
            <form action="{{ route('logs.index') }}" method="GET">
                @if($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory->id }}">
                @endif
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 0.85rem;"></i>
                    <input type="text" name="search" class="form-control search-input-box ps-5 py-2" placeholder="Search accounts by name, platform, country..." value="{{ request('search') }}">
                    @if(request('search'))
                        <a href="{{ route('logs.index', request()->except('search')) }}" class="position-absolute text-muted text-decoration-none" style="top: 50%; right: 14px; transform: translateY(-50%); font-size: 0.85rem;" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Quick Category Select (Dropdown for mobile/compact screens) -->
        <div class="col-6 col-md-3 col-lg-4">
            <select class="form-select cat-select-dropdown" onchange="window.location.href=this.value">
                <option value="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" {{ !$selectedCategory ? 'selected' : '' }}>
                    All Categories ({{ $products->count() }})
                </option>
                @foreach($categories as $category)
                    @php
                        $isSelected = ($selectedCategory && $selectedCategory->id == $category->id);
                        $count = $category->products_count ?? $category->products()->where('is_active', true)->count();
                    @endphp
                    <option value="{{ route('logs.index', array_merge(request()->except('category', 'category_id', 'page'), ['category' => $category->id])) }}" {{ $isSelected ? 'selected' : '' }}>
                        {{ $category->name }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- My Purchases Button -->
        <div class="col-6 col-md-3 col-lg-3 text-end">
            <a href="{{ route('logs.history') }}" class="btn btn-outline-primary w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-semibold shadow-sm text-nowrap rounded-3" style="font-size: 0.82rem;" title="View all your purchased accounts">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>My Purchases</span>
            </a>
        </div>
    </div>

    <!-- Category Tabs Navigation (Clean horizontal pills) -->
    <div class="mb-4">
        <div class="cat-tabs-container">
            <!-- All Pill -->
            <a href="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" class="cat-tab-btn {{ !$selectedCategory ? 'active' : '' }}">
                <i class="fa-solid fa-border-all"></i>
                <span>All</span>
                <span class="badge-count">{{ $products->count() }}</span>
            </a>

            <!-- Individual Categories -->
            @foreach($categories as $cat)
                @php
                    $isTabActive = ($selectedCategory && $selectedCategory->id == $cat->id);
                    $catCount = $cat->products_count ?? $cat->products()->where('is_active', true)->count();
                @endphp
                <a href="{{ route('logs.index', array_merge(request()->except('category', 'category_id', 'page'), ['category' => $cat->id])) }}" class="cat-tab-btn {{ $isTabActive ? 'active' : '' }}">
                    <span>{{ $cat->name }}</span>
                    <span class="badge-count">{{ $catCount }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Active Search or Filter Banner -->
    @if($selectedCategory || request('search'))
        <div class="d-flex align-items-center justify-content-between p-2.5 px-3 mb-3 rounded-3" style="background: rgba(99, 102, 241, 0.08); border: 1.5px solid rgba(99, 102, 241, 0.25); font-size: 0.82rem;">
            <div class="d-flex align-items-center gap-2" style="color: var(--text-main, #0f172a);">
                <i class="fa-solid fa-filter text-primary"></i>
                <span>
                    Showing <strong>{{ $products->count() }}</strong> results 
                    @if($selectedCategory) in <strong>{{ $selectedCategory->name }}</strong> @endif
                    @if(request('search')) matching <strong>"{{ request('search') }}"</strong> @endif
                </span>
            </div>
            <a href="{{ route('logs.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted p-0" style="font-size: 0.75rem;">
                <i class="fa-solid fa-xmark me-1"></i> Clear all filters
            </a>
        </div>
    @endif

    <!-- Hot / Trending Products Section (Shown on default 'All' view) -->
    @if(!$selectedCategory && !request('search') && $hotProducts->count() > 0)
        <div class="mb-4">
            <div class="d-flex align-items-center gap-2 mb-2 px-1">
                <i class="fa-solid fa-fire text-danger" style="font-size: 0.9rem;"></i>
                <span class="fw-bold text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px; color: var(--text-main, #0f172a);">Trending Products</span>
            </div>
            <div class="p-3 rounded-3" style="background: var(--bg-surface, #ffffff); border: 1.5px solid var(--border-color, #e2e8f0);">
                <div class="d-flex flex-column gap-2.5">
                    @foreach($hotProducts as $hot)
                        @include('logs.partials.product_card', ['product' => $hot])
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Categorized Products Listing -->
    @if($products->isEmpty())
        <div class="text-center py-5 rounded-4" style="background: var(--bg-surface, #ffffff); border: 1.5px dashed var(--border-color, #e2e8f0);">
            <i class="fa-solid fa-box-open fa-2x text-muted opacity-50 mb-2"></i>
            <h6 class="fw-semibold mb-1" style="font-size: 0.9rem; color: var(--text-main, #0f172a);">No accounts found</h6>
            <p class="text-muted small mb-3">Try searching with a different keyword or selecting another category.</p>
            <a href="{{ route('logs.index') }}" class="btn btn-sm btn-primary-custom rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                View All Categories
            </a>
        </div>
    @else
        @php
            // Group products by their category name so the layout is properly categorized
            $groupedProducts = $products->groupBy(function($item) {
                return $item->category ? $item->category->name : 'General & Other Accounts';
            });
        @endphp

        @foreach($groupedProducts as $categoryName => $catProducts)
            <div class="category-section mb-4" id="cat-{{ \Illuminate\Support\Str::slug($categoryName) }}">
                <!-- Categorized Section Header -->
                <div class="category-section-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 26px; height: 26px; background: rgba(99, 102, 241, 0.12); color: var(--primary-color, #6366f1); font-size: 0.75rem;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-uppercase category-section-title">
                            {{ $categoryName }}
                        </h6>
                        <span class="badge rounded-pill fw-semibold" style="font-size: 0.68rem; background: rgba(99, 102, 241, 0.1); color: var(--primary-color, #6366f1); border: 1px solid rgba(99, 102, 241, 0.2);">
                            {{ $catProducts->count() }} {{ \Illuminate\Support\Str::plural('product', $catProducts->count()) }}
                        </span>
                    </div>

                    @if(!$selectedCategory && $catProducts->first()?->category)
                        <a href="{{ route('logs.index', ['category' => $catProducts->first()->category->id]) }}" class="text-decoration-none small fw-semibold" style="font-size: 0.74rem; color: var(--primary-color, #6366f1);">
                            Filter this category <i class="fa-solid fa-chevron-right ms-1" style="font-size: 0.6rem;"></i>
                        </a>
                    @endif
                </div>

                <!-- Categorized Products Body -->
                <div class="category-section-body">
                    <div class="d-flex flex-column gap-2.5">
                        @foreach($catProducts as $product)
                            @include('logs.partials.product_card', ['product' => $product])
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @endif

</div>
@endsection
