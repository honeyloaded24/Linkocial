@extends('layouts.app')

@section('header_title', 'Order ' . $order->order_number)

@push('styles')
<style>
    .cred-box {
        background: var(--bg-muted,#f8fafc);
        border: 1px solid var(--border-color,#e2e8f0);
        border-radius: 10px;
        padding: 1rem;
        font-family: monospace;
        font-size: 0.9rem;
        word-break: break-all;
        position: relative;
    }
    .copy-btn { position: absolute; top: 8px; right: 8px; }
    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 0.4rem 1rem; border-radius: 20px; font-weight: 700; font-size: 0.85rem;
    }
    .status-pill.completed { background:#d1fae5; color:#065f46; }
    .status-pill.processing, .status-pill.pending { background:#fef3c7; color:#92400e; }
    .status-pill.failed    { background:#fee2e2; color:#991b1b; }
    .status-pill.refunded  { background:#dbeafe; color:#1e40af; }
    .status-pill.under_review { background:#f3f4f6; color:#374151; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <div class="mb-3">
        <a href="{{ route('ai-tools.history') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i>Back to Orders
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-4">
        {{-- Order summary --}}
        <div class="col-lg-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="fa fa-receipt me-2 text-primary"></i>Order Summary</h5>

                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Order Number</span>
                            <span class="fw-bold font-monospace">{{ $order->order_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Product</span>
                            <span class="fw-semibold text-end" style="max-width:60%;">{{ $order->product->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Quantity</span>
                            <span class="fw-semibold">{{ $order->quantity }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Amount Paid</span>
                            <span class="fw-bold text-primary">₦{{ number_format($order->total_paid_ngn, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status</span>
                            <span class="status-pill {{ str_replace('_', '', $order->status) }}">
                                @php
                                    $icons = [
                                        'completed'    => 'fa-circle-check',
                                        'processing'   => 'fa-spinner fa-spin',
                                        'pending'      => 'fa-clock',
                                        'failed'       => 'fa-circle-xmark',
                                        'refunded'     => 'fa-rotate-left',
                                        'under_review' => 'fa-eye',
                                    ];
                                @endphp
                                <i class="fa-solid {{ $icons[$order->status] ?? 'fa-circle' }}"></i>
                                {{ $order->status_label }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Date Placed</span>
                            <span>{{ $order->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        @if($order->completed_at)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Completed</span>
                            <span>{{ $order->completed_at->format('d M Y, H:i') }}</span>
                        </div>
                        @endif
                    </div>

                    @if(in_array($order->status, ['pending','processing']))
                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="fa fa-spinner fa-spin me-1"></i>
                        Your order is being processed. Credentials will appear here automatically once delivered.
                        <br><small class="text-muted">Refresh this page to check for updates.</small>
                    </div>
                    @endif

                    @if($order->status === 'failed' && $order->failure_reason)
                    <div class="alert alert-danger small mt-3 mb-0">
                        <strong>Failed:</strong> Please contact support with your order number.
                    </div>
                    @endif

                    @if($order->status === 'refunded')
                    <div class="alert alert-info small mt-3 mb-0">
                        <i class="fa fa-rotate-left me-1"></i>
                        This order was refunded. ₦{{ number_format($order->total_paid_ngn, 2) }} has been returned to your wallet.
                    </div>
                    @endif

                    @if($order->status === 'under_review')
                    <div class="alert alert-secondary small mt-3 mb-0">
                        <i class="fa fa-eye me-1"></i>
                        This order is under review. Our team will resolve it and contact you if needed.
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Delivered credentials --}}
        <div class="col-lg-7">
            <div class="card border shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-key me-2 text-warning"></i>Delivered Credentials</h5>

                    @if($order->status === 'completed' && $order->items->count())
                        @foreach($order->items as $item)
                        <div class="mb-3">
                            @if($order->items->count() > 1)
                                <p class="text-muted small mb-1 fw-semibold">Item {{ $item->item_index + 1 }}</p>
                            @endif
                            <div class="cred-box">
                                {{ $item->code ?? 'No code available' }}
                                <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyText(this, '{{ addslashes($item->code ?? '') }}')">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            @if($item->details)
                            @php
                                $details = json_decode($item->details, true);
                            @endphp
                            @if($details && is_array($details) && count($details) > 1)
                            <div class="mt-2">
                                @foreach($details as $key => $val)
                                    @if($key !== 'code' && !is_array($val))
                                    <div class="d-flex gap-2 small mb-1">
                                        <span class="text-muted" style="min-width:80px;">{{ ucfirst($key) }}:</span>
                                        <span class="fw-semibold font-monospace">{{ $val }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                            @endif
                            @endif
                        </div>
                        @endforeach

                        <div class="alert alert-success small mb-0">
                            <i class="fa fa-shield-halved me-1"></i>
                            Keep your credentials secure. Do not share them publicly.
                        </div>

                    @elseif($order->status === 'completed')
                        <div class="text-center py-3 text-muted">
                            <i class="fa fa-box-open fa-2x mb-2"></i>
                            <p class="small">Order completed but no credentials were returned. Please contact support.</p>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fa-solid fa-hourglass-half fa-2x mb-2"></i>
                            <p class="small">Credentials will appear here once your order is fulfilled.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyText(btn, text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-check"></i>';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-primary');
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}

// Auto-refresh if still processing
@if(in_array($order->status, ['pending','processing']))
setTimeout(function() { location.reload(); }, 15000);
@endif
</script>
@endpush
