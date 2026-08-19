@extends('layouts.app')

@section('header_title', 'HQ: Coupon Management')

@section('content')
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Discount Coupons</h5>
                    <p class="text-muted small mb-0">Manage coupons specifically applied to Virtual Number purchases.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-3"><i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 rounded-3"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 rounded-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Usage</th>
                            <th>Min Spend</th>
                            <th>Status</th>
                            <th>Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                        <tr>
                            <td>
                                <span class="fw-bold font-monospace bg-light px-2 py-1 rounded">{{ $coupon->code }}</span>
                            </td>
                            <td>
                                @if($coupon->type === 'fixed')
                                    <span class="badge bg-primary-custom bg-opacity-10 text-primary-custom">{{ money($coupon->value) }}</span>
                                @else
                                    <span class="badge bg-info bg-opacity-10 text-info">{{ number_format($coupon->value, 0) }}% Off</span>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-bold">
                                    {{ $coupon->usages_count }} / {{ $coupon->usage_limit ?: '∞' }}
                                </div>
                                <div class="text-muted" style="font-size: 0.65rem;">Per User: {{ $coupon->user_limit }}</div>
                            </td>
                            <td>
                                @if($coupon->min_spend > 0)
                                    <span class="small fw-bold">{{ money($coupon->min_spend) }}</span>
                                @else
                                    <span class="small text-muted fst-italic">None</span>
                                @endif
                            </td>
                            <td>
                                @if($coupon->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($coupon->expires_at)
                                    <span class="small {{ $coupon->expires_at->isPast() ? 'text-danger fw-bold' : 'text-muted' }}">
                                        {{ $coupon->expires_at->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="small text-muted fst-italic">No Expiry</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <form action="{{ route('hq.coupons.toggle', $coupon) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $coupon->status ? 'warning' : 'success' }}" title="Toggle Status">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('hq.coupons.destroy', $coupon) }}" method="POST" onsubmit="return confirm('Delete this coupon permanently?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Coupon">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No coupons found. Create one to get started.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Create Coupon</h5>
            <form action="{{ route('hq.coupons.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold">Coupon Code</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. SUMMER2026" required style="text-transform: uppercase;">
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Discount Type</label>
                        <select name="type" class="form-select" required>
                            <option value="fixed">Fixed ($)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Value</label>
                        <input type="number" name="value" class="form-control" step="0.01" min="0.01" placeholder="0.50" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Minimum Spend (USD)</label>
                    <input type="number" name="min_spend" class="form-control" step="0.01" min="0" placeholder="0.00" value="0.00">
                    <div class="form-text small">Leave 0.00 for no minimum.</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Total Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-control" min="1" placeholder="Unlimited">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Per-User Limit</label>
                        <input type="number" name="user_limit" class="form-control" min="1" value="1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Expiry Date</label>
                    <input type="datetime-local" name="expires_at" class="form-control">
                    <div class="form-text small">Leave blank for no expiration.</div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="status" id="couponStatus" checked value="1">
                        <label class="form-check-label small fw-bold" for="couponStatus">Active</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 fw-bold">Create Coupon</button>
            </form>
        </div>
    </div>
</div>
@endsection
