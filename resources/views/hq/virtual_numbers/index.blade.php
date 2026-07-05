@extends('layouts.app')

@section('header_title', 'HQ: Virtual Numbers')

@section('content')
<div class="row g-3 mb-4">
    <!-- Stat Cards -->
    <div class="col-md-3">
        <div class="premium-card p-3 d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 rounded-circle p-3 text-primary me-3">
                <i class="fa-solid fa-phone fs-4"></i>
            </div>
            <div>
                <p class="text-muted small mb-0 fw-bold text-uppercase">Total Orders</p>
                <h4 class="mb-0 fw-bold">{{ number_format($vnStats['total']) }}</h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="premium-card p-3 d-flex align-items-center">
            <div class="bg-success bg-opacity-10 rounded-circle p-3 text-success me-3">
                <i class="fa-solid fa-check fs-4"></i>
            </div>
            <div>
                <p class="text-muted small mb-0 fw-bold text-uppercase">Received / Finished</p>
                <h4 class="mb-0 fw-bold">{{ number_format($vnStats['received'] + $vnStats['finished']) }}</h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="premium-card p-3 d-flex align-items-center">
            <div class="bg-warning bg-opacity-10 rounded-circle p-3 text-warning me-3">
                <i class="fa-solid fa-spinner fs-4"></i>
            </div>
            <div>
                <p class="text-muted small mb-0 fw-bold text-uppercase">Pending Active</p>
                <h4 class="mb-0 fw-bold">{{ number_format($vnStats['pending']) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="premium-card p-3 d-flex align-items-center">
            <div class="bg-info bg-opacity-10 rounded-circle p-3 text-info me-3">
                <i class="fa-solid fa-chart-line fs-4"></i>
            </div>
            <div>
                <p class="text-muted small mb-0 fw-bold text-uppercase">Total Profit (USD)</p>
                <h4 class="mb-0 fw-bold">${{ number_format($vnStats['total_revenue'] - $vnStats['total_cost'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="premium-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Virtual Number Orders</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('hq.virtual-numbers') }}" class="btn {{ request('status') ? 'btn-light' : 'btn-primary-custom' }} btn-sm rounded-pill px-3">All</a>
            <a href="{{ route('hq.virtual-numbers', ['status' => 'pending']) }}" class="btn {{ request('status') === 'pending' ? 'btn-primary-custom' : 'btn-light' }} btn-sm rounded-pill px-3">Pending</a>
            <a href="{{ route('hq.virtual-numbers', ['status' => 'received']) }}" class="btn {{ request('status') === 'received' ? 'btn-primary-custom' : 'btn-light' }} btn-sm rounded-pill px-3">Received</a>
        </div>
    </div>

    <form class="mb-4">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-start-0 bg-transparent" placeholder="Search phone number, user, or service..." value="{{ request('search') }}">
            <button class="btn btn-primary-custom px-4" type="submit">Search</button>
        </div>
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
                        <div class="small text-muted text-uppercase">{{ $order->country }} ({{ $order->operator }})</div>
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
                        <div class="fw-bold">${{ number_format($order->cost, 2) }}</div>
                        @if($order->coupon_code)
                            <div class="small text-success fw-bold"><i class="fa-solid fa-tag"></i> {{ $order->coupon_code }} (-${{ number_format($order->discount, 2) }})</div>
                        @endif
                        <div class="small text-muted">Cost: ${{ number_format($order->provider_cost, 2) }}</div>
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
@endsection
