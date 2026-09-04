@extends('layouts.app')

@section('header_title', 'HQ: Logs & Accounts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">Logs Marketplace Products</h4>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="fa-solid fa-folder-plus"></i> New Category
            </button>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createProductModal">
                <i class="fa-solid fa-plus"></i> New Local Product
            </button>
            <form action="{{ route('hq.logs.sync') }}" method="POST">
                @csrf
                <button class="btn btn-primary-custom" onclick="this.innerHTML='<i class=\'fa-solid fa-spinner fa-spin\'></i> Syncing...'">
                    <i class="fa-solid fa-rotate"></i> Sync API Products
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Filter Tabs and Search -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <!-- Type Filter Pills -->
        <div class="nav nav-pills gap-2">
            <a href="{{ route('hq.logs.index', array_merge(request()->except('type', 'page'))) }}" 
               class="nav-link rounded-pill px-3 py-2 fw-bold small {{ !request('type') ? 'active bg-primary text-white' : 'bg-light text-muted' }}">
                All Products <span class="badge {{ !request('type') ? 'bg-white text-primary' : 'bg-secondary' }} rounded-pill ms-1">{{ $counts['all'] }}</span>
            </a>
            <a href="{{ route('hq.logs.index', array_merge(request()->except('page'), ['type' => 'local'])) }}" 
               class="nav-link rounded-pill px-3 py-2 fw-bold small {{ request('type') == 'local' ? 'active bg-success text-white' : 'bg-light text-muted' }}">
                <i class="fa-solid fa-cube me-1"></i> Local / Created <span class="badge {{ request('type') == 'local' ? 'bg-white text-success' : 'bg-secondary' }} rounded-pill ms-1">{{ $counts['local'] }}</span>
            </a>
            <a href="{{ route('hq.logs.index', array_merge(request()->except('page'), ['type' => 'api'])) }}" 
               class="nav-link rounded-pill px-3 py-2 fw-bold small {{ request('type') == 'api' ? 'active bg-info text-white' : 'bg-light text-muted' }}">
                <i class="fa-solid fa-cloud me-1"></i> API Synced <span class="badge {{ request('type') == 'api' ? 'bg-white text-info' : 'bg-secondary' }} rounded-pill ms-1">{{ $counts['api'] }}</span>
            </a>
        </div>

        <!-- Search and Category Filter -->
        <form action="{{ route('hq.logs.index') }}" method="GET" class="d-flex gap-2 flex-grow-1 justify-content-md-end" style="max-width: 500px;">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <select name="category_id" class="form-select form-select-sm" style="max-width: 180px;" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search product..." value="{{ request('search') }}">
                <button class="btn btn-primary-custom" type="submit"><i class="fa-solid fa-search"></i></button>
                @if(request('search') || request('category_id') || request('type'))
                    <a href="{{ route('hq.logs.index') }}" class="btn btn-outline-secondary" title="Reset filters"><i class="fa-solid fa-times"></i></a>
                @endif
            </div>
        </form>
    </div>

    <div class="premium-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>Type/ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Cost (API)</th>
                        <th>Our Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            @if($product->type === 'api')
                                <span class="badge bg-secondary">API #{{ $product->api_product_id }}</span>
                            @else
                                <span class="badge bg-primary">Local</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $product->name }}</div>
                            @if($product->type === 'api')
                                <small class="text-muted" style="font-size: 0.75rem;">Key: {{ $product->api_product_key }}</small>
                            @endif
                        </td>
                        <td>{{ $product->category->name ?? 'General' }}</td>
                        <td class="fw-bold text-danger">
                            @if($product->type === 'api')
                                {{ money($product->base_api_price, true, 'NGN') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('hq.logs.price', $product->id) }}" method="POST" class="d-flex align-items-center">
                                @csrf
                                <input type="number" step="0.01" name="our_price" value="{{ $product->our_price }}" class="form-control form-control-sm me-2" style="width: 100px;">
                                <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-save"></i></button>
                            </form>
                        </td>
                        <td class="fw-bold">
                            {{ $product->in_stock }} pcs
                            @if($product->type === 'local')
                                <a href="{{ route('hq.logs.stock', $product->id) }}" class="btn btn-sm btn-link p-0 ms-2 text-decoration-none">Manage</a>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill">Disabled</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <form action="{{ route('hq.logs.toggle', $product->id) }}" method="POST">
                                    @csrf
                                    @if($product->is_active)
                                        <button class="btn btn-sm btn-outline-danger">Disable</button>
                                    @else
                                        <button class="btn btn-sm btn-outline-success">Enable</button>
                                    @endif
                                </form>
                                @if($product->type === 'local')
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $product->id }}" title="Edit Product">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Edit Local Product Modal -->
                                    <div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content border-0 rounded-4 shadow-lg text-start" action="{{ route('hq.logs.product.update', $product->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold">Edit Local Product</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body py-3">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Category</label>
                                                        <select class="form-select" name="log_category_id" required>
                                                            @foreach($categories as $cat)
                                                                <option value="{{ $cat->id }}" {{ $product->log_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Product Name</label>
                                                        <input type="text" class="form-control" name="name" required value="{{ $product->name }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Description (Optional)</label>
                                                        <textarea class="form-control" name="description" rows="3">{{ $product->description }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Our Price (₦)</label>
                                                        <input type="number" step="0.01" class="form-control" name="our_price" required min="0" value="{{ $product->our_price }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary-custom px-4">Update Product</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <form action="{{ route('hq.logs.product.delete', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this local product? All stock will be deleted too.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Delete Product"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fa-solid fa-box-open fa-3x text-muted opacity-25 mb-3"></i>
                            <h6>No log products found.</h6>
                            <p class="small text-muted mb-0">Create a Local Product or Sync from API.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($products->hasPages())
        <div class="mt-4">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 shadow-lg" action="{{ route('hq.logs.category.store') }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create Local Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Category Name</label>
                    <input type="text" class="form-control" name="name" required placeholder="e.g. Gmail Accounts">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-custom px-4">Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Local Product Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 shadow-lg" action="{{ route('hq.logs.product.store') }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create Local Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="log_category_id" required>
                        <option value="" disabled selected>Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" class="form-control" name="name" required placeholder="e.g. Old US Gmails">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (Optional)</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Our Price (₦)</label>
                    <input type="number" step="0.01" class="form-control" name="our_price" required min="0" value="0">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-custom px-4">Create Product</button>
            </div>
        </form>
    </div>
</div>
@endsection
