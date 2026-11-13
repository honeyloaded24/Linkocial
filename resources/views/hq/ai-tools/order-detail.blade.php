@extends('layouts.app')

@section('header_title', 'AI TOOLS — Order ' . $order->order_number)

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="mb-3">
        <a href="{{ route('hq.ai-tools.orders') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i>Back to Orders
        </a>
    </div>

    <div class="row g-4">
        {{-- Order info --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Order Details</h5>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Order #</dt>
                        <dd class="col-7 font-monospace fw-bold">{{ $order->order_number }}</dd>

                        <dt class="col-5 text-muted">Customer</dt>
                        <dd class="col-7">{{ $order->user->first_name ?? '' }} {{ $order->user->last_name ?? '' }}<br><small>{{ $order->user->email ?? '' }}</small></dd>

                        <dt class="col-5 text-muted">Product</dt>
                        <dd class="col-7">{{ $order->product->name ?? 'N/A' }}</dd>

                        <dt class="col-5 text-muted">Provider ID</dt>
                        <dd class="col-7">{{ $order->product->provider_product_id ?? '—' }}</dd>

                        <dt class="col-5 text-muted">Quantity</dt>
                        <dd class="col-7">{{ $order->quantity }}</dd>

                        <dt class="col-5 text-muted">Provider Order ID</dt>
                        <dd class="col-7 font-monospace">{{ $order->provider_order_id ?? '—' }}</dd>

                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7"><span class="badge bg-{{ $order->status_badge }} bg-opacity-15 text-{{ $order->status_badge }}">{{ $order->status_label }}</span></dd>

                        <dt class="col-5 text-muted">Provider Status</dt>
                        <dd class="col-7">{{ $order->provider_status ?? '—' }}</dd>

                        <dt class="col-5 text-muted">Date</dt>
                        <dd class="col-7">{{ $order->created_at->format('d M Y, H:i:s') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Financial Breakdown</h6>
                    <dl class="row small mb-0">
                        <dt class="col-6 text-muted">Customer Paid (NGN)</dt>
                        <dd class="col-6 fw-bold text-primary">₦{{ number_format($order->total_paid_ngn, 2) }}</dd>

                        <dt class="col-6 text-muted">Provider Cost (USDT)</dt>
                        <dd class="col-6">${{ number_format($order->total_price_usdt, 4) }}</dd>

                        <dt class="col-6 text-muted">Exchange Rate Used</dt>
                        <dd class="col-6">₦{{ number_format($order->exchange_rate, 2) }}/USDT</dd>

                        <dt class="col-6 text-muted">Provider Cost (NGN)</dt>
                        <dd class="col-6 text-warning">₦{{ number_format($order->provider_cost_ngn, 2) }}</dd>

                        <dt class="col-6 text-muted">Net Profit</dt>
                        <dd class="col-6 fw-bold {{ $order->profit_ngn >= 0 ? 'text-success' : 'text-danger' }}">₦{{ number_format($order->profit_ngn, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Credentials & provider response --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-key me-2 text-warning"></i>Delivered Credentials</h6>
                    @if($order->items->count())
                        @foreach($order->items as $item)
                        <div class="mb-3 p-3 rounded" style="background:var(--bg-muted,#f8fafc);border:1px solid var(--border-color,#e2e8f0);">
                            <div class="small text-muted mb-1">Item {{ $item->item_index + 1 }}</div>
                            <div class="font-monospace fw-bold">{{ $item->code ?? '(encrypted)' }}</div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted small">No credentials delivered yet.</p>
                    @endif
                </div>
            </div>

            @if($order->failure_reason)
            <div class="card border-0 shadow-sm mb-3 border-danger">
                <div class="card-body">
                    <h6 class="fw-bold text-danger mb-2">Failure Reason</h6>
                    <p class="small mb-0">{{ $order->failure_reason }}</p>
                </div>
            </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Provider Response (Raw)</h6>
                    <pre class="small" style="max-height:200px;overflow-y:auto;background:var(--bg-muted,#f8fafc);padding:1rem;border-radius:8px;">{{ json_encode($order->provider_response, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


