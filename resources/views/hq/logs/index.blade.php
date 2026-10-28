@extends('layouts.app')

@section('header_title', 'HQ: Logs & Accounts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">Logs Marketplace Products</h4>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#manageHotPicksModal">
                <i class="fa-solid fa-fire text-danger me-1"></i> Manage HOT PICKS
                @if(count($hotPickIds) > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ count($hotPickIds) }}</span>
                @endif
            </button>
            <a href="{{ route('hq.logs.pricing') }}" class="btn btn-outline-warning">
                <i class="fa-solid fa-tags me-1"></i> Pricing & Markup
            </a>
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="fa-solid fa-folder-tree me-1"></i> Manage Categories
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
                    <option value="{{ $cat->id }}" {{ (request('category_id') == $cat->id || request('category') == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
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
                        <th>Our Price (₦)</th>
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
                            <div class="d-flex align-items-center gap-2">
                                {!! $product->getIconHtml(36) !!}
                                <div>
                                    <div class="fw-bold d-flex align-items-center flex-wrap gap-1" style="font-size: 0.85rem; word-break: break-word; overflow-wrap: anywhere;">
                                        <span>{{ $product->name }}</span>
                                        @if(in_array($product->id, $hotPickIds))
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger" style="font-size: 0.65rem;">
                                                <i class="fa-solid fa-fire"></i> HOT PICK
                                            </span>
                                        @endif
                                    </div>
                                    @if($product->type === 'api')
                                        <small class="text-muted" style="font-size: 0.75rem;">Key: {{ $product->api_product_key }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category->name ?? 'General' }}</td>
                        <td class="fw-bold text-danger">
                            @if($product->type === 'api')
                                @if((float)$product->base_api_price > 0)
                                    ₦{{ number_format((float)$product->base_api_price, 2) }}
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">₦0.00 (Missing)</span>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
                                $retail = $product->getRetailPrice();
                                $wholesale = $product->getWholesalePrice();
                                $isUnpriced = ($retail <= 0 && ($product->type === 'local' || $wholesale <= 0));
                            @endphp
                            <div class="d-flex align-items-center">
                                <div class="fw-bold text-success font-monospace me-2" style="font-size: 0.95rem;">
                                    @if($retail > 0)
                                        ₦{{ number_format($retail, 2) }}
                                    @else
                                        <span class="text-danger">Unpriced</span>
                                    @endif
                                </div>
                                @if($product->type === 'api')
                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.65rem;">+{{ $markupRate }}%</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-15 text-muted" style="font-size: 0.65rem;">Fixed</span>
                                @endif
                            </div>
                            @if($product->type === 'api' && $wholesale > 0)
                                <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">
                                    Wholesale: ₦{{ number_format($wholesale, 2) }}
                                </small>
                            @endif
                            @if($isUnpriced)
                                <small class="text-danger fw-bold d-block mt-1" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Zero cost (blocked for buyers)
                                </small>
                            @endif
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
                                <form action="{{ route('hq.logs.hot-picks.toggle', $product->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ in_array($product->id, $hotPickIds) ? 'btn-danger text-white' : 'btn-outline-danger' }}" title="{{ in_array($product->id, $hotPickIds) ? 'Remove from HOT PICKS' : 'Add to HOT PICKS' }}">
                                        <i class="fa-solid fa-fire"></i>
                                    </button>
                                </form>
                                <form action="{{ route('hq.logs.toggle', $product->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @if($product->is_active)
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Disable Product">Disable</button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Enable Product">Enable</button>
                                    @endif
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $product->id }}" title="Edit Product">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <form action="{{ route('hq.logs.product.delete', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product? All its inventory will be deleted too.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product"><i class="fa-solid fa-trash"></i></button>
                                </form>
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

<!-- Manage / Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="manageCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                        <i class="fa-solid fa-folder-tree fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="manageCategoryModalLabel">Manage Product Categories</h5>
                        <small class="text-muted">Create new categories or manage and delete existing ones.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <!-- Create New Category Form -->
                <form action="{{ route('hq.logs.category.store') }}" method="POST" class="p-3 rounded-3 bg-light border mb-4">
                    @csrf
                    <label class="form-label fw-bold small text-uppercase" style="letter-spacing: 0.5px;">Create New Category</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="name" required placeholder="e.g. Gmail Accounts, Instagram Logs..." autocomplete="off">
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="fa-solid fa-plus me-1"></i> Add Category
                        </button>
                    </div>
                </form>

                <!-- Existing Categories List -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">
                            Existing Categories ({{ $categories->count() }})
                        </span>
                        <small class="text-muted" style="font-size: 0.75rem;">Categories with products require reassignment before deletion</small>
                    </div>

                    @if($categories->isEmpty())
                        <div class="text-center py-4 text-muted border rounded-3 bg-light">
                            <i class="fa-regular fa-folder-open fs-2 mb-2 opacity-50"></i>
                            <p class="mb-0 small">No categories found. Create your first category above.</p>
                        </div>
                    @else
                        <div class="border rounded-3 overflow-hidden">
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                                        <tr>
                                            <th class="ps-3 py-2">Category Name</th>
                                            <th class="py-2 text-center">Assigned Products</th>
                                            <th class="pe-3 py-2 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categories as $cat)
                                        @php $pCount = $cat->products_count ?? $cat->products()->count(); @endphp
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa-solid fa-folder text-warning fs-6"></i>
                                                    <span class="fw-bold fs-7">{{ $cat->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($pCount > 0)
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                                        {{ $pCount }} {{ Str::plural('product', $pCount) }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                                        0 products (Empty)
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-end">
                                                @if($pCount == 0)
                                                    <form action="{{ route('hq.logs.category.delete', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete the category \'{{ addslashes($cat->name) }}\'? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" title="Delete category">
                                                            <i class="fa-solid fa-trash-can me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <!-- Category has products: offer safe reassignment dialog -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" data-bs-toggle="collapse" data-bs-target="#reassignBox{{ $cat->id }}" aria-expanded="false" title="Delete or reassign products">
                                                        <i class="fa-solid fa-trash-can me-1"></i> Delete
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($pCount > 0)
                                        <tr class="collapse bg-light" id="reassignBox{{ $cat->id }}">
                                            <td colspan="3" class="p-3">
                                                <div class="p-3 rounded-3 bg-white border border-danger border-opacity-25 shadow-sm">
                                                    <div class="d-flex align-items-center gap-2 text-danger fw-bold small mb-2">
                                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                                        <span>Category Contains {{ $pCount }} Product(s)</span>
                                                    </div>
                                                    <p class="small text-muted mb-3">
                                                        To prevent accidental data loss, products must be reassigned before deleting <strong>{{ $cat->name }}</strong>. Select another category below to transfer these products to, then proceed:
                                                    </p>
                                                    <form action="{{ route('hq.logs.category.delete', $cat->id) }}" method="POST" class="row g-2 align-items-center" onsubmit="return confirm('Reassign all {{ $pCount }} product(s) to the selected category and permanently delete category \'{{ addslashes($cat->name) }}\'?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <div class="col-md-7 col-sm-12">
                                                            <select name="reassign_to" class="form-select form-select-sm" required>
                                                                <option value="" disabled selected>-- Select Target Category --</option>
                                                                @foreach($categories as $targetCat)
                                                                    @if($targetCat->id != $cat->id)
                                                                        <option value="{{ $targetCat->id }}">{{ $targetCat->name }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-5 col-sm-12 d-flex gap-2">
                                                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 flex-grow-1">
                                                                <i class="fa-solid fa-right-left me-1"></i> Reassign & Delete
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-light rounded-pill px-2" data-bs-toggle="collapse" data-bs-target="#reassignBox{{ $cat->id }}">
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Local Product Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 shadow-lg" action="{{ route('hq.logs.product.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create Local Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Category</label>
                    <select class="form-select" name="log_category_id" required>
                        <option value="" disabled selected>Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Product Name</label>
                    <input type="text" class="form-control" name="name" required placeholder="e.g. Old US Gmails">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Product Icon (Optional)</label>
                    <input type="file" class="form-control" name="icon" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                    <div class="form-text small">Upload an image (PNG, JPG, SVG, WebP) or leave empty to automatically detect based on platform name (Instagram, FB, X, Gmail, etc.).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Description (Optional)</label>
                    <textarea class="form-control" name="description" rows="5" placeholder="Enter product instructions, details, rules... Links (e.g. https://...) and multiple paragraphs are supported and will be clickable."></textarea>
                    <div class="form-text small">Supports multiple paragraphs and clickable URLs for buyers.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Our Price (₦)</label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">₦</span>
                        <input type="number" step="0.01" class="form-control" name="our_price" required min="0" value="0" placeholder="0.00">
                    </div>
                    <div class="form-text small">Price in Nigerian Naira (₦).</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-custom px-4">Create Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modals (Rendered outside of table for proper Bootstrap 5 backdrop, stacking & form submission) -->
@foreach($products as $product)
    <div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1" aria-labelledby="editProductLabel{{ $product->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4 shadow-lg text-start" action="{{ route('hq.logs.product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editProductLabel{{ $product->id }}">Edit Product #{{ $product->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Category</label>
                        <select class="form-select" name="log_category_id">
                            <option value="">-- No Category / General --</option>
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
                        <label class="form-label fw-bold small">Product Icon (Optional)</label>
                        @if(!empty($product->icon))
                            <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded bg-light border">
                                <img src="{{ asset($product->icon) }}" class="rounded-circle object-fit-cover" style="width: 38px; height: 38px;">
                                <span class="small text-muted flex-grow-1">Current custom icon</span>
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" name="remove_icon" value="1" id="removeIcon{{ $product->id }}">
                                    <label class="form-check-label text-danger small fw-bold" for="removeIcon{{ $product->id }}">
                                        Remove icon
                                    </label>
                                </div>
                            </div>
                        @endif
                        <input type="file" class="form-control" name="icon" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                        <div class="form-text small">Upload to replace icon, or leave empty to keep current / auto-detect.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Description (Optional)</label>
                        <textarea class="form-control" name="description" rows="5" placeholder="Enter product instructions, details, rules... Links (e.g. https://...) and multiple paragraphs are supported and will be clickable.">{{ $product->description }}</textarea>
                        <div class="form-text small">Supports multiple paragraphs and clickable URLs for buyers.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Our Price (₦)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">₦</span>
                            <input type="number" step="0.01" class="form-control" name="our_price" required min="0" value="{{ $product->our_price }}" placeholder="0.00">
                        </div>
                        <div class="form-text small">Price in Nigerian Naira (₦).</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">Update Product</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<!-- Manage HOT PICKS Modal -->
<div class="modal fade" id="manageHotPicksModal" tabindex="-1" aria-labelledby="manageHotPicksLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg text-start">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="manageHotPicksLabel">
                    <i class="fa-solid fa-fire text-danger me-2"></i> Manage HOT PICKS 🔥
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="alert alert-info border-0 rounded-3 small py-2 mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Only products explicitly selected here or toggled with <i class="fa-solid fa-fire text-danger"></i> appear in the <strong>HOT PICKS 🔥</strong> section on the customer marketplace. You can reorder, add, or remove items at any time without deleting the products.
                </div>

                <!-- Currently Selected Hot Picks List -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold small text-uppercase text-muted mb-0">Current HOT PICKS ({{ count($hotPickIds) }})</h6>
                        @if(count($hotPickIds) > 0)
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Active on Frontend</span>
                        @endif
                    </div>
                    @php
                        $hotProductsList = collect($hotPickIds)->map(function($id) use ($allActiveProducts) {
                            return $allActiveProducts->firstWhere('id', (int)$id);
                        })->filter();
                    @endphp

                    @if($hotProductsList->isEmpty())
                        <div class="p-4 text-center border rounded-3 bg-light text-muted">
                            <i class="fa-solid fa-fire-flame-simple fa-2x mb-2 text-secondary opacity-50"></i>
                            <div class="fw-semibold">No products currently selected for HOT PICKS</div>
                            <small class="text-muted">Select an active product below or use the 🔥 button in the table to feature it.</small>
                        </div>
                    @else
                        <div class="list-group">
                            @foreach($hotProductsList as $index => $hotProd)
                                <div class="list-group-item d-flex align-items-center justify-content-between p-2 rounded-3 mb-2 border">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark rounded-circle px-2 py-1 small fw-bold" style="width: 26px; text-align: center;">{{ $index + 1 }}</span>
                                        {!! $hotProd->getIconHtml(32) !!}
                                        <div>
                                            <div class="fw-bold small">{{ $hotProd->name }}</div>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">{{ $hotProd->category->name ?? 'General' }}</span>
                                            <span class="text-success fw-bold ms-2" style="font-size: 0.75rem;">₦{{ number_format($hotProd->getRetailPrice(), 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <!-- Move Up -->
                                        <form action="{{ route('hq.logs.hot-picks.reorder') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $hotProd->id }}">
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2" {{ $index === 0 ? 'disabled' : '' }} title="Move Up">
                                                <i class="fa-solid fa-arrow-up"></i>
                                            </button>
                                        </form>
                                        <!-- Move Down -->
                                        <form action="{{ route('hq.logs.hot-picks.reorder') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $hotProd->id }}">
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2" {{ $index === count($hotProductsList) - 1 ? 'disabled' : '' }} title="Move Down">
                                                <i class="fa-solid fa-arrow-down"></i>
                                            </button>
                                        </form>
                                        <!-- Remove from Hot Picks -->
                                        <form action="{{ route('hq.logs.hot-picks.toggle', $hotProd->id) }}" method="POST" class="d-inline ms-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Remove from HOT PICKS without deleting product">
                                                <i class="fa-solid fa-xmark me-1"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Add New Product to Hot Picks -->
                <div class="border-top pt-3">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Add Product to HOT PICKS</h6>
                    @php
                        $availableToAdd = $allActiveProducts->reject(function($p) use ($hotPickIds) {
                            return in_array($p->id, $hotPickIds);
                        });
                    @endphp
                    @if($availableToAdd->isEmpty())
                        <div class="small text-muted fst-italic">All active products are already in HOT PICKS.</div>
                    @else
                        <form action="{{ route('hq.logs.hot-picks.update') }}" method="POST" class="d-flex gap-2">
                            @csrf
                            @foreach($hotPickIds as $existingId)
                                <input type="hidden" name="product_ids[]" value="{{ $existingId }}">
                            @endforeach
                            <select name="product_ids[]" class="form-select form-select-sm" required>
                                <option value="" disabled selected>-- Select a product to add to HOT PICKS --</option>
                                @foreach($availableToAdd as $addProd)
                                    <option value="{{ $addProd->id }}">
                                        {{ $addProd->name }} ({{ $addProd->category->name ?? 'General' }} - ₦{{ number_format($addProd->getRetailPrice(), 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-danger text-nowrap px-3">
                                <i class="fa-solid fa-plus me-1"></i> Add to HOT PICKS
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

