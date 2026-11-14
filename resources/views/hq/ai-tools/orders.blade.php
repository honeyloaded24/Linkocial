@extends('layouts.app')

@section('header_title', 'AI TOOLS — Orders')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>AI TOOLS — Orders</h4>
    </div>

    {{-- Filters --}}
    <form method="GET" class="d-flex flex-wrap gap-2 mb-4 align-items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Order # or email…" class="form-control form-control-sm" style="max-width:220px;">
        <select name="status" class="form-select form-select-sm" style="max-width:160px;">
            <option value="">All Status</option>
            @foreach(['pending','processing','completed','refunded','failed','under_review'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="max-width:150px;">
        <input type="date" name="to"   value="{{ request('to') }}"   class="form-control form-control-sm" style="max-width:150px;">
        <button class="btn btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['search','status','from','to']))
            <a href="{{ route('hq.ai-tools.orders') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        @endif
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead style="background:var(--bg-muted,#f8fafc);">
                        <tr>
                            <th class="px-3 py-2">Order #</th>
                            <th class="py-2">Customer</th>
                            <th class="py-2">Product</th>
                            <th class="py-2">Qty</th>
                            <th class="py-2">Revenue</th>
                            <th class="py-2">Cost</th>
                            <th class="py-2">Profit</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Date</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="px-3 font-monospace">{{ $order->order_number }}</td>
                            <td>
                                <div>{{ $order->user->first_name ?? '' }} {{ $order->user->last_name ?? '' }}</div>
                                <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($order->product->name ?? 'N/A', 25) }}</td>
                            <td>{{ $order->quantity }}</td>
                            <td class="fw-bold">₦{{ number_format($order->total_paid_ngn, 2) }}</td>
                            <td class="text-muted">₦{{ number_format($order->provider_cost_ngn, 2) }}</td>
                            <td class="{{ $order->profit_ngn >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                ₦{{ number_format($order->profit_ngn, 2) }}
                            </td>
                            <td><span class="badge bg-{{ $order->status_badge }} bg-opacity-15 text-{{ $order->status_badge }}">{{ $order->status_label }}</span></td>
                            <td class="text-muted">{{ $order->created_at->format('d M Y, H:i') }}</td>
                            <td><a href="{{ route('hq.ai-tools.orders.show', $order->id) }}" class="btn btn-xs btn-outline-secondary" style="font-size:0.72rem;padding:2px 8px;">View</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-4 text-muted">No orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($orders->hasPages())
    <div class="d-flex justify-content-center mt-4">{{ $orders->links() }}</div>
    @endif
</div>
@endsection


