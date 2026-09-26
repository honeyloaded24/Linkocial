@extends('layouts.app')

@section('header_title', 'Logs Purchase History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Logs & Accounts Purchase History</h4>
            <small class="text-muted">Access your delivered credentials, account logins, and attached files anytime.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('logs.index') }}" class="btn btn-primary-custom px-3 rounded-pill fw-bold shadow-sm">
                <i class="fa-solid fa-cart-shopping me-1"></i> Browse Marketplace
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">{{ session('error') }}</div>
    @endif

    <div class="premium-card p-4 shadow-sm border-0" style="border-radius: 14px;">
        <!-- Search and Filter Row -->
        <form action="{{ route('logs.history') }}" method="GET" class="row g-3 mb-4 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search by Product Name or Order ID..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom flex-grow-1 fw-bold">Filter</button>
                @if(request('search') || request('status'))
                    <a href="{{ route('logs.history') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">Order ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Amount Paid</th>
                        <th>Status</th>
                        <th>Purchased Date</th>
                        <th class="text-end px-3">Credentials</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php
                        $items = json_decode($order->delivered_items, true) ?? [];
                        $hasItems = is_array($items) && count($items) > 0;
                    @endphp
                    <tr>
                        <td class="px-3">
                            <span class="fw-bold">#LOG-{{ $order->id }}</span>
                            @if($order->external_order_id)
                                <div class="text-muted small" style="font-size: 0.72rem;">{{ $order->external_order_id }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($order->product)
                                    {!! $order->product->getIconHtml(36) !!}
                                @else
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold" style="word-break: break-word; overflow-wrap: anywhere;">{{ $order->product_name }}</div>
                                    <small class="text-muted">₦{{ number_format((float)$order->unit_cost, 2) }} / unit</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary px-3 py-1 rounded-pill fw-bold" style="font-size: 0.8rem;">
                                {{ $order->quantity }} pcs
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-success fs-6">
                                ₦{{ number_format((float)$order->total_cost, 2) }}
                            </span>
                        </td>
                        <td>
                            @if($order->status === 'completed')
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-circle-check me-1"></i> COMPLETED
                                </span>
                            @elseif($order->status === 'pending')
                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-clock me-1"></i> PENDING
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill fw-bold" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-circle-xmark me-1"></i> {{ strtoupper($order->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            <div>{{ $order->created_at->format('M d, Y') }}</div>
                            <div style="font-size: 0.72rem;">{{ $order->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="text-end px-3">
                            @if($order->status === 'completed' && $hasItems)
                                <a href="{{ route('logs.order', $order->id) }}" class="btn btn-sm btn-primary-custom rounded-pill px-3 py-1 fw-bold text-nowrap" title="View credentials and downloads">
                                    <i class="fa-solid fa-key me-1"></i> View Credentials
                                </a>
                            @elseif($order->status === 'failed')
                                <span class="text-muted small">Refunded</span>
                            @else
                                <a href="{{ route('logs.order', $order->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 text-nowrap">
                                    Details
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="opacity-25 mb-3">
                                <i class="fa-solid fa-box-open fa-3x"></i>
                            </div>
                            <h6 class="text-muted fw-bold">No purchase records found.</h6>
                            <p class="text-muted small mb-3">You have not purchased any logs or accounts yet.</p>
                            <a href="{{ route('logs.index') }}" class="btn btn-primary-custom btn-sm px-4 rounded-pill fw-bold">
                                <i class="fa-solid fa-cart-shopping me-1"></i> Visit Logs Marketplace
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-4">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
