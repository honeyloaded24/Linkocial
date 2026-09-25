@extends('layouts.app')

@section('header_title', 'Logs & Accounts')

@push('styles')
<style>
    .marketplace-hero {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(20, 184, 166, 0.04) 100%);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 14px;
    }
    .cat-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        text-decoration: none;
        color: #94a3b8;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.07);
        transition: all 0.18s ease;
        white-space: nowrap;
    }
    .cat-tab-btn:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(255, 255, 255, 0.14);
        transform: translateY(-1px);
    }
    .cat-tab-btn.active {
        color: #ffffff;
        background: var(--primary-color, #6366f1);
        border-color: var(--primary-color, #6366f1);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .cat-tab-btn .badge-count {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.25);
    }
    .category-section-header {
        background: rgba(30, 41, 59, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 11px 16px;
    }
    .category-section-body {
        background: var(--bg-surface, rgba(15, 23, 42, 0.5));
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-top: none;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
        padding: 14px;
    }
    .log-card {
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    .log-card:hover {
        background: rgba(255, 255, 255, 0.045);
        border-color: rgba(99, 102, 241, 0.3);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .product-title-link {
        color: #f1f5f9;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
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
        font-size: 0.76rem;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 6px;
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.25);
        letter-spacing: 0.3px;
    }
    .product-badge-stock {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .product-badge-stock.in-stock {
        background: rgba(99, 102, 241, 0.1);
        color: #a5b4fc;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }
    .product-badge-stock.out-of-stock {
        background: rgba(239, 68, 68, 0.08);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.18);
    }
    .product-badge-unavail {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 6px;
        background: rgba(239, 68, 68, 0.08);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.18);
    }
    .product-badge-type {
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2px 6px;
        border-radius: 4px;
        background: rgba(245, 158, 11, 0.1);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    .search-input-box {
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        color: #f8fafc;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    .search-input-box:focus {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(99, 102, 241, 0.4);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        color: #fff;
    }
    .cat-select-dropdown {
        background-color: #1e293b;
        color: #f1f5f9;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 9px 14px;
        cursor: pointer;
    }
    .cat-select-dropdown:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        outline: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-2">
    
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success bg-opacity-25 text-white py-2 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
            <i class="fa-solid fa-circle-check text-success"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger bg-opacity-25 text-white py-2 px-3 rounded-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
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
            <a href="{{ route('logs.history') }}" class="btn btn-outline-light border-secondary border-opacity-25 w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-semibold shadow-sm text-nowrap rounded-3" style="font-size: 0.82rem; background: rgba(255,255,255,0.03);" title="View all your purchased accounts">
                <i class="fa-solid fa-clock-rotate-left text-primary-custom"></i>
                <span>My Purchases</span>
            </a>
        </div>
    </div>

    <!-- Category Tabs Navigation (Clean horizontal pills) -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 overflow-auto pb-2" style="scrollbar-width: thin;">
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
        <div class="d-flex align-items-center justify-content-between p-2.5 px-3 mb-3 rounded-3" style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.2); font-size: 0.82rem;">
            <div class="d-flex align-items-center gap-2 text-white">
                <i class="fa-solid fa-filter text-primary-custom"></i>
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
                <span class="fw-bold text-white text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px;">Trending Products</span>
            </div>
            <div class="p-2.5 rounded-3" style="background: rgba(255, 255, 255, 0.015); border: 1px solid rgba(255, 255, 255, 0.05);">
                <div class="row g-2">
                    @foreach($hotProducts as $hot)
                        <div class="col-12 col-md-6 col-xl-4">
                            @include('logs.partials.product_card', ['product' => $hot])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Categorized Products Listing -->
    @if($products->isEmpty())
        <div class="text-center py-5 rounded-4" style="background: rgba(255, 255, 255, 0.02); border: 1px dashed rgba(255, 255, 255, 0.08);">
            <i class="fa-solid fa-box-open fa-2x text-muted opacity-25 mb-2"></i>
            <h6 class="text-white fw-semibold mb-1" style="font-size: 0.9rem;">No accounts found</h6>
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
                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 24px; height: 24px; background: rgba(99, 102, 241, 0.15); color: #818cf8; font-size: 0.72rem;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-white text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            {{ $categoryName }}
                        </h6>
                        <span class="badge rounded-pill fw-medium" style="font-size: 0.68rem; background: rgba(255, 255, 255, 0.07); color: #94a3b8;">
                            {{ $catProducts->count() }} {{ \Illuminate\Support\Str::plural('product', $catProducts->count()) }}
                        </span>
                    </div>

                    @if(!$selectedCategory && $catProducts->first()?->category)
                        <a href="{{ route('logs.index', ['category' => $catProducts->first()->category->id]) }}" class="text-decoration-none text-muted small fw-medium" style="font-size: 0.72rem;">
                            Filter this category <i class="fa-solid fa-chevron-right ms-1" style="font-size: 0.6rem;"></i>
                        </a>
                    @endif
                </div>

                <!-- Categorized Products Body -->
                <div class="category-section-body">
                    <div class="row g-2">
                        @foreach($catProducts as $product)
                            <div class="col-12 col-lg-6">
                                @include('logs.partials.product_card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @endif

</div>
@endsection
