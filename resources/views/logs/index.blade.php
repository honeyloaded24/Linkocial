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
        scroll-behavior: smooth;
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
        cursor: pointer;
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

    /* Asynchronous Loading States */
    .regular-catalogue-wrapper {
        min-height: 250px;
    }
    #marketplace-catalogue-container {
        transition: opacity 0.18s ease;
    }
    #marketplace-catalogue-container.is-loading {
        opacity: 0.45;
        pointer-events: none;
    }
    .catalogue-loading-indicator {
        position: absolute;
        top: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        padding: 6px 16px;
        border-radius: 20px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        z-index: 10;
        display: flex;
        align-items: center;
        color: var(--text-main, #0f172a);
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
    [data-theme="dark"] .catalogue-loading-indicator {
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

    <!-- 1. Marketplace Heading, Introduction and Search Controls -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
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

    <!-- Search Input & Quick Select Dropdown -->
    <div class="row mb-4 g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-12 col-md-7 col-lg-8">
            <form id="marketplace-search-form" action="{{ route('logs.index') }}" method="GET">
                <input type="hidden" name="category" id="search-hidden-category" value="{{ $selectedCategory ? $selectedCategory->id : '' }}">
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 0.85rem;"></i>
                    <input type="text" name="search" id="marketplace-search-input" class="form-control search-input-box ps-5 py-2.5" placeholder="Search accounts by name, platform, country..." value="{{ request('search') }}">
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
            <select id="marketplace-category-select" class="form-select cat-select-dropdown w-100 py-2.5">
                <option value="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" data-category-id="" {{ !$selectedCategory ? 'selected' : '' }}>
                    All Categories ({{ $totalActiveCount }})
                </option>
                @foreach($categories as $category)
                    @php
                        $isSelected = ($selectedCategory && $selectedCategory->id == $category->id);
                        $count = $category->products_count ?? $category->products()->where('is_active', true)->count();
                    @endphp
                    <option value="{{ route('logs.index', array_merge(request()->except('category', 'category_id', 'page'), ['category' => $category->id])) }}" data-category-id="{{ $category->id }}" {{ $isSelected ? 'selected' : '' }}>
                        {{ $category->name }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- 2. Horizontally Sliding Category Bar (Placed ABOVE HOT PICKS 🔥) -->
    <section class="category-navigation-section mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-uppercase text-muted small mb-0" style="letter-spacing: 0.5px;">
                Categories
            </h6>
            <span id="active-category-label" class="text-muted small">
                @if($selectedCategory)
                    Filtered: <strong class="text-primary">{{ $selectedCategory->name }}</strong>
                @else
                    Showing all categories
                @endif
            </span>
        </div>

        <!-- Horizontal scrollable category bar -->
        <div id="marketplace-category-tabs" class="cat-tabs-container">
            <!-- All Pill -->
            <a href="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" 
               class="cat-tab-btn {{ !$selectedCategory ? 'active' : '' }}" 
               data-category-id="" 
               data-category-name="All">
                <i class="fa-solid fa-border-all"></i>
                <span>All</span>
                <span class="badge-count">{{ $totalActiveCount }}</span>
            </a>

            <!-- Individual Category Tabs -->
            @foreach($categories as $cat)
                @php
                    $isTabActive = ($selectedCategory && $selectedCategory->id == $cat->id);
                    $catCount = $cat->products_count ?? $cat->products()->where('is_active', true)->count();
                @endphp
                <a href="{{ route('logs.index', array_merge(request()->except('category', 'category_id', 'page'), ['category' => $cat->id])) }}" 
                   class="cat-tab-btn {{ $isTabActive ? 'active' : '' }}" 
                   data-category-id="{{ $cat->id }}" 
                   data-category-name="{{ $cat->name }}">
                    <span>{{ $cat->name }}</span>
                    <span class="badge-count">{{ $catCount }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- 3. HOT PICKS 🔥 Section (Only Admin-Selected Products, Below Category Bar) -->
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

    <!-- 4. Regular Product Catalogue and 5. Pagination Controls -->
    <section class="regular-catalogue-wrapper position-relative mb-4">
        <div id="marketplace-catalogue-container">
            @include('logs.partials.catalogue_section', [
                'products' => $products,
                'selectedCategory' => $selectedCategory,
                'totalActiveCount' => $totalActiveCount,
                'hotPickIds' => $hotPickIds
            ])
        </div>

        <!-- Subtle Non-blocking Loading Indicator -->
        <div id="catalogue-loading-indicator" class="catalogue-loading-indicator d-none">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
            <span class="small fw-semibold">Loading accounts...</span>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabsContainer = document.getElementById('marketplace-category-tabs');
    const categorySelect = document.getElementById('marketplace-category-select');
    const catalogueContainer = document.getElementById('marketplace-catalogue-container');
    const loadingIndicator = document.getElementById('catalogue-loading-indicator');
    const searchHiddenCategory = document.getElementById('search-hidden-category');
    const activeCategoryLabel = document.getElementById('active-category-label');

    let currentAbortController = null;
    let requestSeq = 0;

    /**
     * Scroll target category button into center of the horizontal scroll container.
     * Prevents vertical page jumps by only scrolling the container's left coordinate.
     */
    function scrollCategoryIntoCenter(buttonEl, smooth = true) {
        if (!tabsContainer || !buttonEl) return;

        const buttonCenter = buttonEl.offsetLeft + (buttonEl.offsetWidth / 2);
        const targetScrollLeft = buttonCenter - (tabsContainer.clientWidth / 2);
        const maxScrollLeft = tabsContainer.scrollWidth - tabsContainer.clientWidth;
        const clampedScrollLeft = Math.max(0, Math.min(targetScrollLeft, maxScrollLeft));

        tabsContainer.scrollTo({
            left: clampedScrollLeft,
            behavior: smooth ? 'smooth' : 'auto'
        });
    }

    // Scroll active category into view on initial page load if one is selected
    const initialActiveTab = tabsContainer ? tabsContainer.querySelector('.cat-tab-btn.active') : null;
    if (initialActiveTab) {
        setTimeout(function () {
            scrollCategoryIntoCenter(initialActiveTab, false);
        }, 50);
    }

    /**
     * Switch category asynchronously without full-page browser reload
     */
    async function switchCategory(targetUrl, categoryId, categoryName, pushToHistory = true) {
        const thisSeq = ++requestSeq;

        // 1. Immediate visual highlighting (optimistic update)
        if (tabsContainer) {
            const allTabs = tabsContainer.querySelectorAll('.cat-tab-btn');
            allTabs.forEach(function (tab) {
                if (tab.getAttribute('data-category-id') === String(categoryId)) {
                    tab.classList.add('active');
                    scrollCategoryIntoCenter(tab, true);
                } else {
                    tab.classList.remove('active');
                }
            });
        }

        // 2. Synchronize quick category dropdown
        if (categorySelect) {
            Array.from(categorySelect.options).forEach(function (opt) {
                if (opt.getAttribute('data-category-id') === String(categoryId)) {
                    opt.selected = true;
                }
            });
        }

        // 3. Update search hidden input & label
        if (searchHiddenCategory) {
            searchHiddenCategory.value = categoryId || '';
        }
        if (activeCategoryLabel) {
            if (categoryId) {
                activeCategoryLabel.innerHTML = 'Filtered: <strong class="text-primary">' + escapeHtml(categoryName) + '</strong>';
            } else {
                activeCategoryLabel.textContent = 'Showing all categories';
            }
        }

        // 4. Update browser URL & history without full page reload
        if (pushToHistory) {
            window.history.pushState({ categoryId: categoryId, categoryName: categoryName, url: targetUrl }, '', targetUrl);
        }

        // 5. Abort in-flight request if user rapidly clicked categories
        if (currentAbortController) {
            currentAbortController.abort();
        }
        currentAbortController = new AbortController();

        // 6. Subtle loading state
        if (catalogueContainer) {
            catalogueContainer.classList.add('is-loading');
        }
        if (loadingIndicator) {
            loadingIndicator.classList.remove('d-none');
        }

        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: currentAbortController.signal
            });

            if (!response.ok) {
                throw new Error('Network response error (status ' + response.status + ')');
            }

            const data = await response.json();

            // Prevent stale race conditions
            if (thisSeq !== requestSeq) {
                return;
            }

            // Update catalogue HTML
            if (catalogueContainer && data.html) {
                catalogueContainer.innerHTML = data.html;
            }
        } catch (err) {
            if (err.name === 'AbortError') {
                return; // User tapped another category before request finished
            }
            console.error('Category async load failed, falling back to page navigation:', err);
            window.location.href = targetUrl;
        } finally {
            if (thisSeq === requestSeq) {
                if (catalogueContainer) {
                    catalogueContainer.classList.remove('is-loading');
                }
                if (loadingIndicator) {
                    loadingIndicator.classList.add('d-none');
                }
            }
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Intercept category tab clicks
    if (tabsContainer) {
        tabsContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.cat-tab-btn');
            if (!btn) return;

            e.preventDefault();
            const targetUrl = btn.getAttribute('href');
            const categoryId = btn.getAttribute('data-category-id') || '';
            const categoryName = btn.getAttribute('data-category-name') || btn.querySelector('span')?.textContent?.trim() || '';

            switchCategory(targetUrl, categoryId, categoryName, true);
        });
    }

    // Intercept quick select dropdown change
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const targetUrl = categorySelect.value;
            const categoryId = selectedOption ? (selectedOption.getAttribute('data-category-id') || '') : '';
            const categoryName = selectedOption ? selectedOption.textContent.replace(/\s*\(\d+\)$/, '').trim() : '';

            switchCategory(targetUrl, categoryId, categoryName, true);
        });
    }

    // Intercept pagination clicks and filter resets inside catalogue container
    if (catalogueContainer) {
        catalogueContainer.addEventListener('click', function (e) {
            const link = e.target.closest('.pagination-link, .reset-filter-btn');
            if (!link) return;

            const targetUrl = link.getAttribute('href');
            if (!targetUrl || targetUrl === '#') return;

            e.preventDefault();

            // Determine category from target URL
            const urlObj = new URL(targetUrl, window.location.origin);
            const catId = urlObj.searchParams.get('category') || urlObj.searchParams.get('category_id') || '';
            const activeTab = tabsContainer ? tabsContainer.querySelector(`.cat-tab-btn[data-category-id="${catId}"]`) : null;
            const catName = activeTab ? activeTab.getAttribute('data-category-name') : 'All';

            switchCategory(targetUrl, catId, catName, true);
        });
    }

    // Handle browser Back & Forward buttons
    window.addEventListener('popstate', function () {
        const url = window.location.href;
        const urlObj = new URL(url);
        const catId = urlObj.searchParams.get('category') || urlObj.searchParams.get('category_id') || '';
        const matchingTab = tabsContainer ? tabsContainer.querySelector(`.cat-tab-btn[data-category-id="${catId}"]`) : null;
        const catName = matchingTab ? matchingTab.getAttribute('data-category-name') : 'All';

        switchCategory(url, catId, catName, false);
    });
});
</script>
@endpush
