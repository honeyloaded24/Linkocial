@extends('layouts.app')

@section('header_title', 'Order Details #' . $order->id)

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('hq.orders') }}" class="btn btn-light rounded-pill px-3 shadow-sm border">
            Back to All Orders
        </a>
    </div>

    <div class="row">
        <!-- Main Order Details -->
        <div class="col-lg-8">
            <div class="premium-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <h4 class="fw-bold mb-0">Order Information</h4>
                    @php
                        $cleanStatus = strtolower(str_replace(' ', '_', $order->status));
                        $statusMap = [
                            'pending'     => ['bg' => 'warning', 'icon' => 'fa-clock'],
                            'processing'  => ['bg' => 'orange',  'icon' => 'fa-spinner fa-spin'],
                            'in_progress' => ['bg' => 'orange',  'icon' => 'fa-spinner fa-spin'],
                            'completed'   => ['bg' => 'success', 'icon' => 'fa-check-circle'],
                            'failed'      => ['bg' => 'danger',  'icon' => 'fa-times-circle'],
                            'canceled'    => ['bg' => 'secondary','icon' => 'fa-ban'],
                            'partial'     => ['bg' => 'warning', 'icon' => 'fa-adjust'],
                        ];
                        $s = $statusMap[$cleanStatus] ?? ['bg' => 'secondary', 'icon' => 'fa-question-circle'];
                        $isActive = in_array($cleanStatus, ['processing', 'in_progress']);
                    @endphp
                    <span class="badge {{ $isActive ? '' : 'bg-opacity-10' }} px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2 shadow-sm" 
                          style="{{ $isActive ? 'background-color: #ff8c00 !important; color: white !important;' : 'background-color: rgba(var(--bs-'.$s['bg'].'-rgb), 0.1) !important; color: var(--bs-'.$s['bg'].') !important;' }} font-size: 0.9rem; font-weight: 800;">
                        <i class="fa-solid {{ $s['icon'] }}"></i>
                        {{ strtoupper(str_replace('_', ' ', $cleanStatus)) }}
                    </span>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Service</label>
                        <p class="fw-bold mb-0 text-dark fs-5">{{ $order->service->name ?? 'Deleted Service' }}</p>
                        <small class="text-muted">Internal ID: #{{ $order->service_id }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Target Link</label>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ $order->link }}" target="_blank" class="fw-bold text-primary text-break">{{ $order->link }}</a>
                            <button class="btn btn-sm btn-light rounded px-2" onclick="navigator.clipboard.writeText('{{ $order->link }}')" style="font-size: 0.7rem;">Copy</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Quantity</label>
                        <p class="fw-bold mb-0 text-dark fs-5">{{ number_format($order->quantity) }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Start Count</label>
                        <p class="fw-bold mb-0 text-dark fs-5">{{ $order->start_count ?? '---' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small text-uppercase fw-bold mb-1 d-block">User Charge</label>
                        <p class="fw-bold mb-0 text-dark fs-5">{{ money((float)$order->charge, true, 'USD') }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Gross Margin</label>
                        <p class="fw-bold mb-0 text-success fs-5">{{ money((float)$order->charge, true, 'USD') }} <span class="text-muted" style="font-size: 0.8rem;">/ {{ money((float)$order->provider_cost, true, 'USD') }}</span></p>
                    </div>
                </div>

                @if($order->error_log)
                <div class="mt-5 pt-4 border-top">
                    <h6 class="fw-bold text-danger mb-3"><i class="fa-solid fa-terminal me-2"></i> Technical Log / Error Trace</h6>
                    <div class="bg-dark text-success p-3 rounded font-monospace small" style="white-space: pre-wrap; line-height: 1.6;">{{ $order->error_log }}</div>
                </div>
                @endif
            </div>

            <!-- Order History Timeline -->
            <div class="premium-card p-4 mb-4">
                <h6 class="fw-bold mb-4"><i class="fa-solid fa-list-ul me-2 text-primary"></i> Order Lifecycle History</h6>
                
                @if($order->logs->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-history mb-2 fs-2 opacity-25"></i>
                        <p class="mb-0">No logs recorded for this order yet.</p>
                    </div>
                @else
                    <div class="timeline">
                        @foreach($order->logs()->latest()->get() as $log)
                            <div class="timeline-item pb-4 position-relative">
                                <div class="timeline-marker position-absolute rounded-circle bg-primary" style="width: 12px; height: 12px; left: -6px; top: 5px;"></div>
                                <div class="ms-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            Status: {{ ucfirst($log->new_status) }}
                                        </h6>
                                        <small class="text-muted fw-bold">{{ $log->created_at->format('M d, H:i:s') }}</small>
                                    </div>
                                    <p class="text-muted small mb-1">{{ $log->message }}</p>
                                    @if($log->payload)
                                        <a href="#payload_{{ $log->id }}" class="text-decoration-none small" data-bs-toggle="collapse">
                                            <i class="fa-solid fa-code me-1"></i> View System Payload
                                        </a>
                                        <div class="collapse mt-2" id="payload_{{ $log->id }}">
                                            <pre class="bg-light p-2 rounded small border mb-0" style="font-size: 0.75rem;">{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Provider Connection Details -->
            <div class="premium-card p-4">
                <h6 class="fw-bold mb-4">Provider Integration Details</h6>
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-4">
                            <label class="text-muted small text-uppercase fw-bold mb-1 d-block">SMM Provider</label>
                            <p class="fw-bold mb-0 text-dark">{{ $order->provider->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-4">
                            <label class="text-muted small text-uppercase fw-bold mb-1 d-block">External Order ID</label>
                            <p class="fw-bold mb-0 text-dark">{{ $order->provider_order_id ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-4">
                            <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Provider Cost</label>
                            <p class="fw-bold mb-0 text-dark">{{ money((float)$order->provider_cost) }}</p>
                            <small class="text-muted" style="font-size: 0.65rem;">
                                for {{ number_format($order->quantity) }} units 
                                <span class="d-block mt-1">({{ money(((float)$order->provider_cost / $order->quantity) * 1000) }}/1k rate)</span>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-4">
                            <label class="text-muted small text-uppercase fw-bold mb-1 d-block">Remains</label>
                            <p class="fw-bold mb-0 text-dark">{{ $order->remains ?? '---' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions & Stats -->
        <div class="col-lg-4">
            <!-- User Info Card -->
            <div class="premium-card p-4 mb-4">
                <h6 class="fw-bold mb-3">Customer Details</h6>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-user fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">{{ $order->user->username ?? 'N/A' }}</h6>
                        <small class="text-muted">{{ $order->user->email ?? 'N/A' }}</small>
                    </div>
                </div>
                <a href="{{ route('hq.users.show', $order->user_id ?? 0) }}" class="btn btn-outline-primary w-100 rounded-pill py-2 small fw-bold">
                    View User Profile
                </a>
            </div>

            <!-- Management Actions -->
            <div class="premium-card p-4">
                <h6 class="fw-bold mb-4">Order Management</h6>
                
                <div class="d-grid gap-2">
                    @if(in_array($order->status, ['pending', 'failed', 'processing', 'in_progress']))
                    <form action="{{ route('hq.orders.complete', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 py-3 rounded-4 fw-bold shadow-sm" onclick="return confirm('Manually mark as completed?')">
                            Force Complete
                        </button>
                    </form>
                    @endif

                    @if($order->status !== 'canceled')
                    <form action="{{ route('hq.orders.cancel', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100 py-3 rounded-4 fw-bold shadow-sm mt-2" onclick="return confirm('Cancel and refund user?')">
                            Cancel & Refund
                        </button>
                    </form>
                    @endif

                    @if(in_array($order->status, ['failed', 'canceled']))
                    <form action="{{ route('hq.orders.resubmit', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm mt-2" onclick="return confirm('Resubmit this order?')">
                            Resubmit to Provider
                        </button>
                    </form>
                    @endif

                    @if(in_array($order->status, ['completed', 'partial']) && $order->provider_order_id)
                    <form action="{{ route('hq.orders.refill', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning w-100 py-3 rounded-4 fw-bold shadow-sm mt-2" onclick="return confirm('Request a REFILL from the provider for this order?')">
                            Request Refill
                        </button>
                    </form>
                    @endif
                </div>

                <div class="mt-4 pt-3 border-top text-center">
                    <p class="text-muted small mb-0">Order Placed: {{ $order->created_at->format('M d, Y @ H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        border-left: 2px solid rgba(var(--bs-primary-rgb), 0.1);
        margin-left: 10px;
    }
    .timeline-item:last-child {
        padding-bottom: 0 !important;
    }
</style>
@endsection
