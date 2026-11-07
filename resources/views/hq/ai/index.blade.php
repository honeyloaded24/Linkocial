@extends('layouts.app')

@section('title', 'Admin HQ — AI Marketplace Services')

@section('content')
<div class="container-fluid py-4 px-md-4">

    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i> AI Marketplace Services</h4>
            <p class="text-muted small mb-0">Manage Google AI, Gemini, and premium LLM product listings, pricing, and stock.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('hq.ai.sync') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary rounded-pill px-3">
                    <i class="fa-solid fa-rotate me-1"></i> Sync from AIPlug
                </button>
            </form>
            <a href="{{ route('hq.ai.orders') }}" class="btn btn-primary rounded-pill px-3">
                <i class="fa-solid fa-receipt me-1"></i> View Orders
            </a>
            <a href="{{ route('hq.ai.settings') }}" class="btn btn-secondary rounded-pill px-3">
                <i class="fa-solid fa-sliders me-1"></i> Settings
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="premium-card p-3 h-100 bg-primary bg-opacity-10 border-0">
                <h6 class="text-primary small fw-bold text-uppercase mb-1">Total AI Services</h6>
                <h4 class="fw-bold mb-0 text-primary">{{ number_format($productStats['total'] ?? 0) }}</h4>
                <small class="text-muted mt-2 d-block" style="font-size: 0.72rem;">Catalog entries</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="premium-card p-3 h-100 bg-success bg-opacity-10 border-0">
                <h6 class="text-success small fw-bold text-uppercase mb-1">Active Services</h6>
                <h4 class="fw-bold mb-0 text-success">{{ number_format($productStats['active'] ?? 0) }}</h4>
                <small class="text-muted mt-2 d-block" style="font-size: 0.72rem;">Visible in user market</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm">
                <h6 class="text-info small fw-bold text-uppercase mb-1">In-Stock</h6>
                <h4 class="fw-bold mb-0 text-info">{{ number_format($productStats['in_stock'] ?? 0) }}</h4>
                <small class="text-muted mt-2 d-block" style="font-size: 0.72rem;">Purchasable now</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm">
                <h6 class="text-warning small fw-bold text-uppercase mb-1">Total Orders</h6>
                <h4 class="fw-bold mb-0 text-warning">{{ number_format($productStats['orders'] ?? 0) }}</h4>
                <small class="text-muted mt-2 d-block" style="font-size: 0.72rem;">Lifetime sales</small>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form method="GET" action="{{ route('hq.ai.index') }}" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search service name, provider product ID..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary rounded-pill">Search</button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Product & Category</th>
                        <th>Provider ID</th>
                        <th>Wholesale (Cost)</th>
                        <th>Retail Price (NGN)</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#{{ $p->id }}</td>
                            <td>
                                <div class="fw-bold text-main">{{ $p->name }}</div>
                                <span class="badge bg-light text-muted small">{{ $p->category }}</span>
                                @if($p->is_hot)
                                    <span class="badge bg-danger small ms-1">HOT</span>
                                @endif
                            </td>
                            <td>
                                <code class="small text-muted">{{ $p->provider_product_id }}</code>
                            </td>
                            <td class="text-muted">
                                {{ money($p->wholesale_price) }}
                                <small class="d-block" style="font-size: 0.7rem;">({{ number_format($p->wholesale_price_kobo) }} kobo)</small>
                            </td>
                            <td class="fw-bold text-primary">
                                {{ money($p->effective_selling_price) }}
                            </td>
                            <td>
                                @if($p->in_stock)
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">In Stock</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2.5 py-1">Out of Stock</span>
                                @endif
                            </td>
                            <td>
                                @if($p->is_active)
                                    <span class="badge bg-success rounded-pill px-2 py-1">Active</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill px-2 py-1">Disabled</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 edit-product-btn"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editProductModal"
                                        data-id="{{ $p->id }}"
                                        data-name="{{ $p->name }}"
                                        data-price="{{ $p->retail_price }}"
                                        data-wholesale="{{ $p->wholesale_price }}"
                                        data-instock="{{ $p->in_stock ? 1 : 0 }}"
                                        data-active="{{ $p->is_active ? 1 : 0 }}"
                                        data-hot="{{ $p->is_hot ? 1 : 0 }}">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No AI services found in catalog. Click "Sync from AIPlug" above to populate catalog from API.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit AI Service Listing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm" method="POST" action="">
                @csrf
                <div class="modal-body py-4">
                    <h6 class="fw-bold text-main mb-3" id="editModalName">Product Name</h6>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Provider Wholesale Cost (NGN)</label>
                        <input type="text" id="editModalWholesale" class="form-control" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Custom Retail Price (NGN)</label>
                        <input type="number" step="0.01" min="0" name="retail_price" id="editModalPrice" class="form-control" placeholder="Leave blank to use default markup">
                        <small class="text-muted">Enter a custom price or leave empty to auto-calculate via global markup.</small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="editModalInStock">
                        <label class="form-check-label fw-bold small" for="editModalInStock">Mark as In-Stock</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editModalActive">
                        <label class="form-check-label fw-bold small" for="editModalActive">Visible in User Marketplace</label>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_hot" value="1" id="editModalHot">
                        <label class="form-check-label fw-bold small" for="editModalHot">Featured / HOT Badge</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editForm = document.getElementById('editProductForm');
    const editName = document.getElementById('editModalName');
    const editWholesale = document.getElementById('editModalWholesale');
    const editPrice = document.getElementById('editModalPrice');
    const editInStock = document.getElementById('editModalInStock');
    const editActive = document.getElementById('editModalActive');
    const editHot = document.getElementById('editModalHot');

    document.querySelectorAll('.edit-product-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = this.getAttribute('data-price');
            const wholesale = this.getAttribute('data-wholesale');
            const inStock = this.getAttribute('data-instock') === '1';
            const active = this.getAttribute('data-active') === '1';
            const hot = this.getAttribute('data-hot') === '1';

            editForm.action = `/hq/ai-marketplace/product/${id}`;
            editName.textContent = name;
            editWholesale.value = '₦' + parseFloat(wholesale || 0).toLocaleString();
            editPrice.value = price > 0 ? price : '';
            editInStock.checked = inStock;
            editActive.checked = active;
            editHot.checked = hot;
        });
    });
});
</script>
@endsection
