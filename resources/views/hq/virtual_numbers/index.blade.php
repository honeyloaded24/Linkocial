@extends('layouts.app')

@section('header_title', 'HQ: Virtual Numbers & Financial Records')

@section('content')
<div class="container-fluid">
    <!-- Header Title & Action -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Virtual Number Verification Orders</h4>
            <small class="text-muted">Track virtual phone orders, SMS verifications, wholesale provider costs, and net profits.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hq.virtual-numbers.pricing') }}" class="btn btn-outline-info btn-sm rounded-pill px-3">
                <i class="fa-solid fa-tags me-1"></i> Pricing & Markup
            </a>
            <a href="{{ route('hq.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Stat Cards -->
        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Orders</span>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-phone fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">{{ number_format($vnStats['total']) }}</h3>
                    <div class="small text-muted d-flex gap-2 flex-wrap" style="font-size: 0.72rem;">
                        <span class="text-success"><i class="fa-solid fa-check"></i> {{ number_format($vnStats['completed']) }} Done</span>
                        <span class="text-warning"><i class="fa-solid fa-clock"></i> {{ number_format($vnStats['pending']) }} Pend</span>
                        <span class="text-danger"><i class="fa-solid fa-times"></i> {{ number_format($vnStats['failed']) }} Cancel</span>
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
                    <h3 class="fw-bold mb-1 text-success">₦{{ number_format($vnStats['revenue'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        Fulfilled number activations
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Provider Cost</span>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-server fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-warning">₦{{ number_format($vnStats['provider_cost'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        SmsPool/Grizzly (Rate: ₦{{ number_format($vnStats['exchange_rate'], 2) }}/$)
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
                    <h3 class="fw-bold mb-1 text-info">₦{{ number_format($vnStats['profit'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        Realized net gross profit
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="premium-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">Virtual Number Orders</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('hq.virtual-numbers', array_merge(request()->except('status'), ['status' => 'all'])) }}" class="btn {{ request('status') === 'all' || !request('status') ? 'btn-primary-custom' : 'btn-light' }} btn-sm rounded-pill px-3">All</a>
                <a href="{{ route('hq.virtual-numbers', array_merge(request()->except('status'), ['status' => 'pending'])) }}" class="btn {{ request('status') === 'pending' ? 'btn-primary-custom' : 'btn-light' }} btn-sm rounded-pill px-3">Pending</a>
                <a href="{{ route('hq.virtual-numbers', array_merge(request()->except('status'), ['status' => 'received'])) }}" class="btn {{ request('status') === 'received' ? 'btn-primary-custom' : 'btn-light' }} btn-sm rounded-pill px-3">Received</a>
                <a href="{{ route('hq.virtual-numbers', array_merge(request()->except('status'), ['status' => 'canceled'])) }}" class="btn {{ request('status') === 'canceled' ? 'btn-primary-custom' : 'btn-light' }} btn-sm rounded-pill px-3">Canceled</a>
            </div>
        </div>

        <form action="{{ route('hq.virtual-numbers') }}" method="GET" class="row g-3 mb-4 align-items-center">
            <input type="hidden" name="status" value="{{ request('status', 'all') }}">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search phone number, user, or service..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control border-secondary bg-transparent" value="{{ request('from_date') }}" title="From Date" style="color: var(--text-main);">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control border-secondary bg-transparent" value="{{ request('to_date') }}" title="To Date" style="color: var(--text-main);">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary-custom w-100" type="submit"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            </div>
            @if(request('search') || (request('status') && request('status') !== 'all') || request('from_date') || request('to_date'))
            <div class="col-md-2">
                <a href="{{ route('hq.virtual-numbers') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="text-uppercase small text-muted">
                <tr>
                    <th>User</th>
                    <th>Service / Region</th>
                    <th>Phone Number</th>
                    <th>Code/SMS</th>
                    <th>Financials</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <div class="fw-bold fs-7">{{ $order->user->username }}</div>
                        <div class="small text-muted">{{ $order->user->email }}</div>
                    </td>
                    <td>
                        <div class="fw-bold fs-7 text-capitalize">{{ str_replace('_', ' ', $order->product) }}</div>
                        <div class="small text-muted text-uppercase d-flex align-items-center gap-1">
                            <span>{{ $order->country }} ({{ $order->operator }})</span>
                            <span class="badge {{ $order->server === 'server2' ? 'bg-secondary' : 'bg-danger' }} text-white" style="font-size: 0.60rem; padding: 2px 5px;">{{ $order->server === 'server2' ? 'Server 2' : 'Server 1 (HOT)' }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="fw-bold font-monospace px-2 py-1 rounded" style="background: rgba(255,255,255,0.05); color: var(--text-main);">{{ $order->phone_number }}</span>
                    </td>
                    <td>
                        @if($order->sms_code || $order->sms_text)
                            <span class="text-success fw-bold font-monospace">{{ $order->sms_code }}</span>
                            <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $order->sms_text }}">{{ $order->sms_text }}</div>
                        @else
                            <span class="text-muted small fst-italic">Waiting...</span>
                        @endif
                    </td>
                    <td>
                        <div class="fw-bold">{{ money($order->cost) }}</div>
                        @if($order->coupon_code)
                            <div class="small text-success fw-bold"><i class="fa-solid fa-tag"></i> {{ $order->coupon_code }} (-{{ money($order->discount) }})</div>
                        @endif
                        <div class="small text-muted">Cost: {{ money($order->provider_cost) }}</div>
                    </td>
                    <td>
                        @php
                            $bg = match($order->status) {
                                'pending' => 'warning',
                                'received', 'finished' => 'success',
                                'canceled', 'banned' => 'danger',
                                default => 'secondary'
                            };
                            $icon = match($order->status) {
                                'pending' => 'fa-clock',
                                'received', 'finished' => 'fa-check',
                                'canceled', 'banned' => 'fa-times',
                                default => 'fa-circle'
                            };
                        @endphp
                        <span class="badge bg-{{ $bg }} bg-opacity-10 text-{{ $bg }} d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                            <i class="fa-solid {{ $icon }}"></i> {{ strtoupper($order->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="small">{{ $order->created_at->format('M d, Y') }}</div>
                        <div class="small text-muted">{{ $order->created_at->format('H:i') }}</div>
                    </td>
                    <td class="text-end">
                        @if(in_array($order->status, ['pending', 'received']))
                        <form action="{{ route('hq.virtual-numbers.refund', $order->id) }}" method="POST" onsubmit="return confirm('Cancel this number and REFUND the user?');">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">Cancel & Refund</button>
                        </form>
                        @else
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>Closed</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-ghost fs-1 mb-3 opacity-25"></i>
                        <p>No virtual number orders found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>
</div>
@endsection
