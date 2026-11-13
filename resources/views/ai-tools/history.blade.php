@extends('layouts.app')

@section('header_title', 'AI TOOLS — My Orders')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>AI TOOLS Order History</h4>
            <p class="text-muted small mb-0">All your AI tool purchases</p>
        </div>
        <a href="{{ route('ai-tools.index') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Browse Tools
        </a>
    </div>

    @if($orders->count())
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:var(--bg-muted,#f8fafc);">
                        <tr>
                            <th class="px-3 py-3 small fw-bold text-muted">Order #</th>
                            <th class="py-3 small fw-bold text-muted">Product</th>
                            <th class="py-3 small fw-bold text-muted">Qty</th>
                            <th class="py-3 small fw-bold text-muted">Amount</th>
                            <th class="py-3 small fw-bold text-muted">Status</th>
                            <th class="py-3 small fw-bold text-muted">Date</th>
                            <th class="py-3 small fw-bold text-muted"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td class="px-3 py-3">
                                <span class="fw-mono small">{{ $order->order_number }}</span>
                            </td>
                            <td class="py-3">
                                <span class="fw-semibold small">{{ $order->product->name ?? 'N/A' }}</span>
                            </td>
                            <td class="py-3 small">{{ $order->quantity }}</td>
                            <td class="py-3 small fw-bold">₦{{ number_format($order->total_paid_ngn, 2) }}</td>
                            <td class="py-3">
                                <span class="badge bg-{{ $order->status_badge }} bg-opacity-15 text-{{ $order->status_badge }} rounded-pill">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="py-3 small text-muted">{{ $order->created_at->format('d M Y, H:i') }}</td>
                            <td class="py-3">
                                <a href="{{ route('ai-tools.order.detail', $order->id) }}" class="btn btn-outline-primary btn-sm" style="border-radius:6px;font-size:0.75rem;">
                                    View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($orders->hasPages())
    <div class="d-flex justify-content-center mt-4">{{ $orders->links() }}</div>
    @endif

    @else
        <div class="text-center py-5">
            <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No orders yet</h5>
            <p class="text-muted small">Your AI Tools purchases will appear here.</p>
            <a href="{{ route('ai-tools.index') }}" class="btn btn-primary btn-sm mt-2">Browse AI TOOLS</a>
        </div>
    @endif
</div>
@endsection
