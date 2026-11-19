@extends('layouts.app')

@section('header_title', 'AI TOOLS — Order ' . $order->order_number)

@section('content')
<style>
    [data-theme="dark"] .hq-ai-order-detail .bg-light,
    [data-bs-theme="dark"] .hq-ai-order-detail .bg-light {
        background-color: rgba(255,255,255,0.06) !important;
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--border-color, #334155) !important;
    }
    [data-theme="dark"] .hq-ai-order-detail .text-dark,
    [data-bs-theme="dark"] .hq-ai-order-detail .text-dark {
        color: var(--text-main, #f8fafc) !important;
    }
    [data-theme="dark"] .hq-ai-order-detail .btn-light,
    [data-bs-theme="dark"] .hq-ai-order-detail .btn-light {
        background-color: var(--bg-surface, #1e293b) !important;
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--border-color, #334155) !important;
    }
</style>
<div class="container-fluid hq-ai-order-detail px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <a href="{{ route('hq.ai-tools.orders') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i>Back to Orders
        </a>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Check Live Provider Status --}}
            @if(!empty($order->provider_order_id) && $order->status !== 'completed')
            <form method="POST" action="{{ route('hq.ai-tools.orders.sync', $order->id) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-sync me-1"></i>Check Provider Status
                </button>
            </form>
            @endif

            {{-- Manual Fulfill Button --}}
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#fulfillModal">
                <i class="fa-solid fa-key me-1"></i>{{ $order->status === 'completed' ? 'Re-deliver Credentials' : 'Manual Fulfill & Deliver' }}
            </button>

            {{-- Refund Button --}}
            @if($order->status !== 'refunded')
            <form method="POST" action="{{ route('hq.ai-tools.orders.refund', $order->id) }}" class="d-inline" onsubmit="return confirm('Refund ₦{{ number_format($order->total_paid_ngn, 2) }} to customer wallet?');">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fa fa-rotate-left me-1"></i>Refund Customer
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

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
                        <dd class="col-7">{{ $order->user->first_name ?? '' }} {{ $order->user->last_name ?? '' }}<br><small class="text-muted">{{ $order->user->email ?? '' }}</small></dd>

                        <dt class="col-5 text-muted">Product</dt>
                        <dd class="col-7 fw-semibold">{{ $order->product->name ?? $order->product_name ?? 'N/A' }}</dd>

                        <dt class="col-5 text-muted">Provider Product ID</dt>
                        <dd class="col-7 font-monospace">{{ $order->provider_product_id ?? '—' }}</dd>

                        <dt class="col-5 text-muted">Quantity</dt>
                        <dd class="col-7 fw-semibold">{{ $order->quantity }}</dd>

                        <dt class="col-5 text-muted">Provider Order ID</dt>
                        <dd class="col-7 font-monospace">{{ $order->provider_order_id ?? '—' }}</dd>

                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7"><span class="badge bg-{{ $order->status_badge }} bg-opacity-15 text-{{ $order->status_badge }}">{{ $order->status_label }}</span></dd>

                        <dt class="col-5 text-muted">Provider Status</dt>
                        <dd class="col-7"><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $order->provider_status ?? 'NOT_SUBMITTED' }}</span></dd>

                        <dt class="col-5 text-muted">Date Placed</dt>
                        <dd class="col-7">{{ $order->created_at->format('d M Y, H:i:s') }}</dd>

                        @if($order->completed_at)
                        <dt class="col-5 text-muted">Completed At</dt>
                        <dd class="col-7 text-success">{{ $order->completed_at->format('d M Y, H:i:s') }}</dd>
                        @endif

                        @if($order->refunded_at)
                        <dt class="col-5 text-muted">Refunded At</dt>
                        <dd class="col-7 text-info">{{ $order->refunded_at->format('d M Y, H:i:s') }}</dd>
                        @endif
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
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0"><i class="fa-solid fa-key me-2 text-warning"></i>Delivered Credentials</h6>
                        <button type="button" class="btn btn-xs btn-outline-primary" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#fulfillModal">
                            <i class="fa fa-pen me-1"></i>Edit / Deliver
                        </button>
                    </div>

                    @if($order->items->count())
                        @foreach($order->items as $item)
                        <div class="mb-3 p-3 rounded" style="background:var(--bg-muted,#f8fafc);border:1px solid var(--border-color,#e2e8f0);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted fw-semibold">Item {{ $item->item_index + 1 }}</span>
                                <button class="btn btn-xs btn-outline-secondary" style="font-size:0.7rem;padding:1px 6px;" data-clipboard-text="{{ $item->code }}" onclick="copyAdminCred(this)">
                                    <i class="fa fa-copy me-1"></i>Copy
                                </button>
                            </div>
                            <pre class="font-monospace fw-bold text-wrap mb-1" style="white-space:pre-wrap;font-size:0.85rem;">{{ $item->code }}</pre>

                            @if($item->details)
                            @php $details = is_string($item->details) ? json_decode($item->details, true) : $item->details; @endphp
                            @if(is_array($details))
                                <div class="mt-2 pt-2 border-top small">
                                    @foreach($details as $k => $v)
                                        @if(!in_array($k, ['code']) && is_scalar($v))
                                        <div class="d-flex gap-2 text-muted">
                                            <span style="min-width:90px;">{{ ucfirst($k) }}:</span>
                                            <span class="text-dark font-monospace">{{ $v }}</span>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            @endif
                        </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning small mb-0">
                            <i class="fa fa-info-circle me-1"></i>No credentials delivered yet. You can fulfill this manually below or check provider status.
                        </div>
                    @endif
                </div>
            </div>

            @if($order->failure_reason)
            <div class="card border-0 shadow-sm mb-3 border-danger">
                <div class="card-body">
                    <h6 class="fw-bold text-danger mb-2">Failure Reason / Notes</h6>
                    <p class="small mb-0">{{ $order->failure_reason }}</p>
                </div>
            </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Provider Response (Raw)</h6>
                    <pre class="small" style="max-height:220px;overflow-y:auto;background:var(--bg-muted,#f8fafc);padding:1rem;border-radius:8px;">{{ json_encode($order->provider_response, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Manual Fulfill Modal --}}
<div class="modal fade" id="fulfillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('hq.ai-tools.orders.fulfill', $order->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-key me-2 text-primary"></i>Manual Order Fulfillment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Enter the credentials (account logins, license keys, or tokens) to deliver to the customer. For multiple items/accounts, enter one per line.
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Credentials / Codes (Required)</label>
                        <textarea name="credentials" rows="5" class="form-control form-control-sm font-monospace" placeholder="e.g.&#10;user@linkocial.com:SecretPass123&#10;or&#10;KEY-XXXX-YYYY-ZZZZ" required>{{ $order->items->pluck('code')->implode("\n") }}</textarea>
                        <div class="form-text small">Each line will be treated as an individual delivered credential item.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Customer Instructions / Notes (Optional)</label>
                        <textarea name="instructions" rows="2" class="form-control form-control-sm" placeholder="e.g. Log in at openai.com using the credentials above. Do not change the password."></textarea>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="fa fa-shield-halved me-1"></i>Credentials will be encrypted at rest in the database and displayed immediately on the customer's order page.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-paper-plane me-1"></i>Save & Deliver Credentials
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyAdminCred(btn) {
    var text = btn.dataset.clipboardText;
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-check me-1"></i>Copied';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}
</script>
@endpush


