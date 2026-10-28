@extends('layouts.app')

@section('header_title', 'Logs & Accounts')

@push('styles')
<style>
    .cat-tabs-container {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 6px;
        scrollbar-width: none;
        -ms-overflow-style: none;
        -webkit-overflow-scrolling: touch;
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
        flex-shrink: 0;
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

    .log-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .log-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
    }
    .border-hot-pick {
        border-color: rgba(239, 68, 68, 0.35) !important;
        background: linear-gradient(180deg, rgba(254, 242, 242, 0.4) 0%, var(--bg-surface, #ffffff) 100%);
    }
    .border-hot-pick:hover {
        border-color: rgba(239, 68, 68, 0.6) !important;
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.12);
    }

    .log-title-link {
        color: var(--text-main, #0f172a) !important;
        transition: color 0.15s ease;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .log-title-link:hover {
        color: var(--primary-color, #6366f1) !important;
        text-decoration: underline !important;
    }

    .search-input-box {
        background: var(--bg-surface, #ffffff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
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
        border-radius: 12px;
        font-size: 0.82rem;
        font-weight: 600;
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
    [data-theme="dark"] .log-card {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .log-card:hover {
        border-color: rgba(99, 102, 241, 0.5);
    }
    [data-theme="dark"] .border-hot-pick {
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.08) 0%, #1e293b 100%);
        border-color: rgba(239, 68, 68, 0.4) !important;
    }
    [data-theme="dark"] .log-title-link {
        color: #f8fafc !important;
    }
    [data-theme="dark"] .log-title-link:hover {
        color: #818cf8 !important;
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
    
    @include('partials.service_ticker', ['service' => 'logs'])

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

    <!-- 1. Marketplace Heading and Introduction -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2" style="color: var(--text-main, #0f172a);">
                <span>Logs & Accounts Marketplace</span>
            </h4>
            <p class="text-muted small mb-0">Browse verified accounts, aged profiles, and developer tools with instant automated delivery.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('logs.history') }}" class="btn btn-outline-primary py-2 px-3 d-inline-flex align-items-center gap-2 fw-semibold rounded-3 shadow-sm text-nowrap" style="font-size: 0.82rem;" title="View all your purchased accounts">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>My Purchases</span>
            </a>
        </div>
    </div>

    <!-- 2. Search and Filtering Controls -->
    <div class="row mb-4 g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-12 col-md-7 col-lg-8">
            <form action="{{ route('logs.index') }}" method="GET">
                @if($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory->id }}">
                @endif
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 0.85rem;"></i>
                    <input type="text" name="search" class="form-control search-input-box ps-5 py-2.5" placeholder="Search accounts by name, platform, country..." value="{{ request('search') }}">
                    @if(request('search'))
                        <a href="{{ route('logs.index', request()->except('search', 'page')) }}" class="position-absolute text-muted text-decoration-none" style="top: 50%; right: 14px; transform: translateY(-50%); font-size: 0.85rem;" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Quick Category Select (Dropdown for quick jumping) -->
        <div class="col-12 col-md-5 col-lg-4">
            <select class="form-select cat-select-dropdown w-100 py-2.5" onchange="window.location.href=this.value">
                <option value="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" {{ !$selectedCategory ? 'selected' : '' }}>
                    All Categories ({{ $totalActiveCount }})
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
    </div>

    <!-- 3. HOT PICKS 🔥 Section (Only Admin-Selected Products) -->
    <section class="hot-picks-section mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger rounded-pill px-2.5 py-1 text-white fw-bold shadow-xs">
                    <i class="fa-solid fa-fire-flame-curved"></i> FEATURED
                </span>
                <h5 class="fw-bold mb-0 text-uppercase tracking-wide" style="font-size: 1.05rem; letter-spacing: 0.5px;">
                    HOT PICKS 🔥
                </h5>
            </div>
            @if($hotProducts->isNotEmpty())
                <span class="text-muted small d-none d-sm-inline">
                    {{ $hotProducts->count() }} {{ \Illuminate\Support\Str::plural('item', $hotProducts->count()) }} handpicked by team
                </span>
            @endif
        </div>

        @if($hotProducts->isNotEmpty())
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                @foreach($hotProducts as $hotProd)
                    @include('logs.partials.product_card', ['product' => $hotProd, 'isHotCard' => true])
                @endforeach
            </div>
        @else
            <div class="p-4 text-center rounded-4 border border-dashed text-muted bg-light-subtle">
                <i class="fa-solid fa-fire text-secondary opacity-40 fa-lg mb-1.5 d-block"></i>
                <span class="small fw-semibold d-block">No products currently featured in HOT PICKS.</span>
                <span class="small text-muted">Browse our full catalogue by category below.</span>
            </div>
        @endif
    </section>

    <!-- 4. Regular Marketplace by Categories -->
    <section class="regular-marketplace-section mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-uppercase text-muted small mb-0" style="letter-spacing: 0.5px;">
                Explore Categories
            </h6>
            @if($selectedCategory)
                <a href="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" class="btn btn-sm btn-link text-primary p-0 text-decoration-none small">
                    <i class="fa-solid fa-arrow-rotate-left me-1"></i> Reset to All
                </a>
            @endif
        </div>

        <!-- Horizontally Scrollable Category Chips -->
        <div class="cat-tabs-container mb-3">
            <!-- All Pill -->
            <a href="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" class="cat-tab-btn {{ !$selectedCategory ? 'active' : '' }}">
                <i class="fa-solid fa-border-all"></i>
                <span>All</span>
                <span class="badge-count">{{ $totalActiveCount }}</span>
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

        <!-- Active Filter Banner (if filtering by category or searching) -->
        @if($selectedCategory || request('search'))
            <div class="d-flex align-items-center justify-content-between p-2.5 px-3 mb-3 rounded-3" style="background: rgba(99, 102, 241, 0.08); border: 1.5px solid rgba(99, 102, 241, 0.25); font-size: 0.82rem;">
                <div class="d-flex align-items-center gap-2" style="color: var(--text-main, #0f172a);">
                    <i class="fa-solid fa-filter text-primary"></i>
                    <span>
                        Showing <strong>{{ $products->total() }}</strong> accounts
                        @if($selectedCategory) in category <strong>{{ $selectedCategory->name }}</strong>@endif
                        @if(request('search')) matching <strong>"{{ request('search') }}"</strong>@endif
                    </span>
                </div>
                <a href="{{ route('logs.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted p-0" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-xmark me-1"></i> Clear all
                </a>
            </div>
        @endif

        <!-- 5. Product Grid -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
            @forelse($products as $product)
                @include('logs.partials.product_card', ['product' => $product])
            @empty
                <div class="col-12">
                    <div class="text-center py-5 rounded-4" style="background: var(--bg-surface, #ffffff); border: 1.5px dashed var(--border-color, #e2e8f0);">
                        <i class="fa-solid fa-box-open fa-2x text-muted opacity-50 mb-2"></i>
                        <h6 class="fw-semibold mb-1" style="font-size: 0.9rem; color: var(--text-main, #0f172a);">No accounts found</h6>
                        <p class="text-muted small mb-3">Try searching with a different keyword or selecting another category.</p>
                        <a href="{{ route('logs.index') }}" class="btn btn-sm btn-primary-custom rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                            View All Categories
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- 6. Previous / Page Indicator / Next Controls -->
        @if($products->total() > 0)
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
                <div class="text-muted small">
                    Showing <strong>{{ $products->firstItem() ?? 0 }}</strong> to <strong>{{ $products->lastItem() ?? 0 }}</strong> of <strong>{{ $products->total() }}</strong> accounts
                    @if($selectedCategory) in <strong>{{ $selectedCategory->name }}</strong>@endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($products->onFirstPage())
                        <button class="btn btn-sm btn-outline-secondary px-3 py-1.5 rounded-pill disabled" disabled>
                            <i class="fa-solid fa-chevron-left me-1"></i> Previous
                        </button>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="btn btn-sm btn-outline-primary px-3 py-1.5 rounded-pill">
                            <i class="fa-solid fa-chevron-left me-1"></i> Previous
                        </a>
                    @endif

                    <span class="px-3 py-1 rounded-pill bg-light border text-muted small fw-semibold font-monospace">
                        Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
                    </span>

                    @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="btn btn-sm btn-outline-primary px-3 py-1.5 rounded-pill">
                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                        </a>
                    @else
                        <button class="btn btn-sm btn-outline-secondary px-3 py-1.5 rounded-pill disabled" disabled>
                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </section>

</div>
@endsection
