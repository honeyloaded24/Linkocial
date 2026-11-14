@extends('layouts.app')

@section('header_title', 'AI TOOLS — Manage Products')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="fa-solid fa-box me-2 text-primary"></i>AI TOOLS — Products</h4>
            <p class="text-muted small mb-0">Activate/deactivate products, set custom pricing and categories</p>
        </div>
        <button class="btn btn-primary btn-sm" onclick="syncProducts()">
            <i class="fa fa-sync me-1"></i>Sync from Provider
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filter bar --}}
    <form method="GET" class="d-flex flex-wrap gap-2 mb-4 align-items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="form-control form-control-sm" style="max-width:220px;">
        <select name="status" class="form-select form-select-sm" style="max-width:150px;">
            <option value="">All Status</option>
            <option value="active"   {{ request('status')=='active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('hq.ai-tools.products') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        @endif
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead style="background:var(--bg-muted,#f8fafc);">
                        <tr>
                            <th class="px-3 py-2">Product Name</th>
                            <th class="py-2">Provider ID</th>
                            <th class="py-2">Category</th>
                            <th class="py-2">Provider Cost</th>
                            <th class="py-2">Stock</th>
                            <th class="py-2">Active</th>
                            <th class="py-2">Featured</th>
                            <th class="py-2">Last Sync</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr id="row-{{ $product->id }}">
                            <td class="px-3 fw-semibold">{{ \Illuminate\Support\Str::limit($product->name, 35) }}</td>
                            <td class="font-monospace text-muted">{{ $product->provider_product_id }}</td>
                            <td>{{ $product->category->name ?? '—' }}</td>
                            <td>${{ number_format((float) $product->price_usdt, 4) }} USDT</td>
                            <td>
                                @if($product->fulfillment === 'on_demand')
                                    <span class="badge bg-info bg-opacity-15 text-info">On-demand</span>
                                @else
                                    <span class="{{ $product->in_stock ? 'text-success' : 'text-danger' }}">
                                        {{ $product->stock ?? ($product->in_stock ? '✓' : '✗') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        {{ $product->is_active ? 'checked' : '' }}
                                        onchange="toggleProduct({{ $product->id }}, 'is_active', this.checked)">
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        {{ $product->is_featured ? 'checked' : '' }}
                                        onchange="toggleProduct({{ $product->id }}, 'is_featured', this.checked)">
                                </div>
                            </td>
                            <td class="text-muted">{{ $product->last_synced_at ? \Carbon\Carbon::parse($product->last_synced_at)->diffForHumans() : 'Never' }}</td>
                            <td>
                                <button class="btn btn-xs btn-outline-secondary btn-edit-product" style="font-size:0.72rem;padding:2px 8px;"
                                    data-id="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-category-id="{{ $product->category_id ?? '' }}"
                                    data-markup-pct="{{ $product->custom_markup_percentage ?? 0 }}"
                                    data-markup-fixed="{{ $product->custom_markup_fixed ?? 0 }}"
                                    data-sort-order="{{ $product->sort_order ?? 0 }}">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-4 text-muted">No products found. Use "Sync from Provider" to import products.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($products->hasPages())
    <div class="d-flex justify-content-center mt-4">{{ $products->links() }}</div>
    @endif
</div>

{{-- Edit Product Modal --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Product</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small" id="editProductName"></p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Category</label>
                    <select class="form-select form-select-sm" id="editCategory">
                        <option value="">— Uncategorised —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col">
                        <label class="form-label small fw-semibold">Custom Markup %</label>
                        <input type="number" id="editMarkupPct" class="form-control form-control-sm" step="0.01" min="0">
                    </div>
                    <div class="col">
                        <label class="form-label small fw-semibold">Custom Markup ₦</label>
                        <input type="number" id="editMarkupFixed" class="form-control form-control-sm" step="0.01" min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Sort Order</label>
                    <input type="number" id="editSortOrder" class="form-control form-control-sm" min="0">
                </div>
                <p class="text-muted small">Leave custom markup at 0 to use the global AI TOOLS markup setting.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-sm" onclick="saveEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

{{-- Sync Toast --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="syncToast" class="toast"><div class="toast-header"><strong class="me-auto">Sync</strong><button class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body" id="syncToastBody"></div></div>
</div>
@endsection

@push('scripts')
<script>
var editingId = null;

function openEdit(id, name, catId, markupPct, markupFixed, sortOrder) {
    editingId = id;
    document.getElementById('editProductName').textContent = name;
    document.getElementById('editCategory').value    = catId || '';
    document.getElementById('editMarkupPct').value   = markupPct || 0;
    document.getElementById('editMarkupFixed').value = markupFixed || 0;
    document.getElementById('editSortOrder').value   = sortOrder || 0;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function saveEdit() {
    if (!editingId) return;
    var data = {
        category_id:              document.getElementById('editCategory').value || null,
        custom_markup_percentage: document.getElementById('editMarkupPct').value,
        custom_markup_fixed:      document.getElementById('editMarkupFixed').value,
        sort_order:               document.getElementById('editSortOrder').value,
        _method: 'PATCH'
    };
    fetch('/hq/ai-tools/products/' + editingId, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(d => {
        if (d.success) { bootstrap.Modal.getInstance(document.getElementById('editModal')).hide(); location.reload(); }
        else alert(d.message || 'Update failed.');
    }).catch(() => alert('Request failed.'));
}

function toggleProduct(id, field, value) {
    var data = { _method: 'PATCH' };
    data[field] = value ? 1 : 0;
    fetch('/hq/ai-tools/products/' + id, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(d => { if (!d.success) { alert(d.message || 'Update failed.'); location.reload(); } })
    .catch(() => location.reload());
}

function syncProducts() {
    if (!confirm('Sync products from provider?')) return;
    fetch('{{ route("hq.ai-tools.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        document.getElementById('syncToastBody').textContent = d.message || (d.success ? 'Sync complete.' : 'Sync failed.');
        new bootstrap.Toast(document.getElementById('syncToast')).show();
        if (d.success) setTimeout(() => location.reload(), 2000);
    }).catch(() => alert('Sync failed.'));
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openEdit(
                this.dataset.id,
                this.dataset.name,
                this.dataset.categoryId,
                parseFloat(this.dataset.markupPct) || 0,
                parseFloat(this.dataset.markupFixed) || 0,
                parseInt(this.dataset.sortOrder) || 0
            );
        });
    });
});
</script>
@endpush


