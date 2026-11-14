@extends('layouts.app')

@section('header_title', 'AI TOOLS — Dashboard')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="fa-solid fa-wand-magic-sparkles me-2 text-purple"></i>AI TOOLS Dashboard</h4>
            <p class="text-muted small mb-0">Provider: Alaric Store</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-primary btn-sm" onclick="syncProducts()">
                <i class="fa fa-sync me-1"></i>Sync Products
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="checkBalance()">
                <i class="fa fa-wallet me-1"></i>Provider Balance
            </button>
            <a href="{{ route('hq.ai-tools.settings') }}" class="btn btn-outline-dark btn-sm">
                <i class="fa fa-gear me-1"></i>Settings
            </a>
        </div>
    </div>

    {{-- Last sync info --}}
    <div class="alert alert-light border small mb-4">
        <i class="fa fa-clock me-1 text-muted"></i>
        Last product sync: <strong>{{ $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Never' }}</strong>
        &nbsp;·&nbsp;
        <a href="{{ route('hq.ai-tools.products') }}">Manage Products</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Stats grid --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3 fw-bold text-primary">{{ number_format($aiStats['total_products']) }}</div>
                <div class="text-muted small">Total Products</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3 fw-bold text-success">{{ number_format($aiStats['active_products']) }}</div>
                <div class="text-muted small">Active</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3 fw-bold text-secondary">{{ number_format($aiStats['inactive_products']) }}</div>
                <div class="text-muted small">Inactive</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3 fw-bold text-info">{{ number_format($aiStats['total_orders']) }}</div>
                <div class="text-muted small">Total Orders</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3 fw-bold text-warning">{{ number_format($aiStats['processing_orders'] + $aiStats['pending_orders']) }}</div>
                <div class="text-muted small">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3 fw-bold text-danger">{{ number_format($aiStats['under_review']) }}</div>
                <div class="text-muted small">Under Review</div>
            </div>
        </div>
    </div>

    {{-- Revenue cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="border-left:4px solid #6366f1 !important;">
                <div class="text-muted small mb-1">Total Revenue (NGN)</div>
                <div class="fs-4 fw-bold text-primary">₦{{ number_format($aiStats['total_revenue_ngn'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="border-left:4px solid #f59e0b !important;">
                <div class="text-muted small mb-1">Provider Cost (NGN)</div>
                <div class="fs-4 fw-bold text-warning">₦{{ number_format($aiStats['total_cost_ngn'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="border-left:4px solid #10b981 !important;">
                <div class="text-muted small mb-1">Net Profit (NGN)</div>
                <div class="fs-4 fw-bold text-success">₦{{ number_format($aiStats['total_profit_ngn'], 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Recent Orders</h6>
            <a href="{{ route('hq.ai-tools.orders') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            @if($recentOrders->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead style="background:var(--bg-muted,#f8fafc);">
                        <tr>
                            <th class="px-3 py-2">Order #</th>
                            <th class="py-2">Customer</th>
                            <th class="py-2">Product</th>
                            <th class="py-2">Amount</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Date</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td class="px-3 font-monospace">{{ $order->order_number }}</td>
                            <td>{{ $order->user->first_name ?? '' }} {{ $order->user->last_name ?? '' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($order->product->name ?? 'N/A', 25) }}</td>
                            <td class="fw-bold">₦{{ number_format($order->total_paid_ngn, 2) }}</td>
                            <td><span class="badge bg-{{ $order->status_badge }} bg-opacity-15 text-{{ $order->status_badge }}">{{ $order->status_label }}</span></td>
                            <td class="text-muted">{{ $order->created_at->format('d M, H:i') }}</td>
                            <td><a href="{{ route('hq.ai-tools.orders.show', $order->id) }}" class="btn btn-xs btn-outline-secondary" style="font-size:0.72rem;padding:2px 8px;">View</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <div class="text-center py-4 text-muted">No orders yet.</div>
            @endif
        </div>
    </div>
</div>

{{-- Provider Balance Modal --}}
<div class="modal fade" id="balanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Provider Wallet Balance</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center" id="balanceContent">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>
    </div>
</div>

{{-- Sync Result Toast --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="syncToast" class="toast" role="alert" aria-live="assertive">
        <div class="toast-header">
            <i class="fa fa-sync me-2 text-primary"></i>
            <strong class="me-auto">AI TOOLS Sync</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="syncToastBody"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function syncProducts() {
    if (!confirm('Sync AI Tools products from provider? This will update prices and stock.')) return;
    fetch('{{ route("hq.ai-tools.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('syncToastBody').textContent = data.message || (data.success ? 'Sync complete.' : 'Sync failed.');
        new bootstrap.Toast(document.getElementById('syncToast')).show();
        if (data.success) setTimeout(() => location.reload(), 2000);
    })
    .catch(() => alert('Sync request failed.'));
}

function checkBalance() {
    var modal = new bootstrap.Modal(document.getElementById('balanceModal'));
    document.getElementById('balanceContent').innerHTML = '<div class="spinner-border text-primary"></div>';
    modal.show();
    fetch('{{ route("hq.ai-tools.balance") }}', { headers: { Accept: 'application/json' } })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            var d = data.data || {};
            document.getElementById('balanceContent').innerHTML =
                '<h3 class="fw-bold text-success">' + JSON.stringify(d) + '</h3>';
        } else {
            document.getElementById('balanceContent').innerHTML =
                '<p class="text-danger">' + (data.error_message || 'Failed to fetch balance.') + '</p>';
        }
    })
    .catch(() => { document.getElementById('balanceContent').innerHTML = '<p class="text-danger">Request failed.</p>'; });
}
</script>
@endpush



