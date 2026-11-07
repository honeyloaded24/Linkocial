@extends('layouts.app')

@section('title', 'AI Marketplace — Premium AI Subscriptions & Tools')
@section('header_title', 'AI Marketplace')

@section('content')
@php
    $activeCategory = $category ?? 'all';
    $activeSort = $sort ?? 'popular';
    $onlyInStock = !empty($inStockOnly);
    $quickBrands = [
        ['label' => 'All Brands', 'term' => '', 'icon' => 'fa-solid fa-layer-group'],
        ['label' => 'ChatGPT', 'term' => 'ChatGPT', 'icon' => 'fa-solid fa-robot'],
        ['label' => 'Gemini / Veo', 'term' => 'Gemini', 'icon' => 'fa-brands fa-google'],
        ['label' => 'CapCut Pro', 'term' => 'CapCut', 'icon' => 'fa-solid fa-clapperboard'],
        ['label' => 'Canva Pro', 'term' => 'Canva', 'icon' => 'fa-solid fa-palette'],
        ['label' => 'ElevenLabs', 'term' => 'Elevenlabs', 'icon' => 'fa-solid fa-microphone-lines'],
        ['label' => 'Figma', 'term' => 'Figma', 'icon' => 'fa-brands fa-figma'],
        ['label' => 'Coding & Dev', 'term' => 'Replit', 'icon' => 'fa-solid fa-terminal'],
        ['label' => 'LinkedIn', 'term' => 'LinkedIn', 'icon' => 'fa-brands fa-linkedin'],
        ['label' => 'Telegram', 'term' => 'Telegram', 'icon' => 'fa-brands fa-telegram'],
        ['label' => 'Notion', 'term' => 'Notion', 'icon' => 'fa-solid fa-pen-nib'],
        ['label' => 'VPN & Mail', 'term' => 'VPN', 'icon' => 'fa-solid fa-user-shield'],
    ];
@endphp

<style>
    .ai-category-scroll {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.35rem;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }
    .ai-category-scroll::-webkit-scrollbar {
        height: 4px;
    }
    .ai-category-scroll::-webkit-scrollbar-thumb {
        background: rgba(79, 70, 229, 0.2);
        border-radius: 999px;
    }
    .ai-cat-pill {
        white-space: nowrap;
        border-radius: 999px;
        padding: 0.5rem 1rem;
        font-size: 0.82rem;
        font-weight: 700;
        transition: all 0.2s ease;
        border: 1px solid rgba(79, 70, 229, 0.15);
        background: var(--bg-surface, #fff);
        color: var(--text-main, #1e293b);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }
    .ai-cat-pill:hover {
        border-color: #4f46e5;
        color: #4f46e5;
        transform: translateY(-1px);
    }
    .ai-cat-pill.active {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff !important;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .ai-brand-chip {
        white-space: nowrap;
        border-radius: 999px;
        padding: 0.3rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: rgba(79, 70, 229, 0.04);
        color: var(--text-main, #334155);
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .ai-brand-chip:hover, .ai-brand-chip.active {
        background: #4f46e5;
        color: #fff;
        border-color: #4f46e5;
    }
    .ai-product-card {
        border-radius: 18px !important;
        border: 1px solid rgba(79, 70, 229, 0.1) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .ai-product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(79, 70, 229, 0.12) !important;
        border-color: rgba(79, 70, 229, 0.35) !important;
    }
</style>

<div class="container-fluid py-2">

    <!-- Compact Hero & Quick Actions -->
    <div class="premium-card p-3 p-md-4 mb-4 position-relative overflow-hidden border-0 shadow-sm" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4c1d95 100%); border-radius: 20px; color: #fff;">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill px-3 py-1 fw-bold" style="background: rgba(255,255,255,0.16); color: #e0e7ff; font-size: 0.72rem;">
                        <i class="fa-solid fa-bolt text-warning me-1"></i> Instant Portal Delivery
                    </span>
                    <span class="badge rounded-pill px-3 py-1 fw-bold" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; font-size: 0.72rem;">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> {{ $inStockCount ?? $products->total() }} of {{ $totalCount ?? $products->total() }} Services Available
                    </span>
                </div>
                <h2 class="fw-extrabold text-white mb-1" style="font-size: 1.75rem; letter-spacing: -0.5px;">
                    <i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i>AI & Premium Software Marketplace
                </h2>
                <p class="mb-3" style="color: rgba(255,255,255,0.8); font-size: 0.92rem; max-width: 680px;">
                    Pre-activated ChatGPT Plus, Gemini AI Pro, CapCut Pro, Canva Pro, ElevenLabs, Figma, Coding AI & Developer Subscriptions at wholesale NGN rates.
                </p>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('ai.history') }}" class="btn btn-sm btn-light rounded-pill px-3 py-2 fw-bold text-dark shadow-sm">
                        <i class="fa-solid fa-receipt text-primary me-1"></i> My AI Orders
                    </a>
                    <a href="{{ route('wallet.index') }}" class="btn btn-sm rounded-pill px-3 py-2 fw-bold text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);">
                        <i class="fa-solid fa-wallet text-warning me-1"></i> Balance: {{ money(Auth::user()->balance) }}
                        <span class="ms-1 badge bg-warning text-dark rounded-pill" style="font-size: 0.65rem;">+ Top Up</span>
                    </a>
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('hq.ai.index') }}" class="btn btn-sm rounded-pill px-3 py-2 fw-bold text-white" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
                            <i class="fa-solid fa-sliders me-1"></i> Manage in HQ
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-lg-4 d-none d-lg-flex justify-content-end">
                <div class="d-flex align-items-center gap-3 p-3 rounded-4" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); backdrop-filter: blur(8px);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: linear-gradient(135deg, #6366f1, #a855f7);">
                        <i class="fa-solid fa-robot fs-3 text-white"></i>
                    </div>
                    <div>
                        <div class="small fw-bold text-white-50 text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.6px;">Verified Reseller Store</div>
                        <div class="fw-bold text-white" style="font-size: 0.95rem;">100% Warranty Protection</div>
                        <div class="small" style="color: #a5b4fc; font-size: 0.78rem;">Credentials delivered to your portal</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Smart Category Tabs / Pills -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.6px;">
                <i class="fa-solid fa-compass text-primary me-1"></i> Browse by Category
            </span>
            @if($activeCategory !== 'all' || !empty($search) || $onlyInStock || $activeSort !== 'popular')
                <a href="{{ route('ai.index') }}" class="small fw-bold text-primary text-decoration-none" style="font-size: 0.78rem;">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset All Filters
                </a>
            @endif
        </div>

        <div class="ai-category-scroll">
            <a href="{{ route('ai.index', array_filter(['search' => $search, 'sort' => $activeSort !== 'popular' ? $activeSort : null, 'in_stock_only' => $onlyInStock ? 1 : null])) }}"
               class="ai-cat-pill {{ $activeCategory === 'all' ? 'active' : '' }}">
                <i class="fa-solid fa-border-all"></i>
                <span>All Categories</span>
                <span class="badge rounded-pill {{ $activeCategory === 'all' ? 'bg-white text-primary' : 'bg-primary bg-opacity-10 text-primary' }}" style="font-size: 0.7rem;">
                    {{ $totalCount ?? $products->total() }}
                </span>
            </a>

            @foreach($categories as $cat)
                @php
                    $isCatActive = $activeCategory === $cat->category;
                    $catIcon = \App\Models\AiProduct::categoryIcon($cat->category);
                @endphp
                <a href="{{ route('ai.index', array_filter(['category' => $cat->category, 'search' => $search, 'sort' => $activeSort !== 'popular' ? $activeSort : null, 'in_stock_only' => $onlyInStock ? 1 : null])) }}"
                   class="ai-cat-pill {{ $isCatActive ? 'active' : '' }}">
                    <i class="{{ $catIcon }}"></i>
                    <span>{{ $cat->category }}</span>
                    <span class="badge rounded-pill {{ $isCatActive ? 'bg-white text-primary' : 'bg-primary bg-opacity-10 text-primary' }}" style="font-size: 0.7rem;">
                        {{ $cat->count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Search, Sort, In-Stock Toggle & Quick Brand Chips -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form method="GET" action="{{ route('ai.index') }}" id="aiFilterForm">
            @if($activeCategory !== 'all')
                <input type="hidden" name="category" value="{{ $activeCategory }}">
            @endif

            <div class="row g-2 align-items-center">
                <!-- Instant Live Search -->
                <div class="col-12 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text"
                               name="search"
                               id="liveAiSearch"
                               class="form-control bg-light border-start-0 border-end-0"
                               placeholder="Instant search: ChatGPT, CapCut, Canva, Gemini, ElevenLabs..."
                               value="{{ $search }}"
                               autocomplete="off">
                        <button type="button" id="clearAiSearch" class="input-group-text bg-light border-start-0 text-muted rounded-end-pill pe-3 {{ empty($search) ? 'd-none' : '' }}" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <!-- Sort Order -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3">
                            <i class="fa-solid fa-arrow-up-wide-short"></i>
                        </span>
                        <select name="sort" id="aiSortSelect" class="form-select bg-light border-start-0 rounded-end-pill fw-semibold" onchange="this.form.submit()">
                            <option value="popular" {{ $activeSort === 'popular' ? 'selected' : '' }}>Sort: Featured & Available</option>
                            <option value="price_asc" {{ $activeSort === 'price_asc' ? 'selected' : '' }}>Price: Low to High (₦)</option>
                            <option value="price_desc" {{ $activeSort === 'price_desc' ? 'selected' : '' }}>Price: High to Low (₦)</option>
                            <option value="stock_desc" {{ $activeSort === 'stock_desc' ? 'selected' : '' }}>Most Stock Available</option>
                            <option value="name_asc" {{ $activeSort === 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                        </select>
                    </div>
                </div>

                <!-- In Stock Only Switch -->
                <div class="col-6 col-md-4 col-lg-2 d-flex align-items-center justify-content-center">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="inStockOnlyToggle" name="in_stock_only" value="1" {{ $onlyInStock ? 'checked' : '' }} onchange="this.form.submit()" style="cursor: pointer;">
                        <label class="form-check-label small fw-bold text-main ms-1" for="inStockOnlyToggle" style="cursor: pointer;">
                            In Stock Only
                        </label>
                    </div>
                </div>

                <!-- Apply Button -->
                <div class="col-12 col-md-4 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold">
                        <i class="fa-solid fa-sliders me-1"></i> Apply
                    </button>
                </div>
            </div>

            <!-- Quick Brand Chips -->
            <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top overflow-auto" style="scrollbar-width: none;">
                <span class="text-muted fw-bold flex-shrink-0" style="font-size: 0.7rem;">QUICK FILTER:</span>
                @foreach($quickBrands as $qb)
                    @php
                        $isChipActive = strtolower($search) === strtolower($qb['term']) && ($qb['term'] !== '' || empty($search));
                    @endphp
                    <button type="button"
                            class="ai-brand-chip {{ $isChipActive ? 'active' : '' }}"
                            data-brand-term="{{ $qb['term'] }}">
                        <i class="{{ $qb['icon'] }}"></i>
                        <span>{{ $qb['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </form>
    </div>

    <!-- Live Filter Status Counter -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <div class="small text-muted fw-semibold" id="aiResultSummary">
            Showing <strong class="text-main" id="visibleProductCount">{{ $products->count() }}</strong> of <strong class="text-main">{{ $products->total() }}</strong> AI products
            @if($activeCategory !== 'all')
                in <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill">{{ $activeCategory }}</span>
            @endif
            @if(!empty($search))
                matching <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill">"{{ $search }}"</span>
            @endif
        </div>
        <div class="small text-muted d-none d-sm-block" style="font-size: 0.75rem;">
            <i class="fa-solid fa-circle-info text-primary me-1"></i> Click any product title for full details
        </div>
    </div>

    <!-- Product Grid -->
    <div class="row g-3" id="aiProductGrid">
        @forelse($products as $product)
            @php
                $durationBadge = $product->duration_badge;
            @endphp
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ai-product-col"
                 data-name="{{ strtolower($product->name) }}"
                 data-category="{{ strtolower($product->category) }}"
                 data-desc="{{ strtolower($product->description ?? '') }}"
                 data-instock="{{ $product->in_stock ? '1' : '0' }}">
                <div class="card h-100 border-0 shadow-sm ai-product-card d-flex flex-column" style="overflow: hidden;">

                    <!-- Card Top Badges -->
                    <div class="p-3 pb-0 d-flex justify-content-between align-items-start gap-1 flex-wrap">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold text-uppercase px-2 py-1 rounded-pill" style="font-size: 0.65rem;">
                                {{ $product->category }}
                            </span>
                            @if($durationBadge)
                                <span class="badge bg-dark bg-opacity-10 text-dark fw-bold px-2 py-1 rounded-pill" style="font-size: 0.65rem;">
                                    <i class="fa-regular fa-clock me-1"></i>{{ $durationBadge }}
                                </span>
                            @endif
                        </div>

                        @if($product->in_stock)
                            @if($product->available_stock !== null)
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1 rounded-pill" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-boxes-stacked me-1"></i> {{ number_format($product->available_stock) }} left
                                </span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1 rounded-pill" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-check me-1"></i> In Stock
                                </span>
                            @endif
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1 rounded-pill" style="font-size: 0.68rem;">
                                <i class="fa-solid fa-xmark me-1"></i> 0 left · Sold Out
                            </span>
                        @endif
                    </div>

                    <!-- Card Body -->
                    <div class="p-3 d-flex align-items-start gap-3">
                        <a href="{{ route('ai.show', $product->id) }}" class="text-decoration-none flex-shrink-0">
                            <div class="rounded-4 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg, rgba(79, 70, 229, 0.12) 0%, rgba(124, 58, 237, 0.12) 100%); color: #4f46e5;">
                                <i class="{{ $product->icon ?: 'fa-solid fa-wand-magic-sparkles' }} fs-4"></i>
                            </div>
                        </a>
                        <div class="flex-grow-1 min-w-0">
                            <a href="{{ route('ai.show', $product->id) }}" class="text-decoration-none">
                                <h6 class="fw-bold text-main mb-1" style="font-size: 0.93rem; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="{{ $product->name }}">
                                    {{ $product->name }}
                                </h6>
                            </a>
                            <p class="text-muted small mb-0" style="font-size: 0.77rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $product->description ?: 'Instant access credentials and verified AI subscription delivered to your portal.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="mt-auto p-3 pt-2 border-top bg-light bg-opacity-50">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div>
                                <small class="text-muted d-block fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.4px;">PRICE (NGN)</small>
                                <span class="fw-extrabold text-primary" style="font-size: 1.15rem;">
                                    {{ money($product->effective_selling_price) }}
                                </span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">
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

                        <div class="d-flex gap-2">
                            <button type="button"
                                    class="btn btn-primary btn-sm flex-grow-1 rounded-pill fw-bold py-2 buy-modal-trigger"
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
                            <a href="{{ route('ai.show', $product->id) }}"
                               class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-2 d-inline-flex align-items-center justify-content-center"
                               title="View Full Product Details">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        @empty
            <div class="col-12 py-5 text-center">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center p-4 bg-light text-muted mb-3">
                    <i class="fa-solid fa-brain fs-1"></i>
                </div>
                <h5 class="fw-bold">No AI Services Match Your Filter</h5>
                <p class="text-muted small mb-3">Try clearing your search or switching back to All Categories.</p>
                <a href="{{ route('ai.index') }}" class="btn btn-primary rounded-pill btn-sm px-4">
                    <i class="fa-solid fa-rotate-left me-1"></i> Show All AI Products
                </a>
            </div>
        @endforelse
    </div>

    <!-- Client-Side Instant Filter Empty State -->
    <div id="liveSearchEmptyState" class="py-5 text-center d-none">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center p-3 bg-light text-muted mb-2">
            <i class="fa-solid fa-magnifying-glass fs-3"></i>
        </div>
        <h6 class="fw-bold mb-1">No products on this page match "<span id="liveSearchTermDisplay"></span>"</h6>
        <p class="text-muted small mb-3">Press <strong>Apply</strong> or hit <strong>Enter</strong> to search across all catalog pages.</p>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" onclick="document.getElementById('aiFilterForm').submit();">
            <i class="fa-solid fa-magnifying-glass me-1"></i> Search Entire Catalog
        </button>
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
                modalStockBadge.textContent = availableStock !== null ? `${availableStock.toLocaleString()} left in stock` : 'Unlimited in stock';
            }

            Array.from(modalQuantity.options).forEach(opt => {
                const val = parseInt(opt.value, 10);
                opt.disabled = (availableStock !== null && val > availableStock);
            });

            updateTotals();
        });
    });

    if (modalQuantity) {
        modalQuantity.addEventListener('change', updateTotals);
    }

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

    // Instant live client-side filtering & Quick Brand Chips
    const liveSearchInput = document.getElementById('liveAiSearch');
    const clearSearchBtn = document.getElementById('clearAiSearch');
    const productCols = document.querySelectorAll('.ai-product-col');
    const visibleCountEl = document.getElementById('visibleProductCount');
    const emptyStateEl = document.getElementById('liveSearchEmptyState');
    const emptyTermEl = document.getElementById('liveSearchTermDisplay');
    const brandChips = document.querySelectorAll('.ai-brand-chip');

    function filterProductsOnPage(term) {
        const q = (term || '').trim().toLowerCase();
        let visible = 0;

        productCols.forEach(col => {
            const name = col.getAttribute('data-name') || '';
            const cat = col.getAttribute('data-category') || '';
            const desc = col.getAttribute('data-desc') || '';
            const match = !q || name.includes(q) || cat.includes(q) || desc.includes(q);
            col.classList.toggle('d-none', !match);
            if (match) visible++;
        });

        if (visibleCountEl) {
            visibleCountEl.textContent = visible;
        }
        if (clearSearchBtn) {
            clearSearchBtn.classList.toggle('d-none', !q);
        }
        if (emptyStateEl && productCols.length > 0) {
            emptyStateEl.classList.toggle('d-none', visible > 0);
            if (emptyTermEl) emptyTermEl.textContent = term;
        }
    }

    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', function () {
            filterProductsOnPage(this.value);
            brandChips.forEach(c => {
                c.classList.toggle('active', c.getAttribute('data-brand-term').toLowerCase() === this.value.trim().toLowerCase());
            });
        });
    }

    if (clearSearchBtn && liveSearchInput) {
        clearSearchBtn.addEventListener('click', function () {
            liveSearchInput.value = '';
            filterProductsOnPage('');
            if ('{{ $search }}' !== '') {
                document.getElementById('aiFilterForm').submit();
            }
        });
    }

    brandChips.forEach(chip => {
        chip.addEventListener('click', function () {
            const term = this.getAttribute('data-brand-term') || '';
            if (liveSearchInput) {
                liveSearchInput.value = term;
            }
            document.getElementById('aiFilterForm').submit();
        });
    });
});
</script>
@endsection
