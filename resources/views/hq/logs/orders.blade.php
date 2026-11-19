@extends('layouts.app')

@section('header_title', 'HQ: Logs Marketplace Orders & Financial Records')

@section('content')
<style>
    [data-theme="dark"] .hq-log-orders .bg-light,
    [data-bs-theme="dark"] .hq-log-orders .bg-light {
        background-color: var(--bg-surface, #1e293b) !important;
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--border-color, #334155) !important;
    }
    [data-theme="dark"] .hq-log-orders .bg-white,
    [data-bs-theme="dark"] .hq-log-orders .bg-white {
        background-color: var(--bg-body, #0f172a) !important;
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--border-color, #334155) !important;
    }
    [data-theme="dark"] .hq-log-orders .btn-light,
    [data-bs-theme="dark"] .hq-log-orders .btn-light {
        background-color: var(--bg-surface, #1e293b) !important;
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--border-color, #334155) !important;
    }
</style>
<div class="container-fluid hq-log-orders">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Logs & Accounts Marketplace Orders</h4>
            <small class="text-muted">Track customer purchases, delivered credentials, wholesale product costs, and gross profits.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hq.logs.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Manage Products
            </a>
            <a href="{{ route('hq.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
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

    <!-- Logs Marketplace Financial Summary Cards -->
    @if(isset($logStats))
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Orders</span>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-box-open fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">{{ number_format($logStats['total']) }}</h3>
                    <div class="small text-muted d-flex gap-2 flex-wrap" style="font-size: 0.72rem;">
                        <span class="text-success"><i class="fa-solid fa-check"></i> {{ number_format($logStats['completed']) }} Done</span>
                        <span class="text-warning"><i class="fa-solid fa-clock"></i> {{ number_format($logStats['pending']) }} Pend</span>
                        <span class="text-danger"><i class="fa-solid fa-times"></i> {{ number_format($logStats['failed']) }} Fail</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Revenue</span>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-money-bill-wave fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-success">₦{{ number_format($logStats['revenue'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        Completed marketplace sales
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Product Cost</span>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-tags fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-warning">₦{{ number_format($logStats['provider_cost'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        Recorded wholesale catalog cost
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Profit</span>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-chart-line fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-info">₦{{ number_format($logStats['profit'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        Realized gross margin
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="premium-card p-4">
        <!-- Search and Date Filter Row -->
        <form action="{{ route('hq.logs.orders') }}" method="GET" class="row g-3 mb-4 align-items-center">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search Product, External ID, or User..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control border-secondary bg-transparent" value="{{ request('from_date') }}" title="From Date" style="color: var(--text-main);">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control border-secondary bg-transparent" value="{{ request('to_date') }}" title="To Date" style="color: var(--text-main);">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary-custom w-100" title="Filter"><i class="fa-solid fa-filter"></i></button>
            </div>
            @if(request('search') || request('status') || request('from_date') || request('to_date'))
            <div class="col-12 mt-2">
                <a href="{{ route('hq.logs.orders') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-times me-1"></i> Clear Filters</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <th class="px-3 text-nowrap">Order ID</th>
                        <th class="text-nowrap">User</th>
                        <th class="text-nowrap">Product / Quantity</th>
                        <th class="text-nowrap">Cost</th>
                        <th class="text-nowrap">Status</th>
                        <th class="text-nowrap">Delivered Items</th>
                        <th class="text-nowrap">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="px-3 text-nowrap">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace px-2 py-1" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.3px;">#LOG-{{ $order->id }}</span>
                            @if($order->external_order_id)
                                <small class="text-muted d-block font-monospace mt-1" style="font-size: 0.65rem;">{{ $order->external_order_id }}</small>
                            @endif
                        </td>
                        <td>
                            @if($order->user)
                                <a href="{{ route('hq.users.show', $order->user->id) }}" class="text-decoration-none text-main">
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
                            <small class="text-muted">Quantity: <strong>{{ $order->quantity }}</strong> &times; ₦{{ number_format((float)$order->unit_cost, 2) }}</small>
                        </td>
                        <td class="fw-bold text-main">
                            ₦{{ number_format((float)$order->total_cost, 2) }}
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
                                <span class="badge bg-warning text-main rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.7rem;">
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
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" data-bs-toggle="modal" data-bs-target="#deliveredModal{{ $order->id }}">
                                        <i class="fa-solid fa-eye me-1"></i> View
                                    </button>
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

<!-- Delivered Credentials Modals (Placed cleanly outside table to prevent layout shaking & blinking) -->
@foreach($orders as $order)
    @if($order->delivered_items)
        @php
            $items = json_decode($order->delivered_items, true);
        @endphp
        <div class="modal fade" id="deliveredModal{{ $order->id }}" tabindex="-1" aria-labelledby="deliveredModalLabel{{ $order->id }}" aria-hidden="true" data-bs-backdrop="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background-color: var(--bg-surface, #ffffff); color: var(--text-main, #212529);">
                    <div class="modal-header border-bottom py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-key text-primary"></i>
                            <h6 class="modal-title fw-bold mb-0" id="deliveredModalLabel{{ $order->id }}">
                                Delivered Credentials #LOG-{{ $order->id }}
                            </h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        @if(is_array($items) && \Illuminate\Support\Arr::isAssoc($items))
                            @if(isset($items['success']) && $items['success'] === false)
                                <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                                    <i class="fa-solid fa-circle-exclamation mt-1 fs-5"></i>
                                    <div>
                                        <div class="fw-bold">Provider API Error Response:</div>
                                        <div>{{ $items['message'] ?? 'Product not enabled or fulfillment failed.' }}</div>
                                    </div>
                                </div>
                            @endif
                            <div class="p-3 bg-light rounded-3 border">
                                <pre class="mb-0 small font-monospace" style="white-space: pre-wrap; word-break: break-all;">{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @elseif(is_array($items) && count($items) > 0)
                            @foreach($items as $item)
                                <div class="p-3 mb-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Item #{{ $loop->iteration }}</small>
                                    </div>
                                    @if(is_array($item))
                                        @if(isset($item['content']))
                                            <pre class="mb-1 small font-monospace p-2 bg-white rounded border" style="white-space: pre-wrap; word-break: break-all;">{{ $item['content'] }}</pre>
                                        @elseif(isset($item['details']))
                                            <pre class="mb-1 small font-monospace p-2 bg-white rounded border" style="white-space: pre-wrap; word-break: break-all;">{{ $item['details'] }}</pre>
                                        @else
                                            <pre class="mb-1 small font-monospace p-2 bg-white rounded border" style="white-space: pre-wrap; word-break: break-all;">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @endif
                                        @if(!empty($item['file_path']))
                                            <a href="{{ $item['file_path'] }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="fa-solid fa-download me-1"></i> Download Attachment
                                            </a>
                                        @endif
                                    @else
                                        <pre class="mb-0 small font-monospace p-2 bg-white rounded border" style="white-space: pre-wrap; word-break: break-all;">{{ $item }}</pre>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="p-3 bg-light rounded-3 border">
                                <pre class="mb-0 small font-monospace" style="white-space: pre-wrap; word-break: break-all;">{{ $order->delivered_items }}</pre>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-top py-2 px-4 bg-light bg-opacity-50">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
