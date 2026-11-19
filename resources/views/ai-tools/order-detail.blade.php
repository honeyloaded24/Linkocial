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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
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
                            <span class="fw-semibold text-end" style="max-width:60%;">{{ $order->product->name ?? $order->product_name ?? 'Product details unavailable' }}</span>
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
                            <span id="status-pill" class="status-pill {{ str_replace('_', '', $order->status) }}">
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
                        <div id="completed-at-row" class="d-flex justify-content-between">
                            <span class="text-muted">Completed</span>
                            <span id="completed-at-val">{{ $order->completed_at->format('d M Y, H:i') }}</span>
                        </div>
                        @else
                        <div id="completed-at-row" class="d-flex justify-content-between" style="display:none!important;">
                            <span class="text-muted">Completed</span>
                            <span id="completed-at-val"></span>
                        </div>
                        @endif
                    </div>

                    @if(in_array($order->status, ['pending','processing']))
                    <div id="processing-alert" class="alert alert-warning small mt-3 mb-0">
                        <i class="fa fa-spinner fa-spin me-1"></i>
                        Your order is being processed. Credentials will appear here automatically once delivered.
                        <br><small class="text-muted">Checking for updates every few seconds…</small>
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

                    <div id="cred-container">
                    @if($order->status === 'completed' && $order->items->count())
                        @foreach($order->items as $item)
                        <div class="mb-3">
                            @if($order->items->count() > 1)
                                <p class="text-muted small mb-1 fw-semibold">Item {{ $item->item_index + 1 }}</p>
                            @endif
                            <div class="cred-box">
                                <pre class="mb-0 font-monospace text-wrap" style="white-space:pre-wrap;">{{ $item->code ?? 'No code available' }}</pre>
                                <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-text="{{ $item->code }}" onclick="copyCred(this)">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            @if($item->details)
                            @php
                                $details = is_string($item->details) ? json_decode($item->details, true) : $item->details;
                            @endphp
                            @if(is_array($details))
                            <div class="mt-2 p-2 rounded small bg-light border">
                                @foreach($details as $key => $val)
                                    @if(!in_array($key, ['code']) && is_scalar($val))
                                    <div class="d-flex gap-2 mb-1">
                                        <span class="text-muted fw-semibold" style="min-width:100px;">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
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
                    </div>{{-- #cred-container --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyCred(btn) {
    var text = btn.dataset.clipboardText;
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

// Copy text from a sibling <pre> element by its ID
function copyById(btn, elemId) {
    var el = document.getElementById(elemId);
    if (!el) return;
    var text = el.innerText || el.textContent || '';
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

// Live AJAX polling — replaces dumb 15s page reload
@if(in_array($order->status, ['pending','processing']))
(function() {
    var POLL_INTERVAL = 7000;        // 7 seconds between requests
    var MAX_POLLS     = 60;          // stop after ~7 min (60 × 7s)
    var pollCount     = 0;
    var statusUrl     = "{{ route('ai-tools.order.status', $order->id) }}";
    var TERMINAL      = ['completed','failed','refunded','under_review'];

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderCredentials(data) {
        var container = document.getElementById('cred-container');
        if (!container) return;

        if (data.status === 'completed' && data.items_count > 0) {
            var html = '';
            data.items.forEach(function(item, idx) {
                var copyId = 'cred-' + idx;
                html += '<div class="mb-3">';
                if (data.items.length > 1) {
                    html += '<p class="text-muted small mb-1 fw-semibold">Item ' + (idx + 1) + '</p>';
                }
                html += '<div class="cred-box">' +
                    '<pre class="mb-0 font-monospace text-wrap" id="' + copyId + '" style="white-space:pre-wrap;">' + escapeHtml(item.code) + '</pre>' +
                    '<button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyById(this,\'' + copyId + '\')">' +
                    '<i class="fa fa-copy"></i></button>' +
                    '</div>';

                if (item.details) {
                    try {
                        var det = typeof item.details === 'string' ? JSON.parse(item.details) : item.details;
                        if (det && typeof det === 'object') {
                            html += '<div class="mt-2 p-2 rounded small bg-light border">';
                            Object.keys(det).forEach(function(k) {
                                if (k !== 'code' && typeof det[k] !== 'object') {
                                    html += '<div class="d-flex gap-2 mb-1">' +
                                        '<span class="text-muted fw-semibold" style="min-width:100px;">' + escapeHtml(k.replace(/_/g,' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); })) + ':</span>' +
                                        '<span class="fw-semibold font-monospace">' + escapeHtml(String(det[k])) + '</span>' +
                                        '</div>';
                                }
                            });
                            html += '</div>';
                        }
                    } catch(e) {}
                }
                html += '</div>';
            });
            html += '<div class="alert alert-success small mb-0"><i class="fa fa-shield-halved me-1"></i>Keep your credentials secure. Do not share them publicly.</div>';
            container.innerHTML = html;

            // Update status pill
            var pill = document.getElementById('status-pill');
            if (pill) {
                pill.className = 'status-pill completed';
                pill.innerHTML = '<i class="fa-solid fa-circle-check"></i> Completed';
            }
            // Hide processing alert
            var alert = document.getElementById('processing-alert');
            if (alert) alert.style.display = 'none';

            // Update completed_at if present
            if (data.completed_at) {
                var ca = document.getElementById('completed-at-row');
                if (ca) ca.style.display = '';
                var caVal = document.getElementById('completed-at-val');
                if (caVal) caVal.textContent = data.completed_at;
            }

        } else if (data.status === 'failed') {
            container.innerHTML = '<div class="alert alert-danger small">Order failed. Please contact support with your order number.</div>';
            var pill = document.getElementById('status-pill');
            if (pill) { pill.className = 'status-pill failed'; pill.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Failed'; }
        } else if (data.status === 'refunded') {
            container.innerHTML = '<div class="alert alert-info small"><i class="fa fa-rotate-left me-1"></i>This order was refunded. Your wallet has been credited.</div>';
            var pill = document.getElementById('status-pill');
            if (pill) { pill.className = 'status-pill refunded'; pill.innerHTML = '<i class="fa-solid fa-rotate-left"></i> Refunded'; }
        }
    }

    function poll() {
        if (pollCount >= MAX_POLLS) {
            var alert = document.getElementById('processing-alert');
            if (alert) alert.innerHTML = '<i class="fa fa-exclamation-circle me-1"></i>Your order is taking longer than expected. Please <a href="/support">contact support</a> with your order number.';
            return;
        }
        pollCount++;

        fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                renderCredentials(data);
                if (!data.is_terminal) {
                    setTimeout(poll, POLL_INTERVAL);
                }
            })
            .catch(function() {
                // On network error retry after a longer delay
                setTimeout(poll, POLL_INTERVAL * 2);
            });
    }

    // Start polling after a short initial delay
    setTimeout(poll, 5000);
})();
@endif
</script>
@endpush
