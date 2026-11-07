@extends('layouts.app')

@section('title', 'Admin HQ — AI Marketplace Orders')

@section('content')
<div class="container-fluid py-4 px-md-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-primary me-2"></i> AI Marketplace Orders</h4>
            <p class="text-muted small mb-0">Monitor customer AI purchases, provider fulfillment status, and delivery payloads.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hq.ai.index') }}" class="btn btn-outline-primary rounded-pill px-3">
                <i class="fa-solid fa-robot me-1"></i> Manage Products
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
            <div class="premium-card p-3 h-100 border-0 shadow-sm">
                <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Orders</h6>
                <h4 class="fw-bold mb-0 text-main">{{ number_format($orderStats['total'] ?? 0) }}</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="premium-card p-3 h-100 bg-success bg-opacity-10 border-0">
                <h6 class="text-success small fw-bold text-uppercase mb-1">Fulfilled</h6>
                <h4 class="fw-bold mb-0 text-success">{{ number_format($orderStats['fulfilled'] ?? 0) }}</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="premium-card p-3 h-100 bg-warning bg-opacity-10 border-0">
                <h6 class="text-warning small fw-bold text-uppercase mb-1">Pending / Processing</h6>
                <h4 class="fw-bold mb-0 text-warning">{{ number_format($orderStats['pending'] ?? 0) }}</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="premium-card p-3 h-100 bg-primary bg-opacity-10 border-0">
                <h6 class="text-primary small fw-bold text-uppercase mb-1">Total Fulfilled Sales</h6>
                <h4 class="fw-bold mb-0 text-primary">{{ money($orderStats['total_sales'] ?? 0) }}</h4>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form method="GET" action="{{ route('hq.ai.orders') }}" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search order ID, username, email, product..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="fulfilled" {{ $status === 'fulfilled' ? 'selected' : '' }}>Fulfilled</option>
                    <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="awaiting_manual" {{ $status === 'awaiting_manual' ? 'selected' : '' }}>Awaiting Manual Delivery</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="refunded" {{ $status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary rounded-pill">Filter</button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>User</th>
                        <th>Product</th>
                        <th>Revenue</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Delivery Payload</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#{{ $order->id }}</td>
                            <td>
                                <div class="fw-bold text-main">{{ $order->user->username ?? 'User #' . $order->user_id }}</div>
                                <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                            </td>
                            <td>
                                <div class="fw-bold text-main">{{ $order->product_name }}</div>
                                <small class="text-muted d-block">Qty: {{ $order->quantity }} • Ref: {{ $order->provider_order_id ?: 'Awaiting' }}</small>
                            </td>
                            <td class="fw-bold text-primary">{{ money($order->total_price) }}</td>
                            <td class="text-muted">{{ money($order->provider_cost) }}</td>
                            <td>
                                @if($order->status === 'fulfilled')
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">Fulfilled</span>
                                @elseif($order->status === 'processing')
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-1">Processing</span>
                                @elseif($order->status === 'awaiting_manual')
                                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2.5 py-1">Awaiting Delivery</span>
                                @elseif($order->status === 'refunded')
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1">Refunded</span>
                                @else
                                    <span class="badge bg-light text-dark rounded-pill px-2.5 py-1">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($order->delivery_payload)
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 view-payload-btn"
                                            data-bs-toggle="modal" data-bs-target="#adminPayloadModal"
                                            data-id="{{ $order->id }}"
                                            data-payload="{{ $order->formatted_delivery }}">
                                        <i class="fa-solid fa-eye me-1"></i> View Payload
                                    </button>
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $order->created_at->format('M d, Y H:i') }}</td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light rounded-pill px-2.5" type="button" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        @if($order->provider_order_id)
                                            <li>
                                                <form method="POST" action="{{ route('hq.ai.orders.refresh', $order->id) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fa-solid fa-rotate text-primary me-2"></i> Refresh from Provider
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($order->status !== 'fulfilled')
                                            <li>
                                                <button type="button" class="dropdown-item fulfill-order-btn"
                                                        data-bs-toggle="modal" data-bs-target="#fulfillModal"
                                                        data-id="{{ $order->id }}"
                                                        data-product="{{ $order->product_name }}">
                                                    <i class="fa-solid fa-check text-success me-2"></i> Fulfill Manually
                                                </button>
                                            </li>
                                        @endif
                                        @if($order->status !== 'refunded')
                                            <li>
                                                <form method="POST" action="{{ route('hq.ai.orders.refund', $order->id) }}" onsubmit="return confirm('Refund ₦{{ number_format($order->total_price, 2) }} back to user wallet?')">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fa-solid fa-arrow-rotate-left me-2"></i> Refund User Wallet
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                No AI Marketplace orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Admin View Payload Modal -->
<div class="modal fade" id="adminPayloadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Delivery Payload</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <pre id="adminPayloadContent" class="bg-dark text-light p-3 rounded-3 small mb-0" style="white-space: pre-wrap; font-family: monospace;"></pre>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manual Fulfill Modal -->
<div class="modal fade" id="fulfillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Manual Fulfillment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="fulfillForm" method="POST" action="">
                @csrf
                <div class="modal-body py-3">
                    <p class="text-muted small mb-2" id="fulfillProductName">Service Name</p>
                    <label class="form-label small fw-bold text-muted text-uppercase">Account Credentials / License Key / Link</label>
                    <textarea name="delivery_payload" rows="4" class="form-control" placeholder="Email: ... | Password: ... | License: ..." required></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Fulfill Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const adminPayloadContent = document.getElementById('adminPayloadContent');
    document.querySelectorAll('.view-payload-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            adminPayloadContent.textContent = this.getAttribute('data-payload');
        });
    });

    const fulfillForm = document.getElementById('fulfillForm');
    const fulfillProductName = document.getElementById('fulfillProductName');
    document.querySelectorAll('.fulfill-order-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const product = this.getAttribute('data-product');

            fulfillForm.action = `/hq/ai-marketplace/orders/${id}/fulfill`;
            fulfillProductName.textContent = product;
        });
    });
});
</script>
@endsection
