<!-- Active Filter Banner (if filtering by category or searching) -->
@if($selectedCategory || request('search'))
    <div class="d-flex align-items-center justify-content-between p-2.5 px-3 mb-3 rounded-3 filter-status-banner" style="background: rgba(99, 102, 241, 0.08); border: 1.5px solid rgba(99, 102, 241, 0.25); font-size: 0.82rem;">
        <div class="d-flex align-items-center gap-2" style="color: var(--text-main, #0f172a);">
            <i class="fa-solid fa-filter text-primary"></i>
            <span>
                Showing <strong>{{ $products->total() }}</strong> accounts
                @if($selectedCategory) in category <strong>{{ $selectedCategory->name }}</strong>@endif
                @if(request('search')) matching <strong>"{{ request('search') }}"</strong>@endif
            </span>
        </div>
        <a href="{{ route('logs.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted p-0 reset-filter-btn" style="font-size: 0.75rem;">
            <i class="fa-solid fa-xmark me-1"></i> Clear all
        </a>
    </div>
@endif

<!-- Product Grid -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
    @forelse($products as $product)
        @include('logs.partials.product_card', ['product' => $product])
    @empty
        <div class="col-12">
            <div class="text-center py-5 rounded-4" style="background: var(--bg-surface, #ffffff); border: 1.5px dashed var(--border-color, #e2e8f0);">
                <i class="fa-solid fa-box-open fa-2x text-muted opacity-50 mb-2"></i>
                <h6 class="fw-semibold mb-1" style="font-size: 0.9rem; color: var(--text-main, #0f172a);">No accounts found</h6>
                <p class="text-muted small mb-3">Try searching with a different keyword or selecting another category.</p>
                <a href="{{ route('logs.index') }}" class="btn btn-sm btn-primary-custom rounded-pill px-3 py-1 fw-semibold reset-filter-btn" style="font-size: 0.78rem;">
                    View All Categories
                </a>
            </div>
        </div>
    @endforelse
</div>

<!-- Previous / Page Indicator / Next Controls -->
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
                <a href="{{ $products->previousPageUrl() }}" class="btn btn-sm btn-outline-primary px-3 py-1.5 rounded-pill pagination-link" data-page="{{ $products->currentPage() - 1 }}">
                    <i class="fa-solid fa-chevron-left me-1"></i> Previous
                </a>
            @endif

            <span class="px-3 py-1 rounded-pill bg-light border text-muted small fw-semibold font-monospace">
                Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
            </span>

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" class="btn btn-sm btn-outline-primary px-3 py-1.5 rounded-pill pagination-link" data-page="{{ $products->currentPage() + 1 }}">
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
