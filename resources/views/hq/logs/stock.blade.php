@extends('layouts.app')

@section('header_title', 'HQ: Manage Local Stock')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Manage Stock: {{ $product->name }}</h4>
            <div class="d-flex align-items-center gap-3 mt-1 flex-wrap">
                <span class="text-muted small">Total Available Stock: <strong class="text-success">{{ $product->in_stock }}</strong> accounts</span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-1 rounded-pill fw-bold">
                    Price: ₦{{ number_format((float)$product->our_price, 2) }}
                </span>
                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#editStockPriceModal">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Price
                </button>
            </div>
        </div>
        <a href="{{ route('hq.logs.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Products
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Add Accounts Form -->
        <div class="col-lg-5 mb-4">
            <div class="premium-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Add Stock</h5>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary-custom fw-bold px-3" id="topAddAnotherBtn" title="Queue another account">
                        <i class="fa-solid fa-plus me-1"></i> Add Another (+)
                    </button>
                </div>
                <p class="text-muted small mb-3">Add accounts one by one or click <strong>"+ Add Another (+)"</strong> continuously to queue bulk accounts before submitting.</p>

                <form action="{{ route('hq.logs.stock.add', $product->id) }}" method="POST" enctype="multipart/form-data" id="stockAddForm">
                    @csrf
                    
                    <div id="accountsQueue">
                        <!-- Account #1 Block -->
                        <div class="account-card p-3 rounded-4 mb-3 border position-relative" style="background: var(--bg-surface); border-color: var(--border-color) !important;" data-index="0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 account-num-badge">
                                    <i class="fa-solid fa-user-tag me-1"></i> Account #1
                                </span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none remove-acc-btn d-none" title="Remove this item">
                                    <i class="fa-solid fa-trash-can"></i> Remove
                                </button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Account Details / Credentials / Instructions <span class="text-danger">*</span></label>
                                <textarea class="form-control font-monospace" name="accounts[0][content]" rows="4" required placeholder="Email: user@example.com&#10;Password: secret123&#10;Recovery Info: code_xyz&#10;2FA Key: ABC12345..." style="font-size: 0.85rem;"></textarea>
                            </div>

                            <div>
                                <label class="form-label fw-bold small">Attachment File (Optional)</label>
                                <input type="file" name="accounts[0][attachment]" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.txt,.zip,.doc,.docx">
                            </div>
                        </div>
                    </div>

                    <!-- Continuous Add Another Button -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary w-100 py-2 fw-bold rounded-3" id="bottomAddAnotherBtn">
                            <i class="fa-solid fa-circle-plus me-1"></i> Add Another Account (+)
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-bold rounded-3 shadow-sm" id="submitAllBtn">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Save to Stock (<span id="totalQueueBadge">1 Account</span>)
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Inventory Items Table -->
        <div class="col-lg-7 mb-4">
            <div class="premium-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> Inventory Accounts</h5>
                    <span class="badge bg-secondary rounded-pill px-3 py-1">{{ $items->total() }} Total</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th>#</th>
                                <th>Credentials Preview</th>
                                <th>Attachment</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr>
                                <td class="fw-bold">#{{ $item->id }}</td>
                                <td>
                                    <pre class="mb-0 p-2 rounded bg-dark text-success small text-truncate" style="max-width: 200px; font-size: 0.75rem; white-space: pre;">{{ Str::limit($item->content, 35) }}</pre>
                                </td>
                                <td>
                                    @if($item->file_path)
                                        <a href="{{ asset($item->file_path) }}" target="_blank" class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1 rounded-pill text-decoration-none" title="{{ $item->file_name }}">
                                            <i class="fa-solid fa-paperclip me-1"></i> File
                                        </a>
                                    @else
                                        <span class="text-muted small">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->is_sold)
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill">Sold</span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Available</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <!-- Edit & View Button -->
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $item->id }}" title="View & Edit Details">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        @if(!$item->is_sold)
                                            <form action="{{ route('hq.logs.stock.item.delete', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this account item from inventory?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete account item">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fa-solid fa-box-open fa-3x text-muted opacity-25 mb-3"></i>
                                    <h6>No accounts in stock for this product yet.</h6>
                                    <p class="small text-muted mb-0">Use the form on the left to add your first account.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($items->hasPages())
                <div class="mt-4">
                    {{ $items->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit & View Modals (Placed cleanly outside of table to prevent layout shaking) -->
@foreach($items as $item)
<div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1" aria-labelledby="editItemLabel{{ $item->id }}" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 shadow-lg text-start" action="{{ route('hq.logs.stock.item.update', $item->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="editItemLabel{{ $item->id }}">Edit Account #{{ $item->id }}</h5>
                    <div class="text-muted small">Added: {{ $item->created_at->format('M d, Y H:i') }}
                        @if($item->is_sold)
                            &bull; <span class="badge bg-danger">Sold to User #{{ $item->user_id }}</span>
                        @else
                            &bull; <span class="badge bg-success">In Stock / Available</span>
                        @endif
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Credentials & Instructions <span class="text-danger">*</span></label>
                    <textarea class="form-control font-monospace" name="content" rows="8" required style="font-size: 0.85rem;">{{ $item->content }}</textarea>
                    <div class="form-text small">You can update passwords, recovery codes, or notes at any time before purchase.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Attachment File</label>
                    @if($item->file_path)
                        <div class="p-2 mb-2 rounded bg-light border d-flex justify-content-between align-items-center">
                            <span class="small text-truncate" style="max-width: 250px;">
                                <i class="fa-solid fa-paperclip text-primary me-1"></i> {{ $item->file_name ?? 'Current File' }}
                            </span>
                            <a href="{{ asset($item->file_path) }}" target="_blank" download class="btn btn-sm btn-outline-info py-0 px-2" title="Download current file">
                                <i class="fa-solid fa-download"></i> View
                            </a>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="removeFile{{ $item->id }}">
                            <label class="form-check-label text-danger small fw-bold" for="removeFile{{ $item->id }}">
                                Remove current attachment file
                            </label>
                        </div>
                    @endif

                    <input type="file" name="attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.txt,.zip,.doc,.docx">
                    <div class="form-text small">Select a file to upload or replace the current attachment.</div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endforeach

<!-- Edit Product Price Modal -->
<div class="modal fade" id="editStockPriceModal" tabindex="-1" aria-labelledby="editStockPriceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 shadow-lg text-start" action="{{ route('hq.logs.price', $product->id) }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editStockPriceLabel">Update Selling Price</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Our Price (₦) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">₦</span>
                        <input type="number" step="0.01" class="form-control" name="our_price" required min="0" value="{{ $product->our_price }}" placeholder="0.00">
                    </div>
                    <div class="form-text small">Price in Nigerian Naira (₦) for <strong>{{ $product->name }}</strong>.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-custom px-4 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Price
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let nextIndex = 1;
    const queue = document.getElementById('accountsQueue');
    const totalBadge = document.getElementById('totalQueueBadge');
    const topBtn = document.getElementById('topAddAnotherBtn');
    const bottomBtn = document.getElementById('bottomAddAnotherBtn');
    const submitBtn = document.getElementById('submitAllBtn');
    const form = document.getElementById('stockAddForm');

    function updateQueueBadges() {
        const cards = queue.querySelectorAll('.account-card');
        cards.forEach((card, i) => {
            const badge = card.querySelector('.account-num-badge');
            if (badge) {
                badge.innerHTML = `<i class="fa-solid fa-user-tag me-1"></i> Account #${i + 1}`;
            }

            const removeBtn = card.querySelector('.remove-acc-btn');
            if (removeBtn) {
                if (cards.length > 1) {
                    removeBtn.classList.remove('d-none');
                } else {
                    removeBtn.classList.add('d-none');
                }
            }
        });

        const total = cards.length;
        if (totalBadge) {
            totalBadge.textContent = total === 1 ? '1 Account' : `${total} Accounts`;
        }
    }

    function addAccountCard() {
        const idx = nextIndex++;
        const card = document.createElement('div');
        card.className = 'account-card p-3 rounded-4 mb-3 border position-relative';
        card.style.background = 'var(--bg-surface)';
        card.style.borderColor = 'var(--border-color)';
        card.dataset.index = idx;

        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 account-num-badge">
                    <i class="fa-solid fa-user-tag me-1"></i> Account #${queue.children.length + 1}
                </span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none remove-acc-btn" title="Remove this item">
                    <i class="fa-solid fa-trash-can"></i> Remove
                </button>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Account Details / Credentials / Instructions <span class="text-danger">*</span></label>
                <textarea class="form-control font-monospace" name="accounts[${idx}][content]" rows="4" required placeholder="Email: user@example.com&#10;Password: secret123&#10;Recovery Info: code_xyz&#10;2FA Key: ABC12345..." style="font-size: 0.85rem;"></textarea>
            </div>

            <div>
                <label class="form-label fw-bold small">Attachment File (Optional)</label>
                <input type="file" name="accounts[${idx}][attachment]" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.txt,.zip,.doc,.docx">
            </div>
        `;

        queue.appendChild(card);
        updateQueueBadges();

        // Scroll into view & focus textarea
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        const ta = card.querySelector('textarea');
        if (ta) ta.focus();
    }

    topBtn?.addEventListener('click', addAccountCard);
    bottomBtn?.addEventListener('click', addAccountCard);

    queue.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-acc-btn');
        if (removeBtn) {
            const card = removeBtn.closest('.account-card');
            if (card && queue.children.length > 1) {
                card.remove();
                updateQueueBadges();
            }
        }
    });

    form?.addEventListener('submit', function() {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving Accounts...`;
        }
    });

    updateQueueBadges();
});
</script>
@endsection
