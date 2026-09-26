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

    .log-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        width: 100%;
    }
    .log-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }
    .log-title-link {
        color: var(--primary-color, #0d6efd) !important;
        transition: opacity 0.15s ease;
    }
    .log-title-link:hover {
        text-decoration: underline !important;
        opacity: 0.85;
    }
    .bag-icon {
        cursor: pointer;
        transition: transform 0.18s ease;
    }
    .bag-icon:hover {
        transform: scale(1.1);
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
    [data-theme="dark"] .log-card {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .log-card:hover {
        border-color: rgba(99, 102, 241, 0.5);
    }
    [data-theme="dark"] .log-title-link {
        color: #60a5fa !important;
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

    <!-- Clean Vertical List of Products (Matches Screenshot) -->
    <div class="product-list">
        @forelse($products as $product)
            @include('logs.partials.product_card', ['product' => $product])
        @empty
            <div class="text-center py-5 rounded-4" style="background: var(--bg-surface, #ffffff); border: 1.5px dashed var(--border-color, #e2e8f0);">
                <i class="fa-solid fa-box-open fa-2x text-muted opacity-50 mb-2"></i>
                <h6 class="fw-semibold mb-1" style="font-size: 0.9rem; color: var(--text-main, #0f172a);">No accounts found</h6>
                <p class="text-muted small mb-3">Try searching with a different keyword or selecting another category.</p>
                <a href="{{ route('logs.index') }}" class="btn btn-sm btn-primary-custom rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                    View All Categories
                </a>
            </div>
        @endforelse
    </div>

</div>
@endsection
