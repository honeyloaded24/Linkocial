@extends('layouts.app')

@section('header_title', 'HQ: Logs & Accounts Orders')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Logs & Accounts Marketplace Orders</h4>
            <small class="text-muted">Track customer purchases and delivered account credentials across all categories.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hq.logs.index') }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Manage Products
            </a>
            <a href="{{ route('hq.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="premium-card p-4">
        <!-- Search and Filter Row -->
        <form action="{{ route('hq.logs.orders') }}" method="GET" class="row g-3 mb-4 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search Product, External ID, or User..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom flex-grow-1">Filter</button>
                @if(request('search') || request('status'))
                    <a href="{{ route('hq.logs.orders') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">Order ID</th>
                        <th>User</th>
                        <th>Product / Quantity</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Delivered Items</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="px-3">
                            <span class="fw-bold text-primary">#LOG-{{ $order->id }}</span>
                            @if($order->external_order_id)
                                <small class="text-muted d-block" style="font-size: 0.65rem;">{{ $order->external_order_id }}</small>
                            @endif
                        </td>
                        <td>
                            @if($order->user)
                                <a href="{{ route('hq.users.show', $order->user->id) }}" class="text-decoration-none text-dark">
                                    <div class="fw-bold" style="font-size: 0.85rem;">{{ trim(($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: $order->user->username }}</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $order->user->email }}</small>
                                </a>
                            @else
                                <span class="text-muted small">Deleted User</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-file-invoice text-warning me-1"></i>
                                {{ $order->product_name ?? ($order->product->name ?? 'Account Log') }}
                            </div>
                            <small class="text-muted">Quantity: <strong>{{ $order->quantity }}</strong> &times; {{ money((float)$order->unit_cost, true, 'NGN') }}</small>
                        </td>
                        <td class="fw-bold text-dark">
                            {{ money((float)$order->total_cost, true, 'NGN') }}
                        </td>
                        <td>
                            @php
                                $status = strtolower($order->status);
                            @endphp
                            @if($status === 'completed')
                                <span class="badge bg-success text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-check me-1"></i>COMPLETED
                                </span>
                            @elseif($status === 'pending')
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-clock me-1"></i>PENDING
                                </span>
                            @elseif($status === 'failed')
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-xmark me-1"></i>FAILED
                                </span>
                            @elseif($status === 'refunded')
                                <span class="badge bg-dark text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-rotate-left me-1"></i>REFUNDED
                                </span>
                            @else
                                <span class="badge bg-secondary text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.7rem;">
                                    {{ strtoupper($status) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($order->delivered_items)
                                @php
                                    $items = json_decode($order->delivered_items, true);
                                @endphp
                                @if(is_array($items) && count($items) > 0)
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2 py-1" data-bs-toggle="modal" data-bs-target="#deliveredModal{{ $order->id }}">
                                        <i class="fa-solid fa-eye me-1"></i> View ({{ count($items) }})
                                    </button>

                                    <!-- Modal -->
                                    <div class="modal fade" id="deliveredModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Delivered Credentials #LOG-{{ $order->id }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @if(is_array($items) && \Illuminate\Support\Arr::isAssoc($items))
                                                        <div class="p-2 mb-2 bg-light rounded border">
                                                            <pre class="mb-0 small font-monospace" style="white-space: pre-wrap;">{{ json_encode($items, JSON_PRETTY_PRINT) }}</pre>
                                                        </div>
                                                    @elseif(is_array($items))
                                                        @foreach($items as $item)
                                                            <div class="p-2 mb-2 bg-light rounded border">
                                                                <small class="text-muted d-block fw-bold">Item #{{ $loop->iteration }}</small>
                                                                @if(is_array($item))
                                                                    @if(isset($item['content']))
                                                                        <pre class="mb-1 small font-monospace" style="white-space: pre-wrap;">{{ $item['content'] }}</pre>
                                                                    @elseif(isset($item['details']))
                                                                        <pre class="mb-1 small font-monospace" style="white-space: pre-wrap;">{{ $item['details'] }}</pre>
                                                                    @else
                                                                        <pre class="mb-1 small font-monospace" style="white-space: pre-wrap;">{{ json_encode($item, JSON_PRETTY_PRINT) }}</pre>
                                                                    @endif
                                                                    @if(!empty($item['file_path']))
                                                                        <a href="{{ $item['file_path'] }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                                                            <i class="fa-solid fa-download me-1"></i> Download Attachment
                                                                        </a>
                                                                    @endif
                                                                @else
                                                                    <pre class="mb-0 small font-monospace" style="white-space: pre-wrap;">{{ $item }}</pre>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">No items</span>
                                @endif
                            @else
                                <span class="text-muted small">None</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $order->created_at ? $order->created_at->format('M d, Y') : 'N/A' }}<br>
                            <span style="font-size: 0.75rem;">{{ $order->created_at ? $order->created_at->format('H:i') : '' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted small">No logs orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
