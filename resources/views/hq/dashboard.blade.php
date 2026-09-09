@extends('layouts.app')

@section('header_title', 'HQ Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Quick User Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="premium-card p-3 p-md-4 border-0" style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(236,72,153,0.05) 100%);">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-primary"><i class="fa-solid fa-user-magnifying-glass me-2"></i>Quick User Search</h5>
                        <p class="text-muted small mb-0">Search users by email or username to check their account.</p>
                    </div>
                    <form action="{{ route('hq.users') }}" method="GET" class="d-flex" style="max-width: 400px; width: 100%;">
                        <div class="input-group shadow-sm rounded-pill overflow-hidden">
                            <input type="text" name="search" class="form-control bg-white border-0 px-4" placeholder="Enter email or username..." required style="box-shadow: none;">
                            <button class="btn btn-primary text-white px-4 border-0" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: User & Order Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="{{ route('hq.users') }}" class="text-decoration-none">
                <div class="premium-card p-3 h-100 border-start border-primary border-4 transition-all">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Users</h6>
                            <h3 class="fw-bold mb-0 text-main">{{ number_format($totalUsers) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary">
                            <i class="fa-solid fa-users fa-xl"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('hq.orders', ['status' => 'all']) }}" class="text-decoration-none">
                <div class="premium-card p-3 h-100 border-start border-info border-4 transition-all">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Orders</h6>
                            <h3 class="fw-bold mb-0 text-main">{{ number_format($totalOrders) }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-3 text-info">
                            <i class="fa-solid fa-cart-shopping fa-xl"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('hq.orders', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="premium-card p-3 h-100 border-start border-warning border-4 transition-all">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-1">Pending Orders</h6>
                            <h3 class="fw-bold mb-0 text-main">{{ number_format($pendingOrders) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 text-warning">
                            <i class="fa-solid fa-clock fa-xl"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('hq.orders', ['status' => 'completed']) }}" class="text-decoration-none">
                <div class="premium-card p-3 h-100 border-start border-success border-4 transition-all">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-1">Completed Orders</h6>
                            <h3 class="fw-bold mb-0 text-main">{{ number_format($completedOrders) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 text-success">
                            <i class="fa-solid fa-circle-check fa-xl"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Section 2: Financial Health -->
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-line me-2 text-primary"></i> Financial Performance</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="premium-card p-3 h-100 bg-primary bg-opacity-10 border-0">
                <h6 class="text-primary small fw-bold text-uppercase mb-1">Amount Deposited</h6>
                <h3 class="fw-bold mb-0 text-primary">{{ money($totalDeposited, true, 'USD') }}</h3>
                <small class="text-muted mt-2 d-block">Lifetime success deposits</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm">
                <h6 class="text-danger small fw-bold text-uppercase mb-1">Amount Spent</h6>
                <h3 class="fw-bold mb-0 text-danger">{{ money($totalSpent, true, 'USD') }}</h3>
                <small class="text-muted mt-2 d-block">Total order volume</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm">
                <h6 class="text-warning small fw-bold text-uppercase mb-1">Cost of Services</h6>
                <h3 class="fw-bold mb-0 text-warning">{{ money($totalCost, true, 'USD') }}</h3>
                <small class="text-muted mt-2 d-block">Provider expenses</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card p-3 h-100 bg-success bg-opacity-10 border-0">
                <h6 class="text-success small fw-bold text-uppercase mb-1">Platform Profit</h6>
                <h3 class="fw-bold mb-0 text-success">{{ money($totalProfit, true, 'USD') }}</h3>
                <small class="text-muted mt-2 d-block">Net margin</small>
            </div>
        </div>
    </div>

    <!-- Section 2.5: Growth Charts -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="premium-card p-4">
                <h6 class="fw-bold text-muted mb-4 text-uppercase small">Platform Growth (Last 30 Days)</h6>
                <div style="height: 320px; width: 100%;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="premium-card p-4">
                <h6 class="fw-bold text-muted mb-4 text-uppercase small">Profit Trend (USD)</h6>
                <div style="height: 320px; width: 100%;">
                    <canvas id="profitChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: User Liquidity & Affiliate -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="premium-card p-4 h-100">
                <h6 class="fw-bold text-muted mb-4 text-uppercase small">User Liquidity</h6>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <small class="text-muted d-block mb-1">Unused Deposits</small>
                        <h4 class="fw-bold mb-0">{{ money($totalUserBalance, true, 'USD') }}</h4>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Pending Services</small>
                        <h4 class="fw-bold mb-0 text-warning">{{ $pendingServices }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="premium-card p-4 h-100">
                <h6 class="fw-bold text-muted mb-4 text-uppercase small">Affiliate & Referrals</h6>
                <div class="row text-center">
                    <div class="col-3 border-end">
                        <small class="text-muted d-block mb-1">Referrals</small>
                        <h5 class="fw-bold mb-0">{{ number_format($totalReferrals) }}</h5>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block mb-1">Earned</small>
                        <h5 class="fw-bold mb-0 text-success">{{ money($totalCommissionEarned, true, 'USD') }}</h5>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block mb-1">Pending</small>
                        <h5 class="fw-bold mb-0 text-warning">{{ money($pendingCommission, true, 'USD') }}</h5>
                    </div>
                    <div class="col-3">
                        <small class="text-muted d-block mb-1">Paid</small>
                        <h5 class="fw-bold mb-0 text-info">{{ money($totalCommissionWithdrawn, true, 'USD') }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Recent Orders -->
    <div class="premium-card p-0 overflow-hidden shadow-sm">
        <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center bg-light bg-opacity-10">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                <h6 class="fw-bold mb-0">Recent Platform Orders</h6>
                <span class="badge bg-secondary bg-opacity-25 text-muted rounded-pill px-2 py-1 small">All Products</span>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle px-3 py-1 fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    View All Orders
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item small" href="{{ route('hq.orders') }}"><i class="fa-solid fa-rocket text-primary me-2"></i>Social Boost Orders</a></li>
                    <li><a class="dropdown-item small" href="{{ route('hq.virtual-numbers') }}"><i class="fa-solid fa-sim-card text-info me-2"></i>Virtual Numbers Orders</a></li>
                    <li><a class="dropdown-item small" href="{{ route('hq.vtu') }}"><i class="fa-solid fa-bolt text-success me-2"></i>VTU & Utility Orders</a></li>
                    <li><a class="dropdown-item small" href="{{ route('hq.logs.orders') }}"><i class="fa-solid fa-file-invoice text-warning me-2"></i>Logs & Accounts Orders</a></li>
                </ul>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light bg-opacity-25">
                    <tr>
                        <th class="px-3">Order ID</th>
                        <th>Type</th>
                        <th>User</th>
                        <th>Service / Details</th>
                        <th>Charge</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td class="px-3 fw-bold">
                            <a href="{{ $order->url }}" class="text-decoration-none fw-bold text-primary">
                                {{ $order->display_id }}
                            </a>
                        </td>
                        <td>
                            @if($order->type_key === 'smm')
                                <span class="badge rounded-pill px-2 py-1 fw-bold" style="background-color: rgba(99, 102, 241, 0.12); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.25); font-size: 0.7rem;">
                                    <i class="fa-solid fa-rocket me-1"></i>Social Boost
                                </span>
                            @elseif($order->type_key === 'vn')
                                <span class="badge rounded-pill px-2 py-1 fw-bold" style="background-color: rgba(14, 165, 233, 0.12); color: #0284c7; border: 1px solid rgba(14, 165, 233, 0.25); font-size: 0.7rem;">
                                    <i class="fa-solid fa-sim-card me-1"></i>Virtual Number
                                </span>
                            @elseif($order->type_key === 'vtu')
                                <span class="badge rounded-pill px-2 py-1 fw-bold" style="background-color: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); font-size: 0.7rem;">
                                    <i class="fa-solid fa-bolt me-1"></i>{{ $order->type }}
                                </span>
                            @else
                                <span class="badge rounded-pill px-2 py-1 fw-bold" style="background-color: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.25); font-size: 0.7rem;">
                                    <i class="fa-solid fa-file-invoice me-1"></i>Logs & Accounts
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($order->user)
                                <a href="{{ route('hq.users.show', $order->user->id) }}" class="text-decoration-none text-dark">
                                    <div class="fw-bold" style="font-size: 0.85rem;">
                                        {{ trim(($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? '')) ?: $order->user->username }}
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $order->user->email }}</small>
                                </a>
                            @else
                                <div class="text-muted small">Deleted User</div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-truncate" style="max-width: 260px; font-size: 0.85rem;" title="{{ $order->service_name }}">
                                {{ Str::limit($order->service_name, 45) }}
                            </div>
                            @if($order->detail)
                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-circle-info me-1" style="font-size: 0.65rem;"></i>{{ $order->detail }}
                                </small>
                            @endif
                        </td>
                        <td class="fw-bold text-dark">
                            @if(isset($order->charge_ngn) && $order->charge_ngn > 0)
                                <div>{{ money($order->charge, true, 'USD') }}</div>
                                <small class="text-muted fw-normal" style="font-size: 0.75rem;">(₦{{ number_format($order->charge_ngn, 2) }})</small>
                            @else
                                {{ money($order->charge, true, 'USD') }}
                            @endif
                        </td>
                        <td>
                            @php
                                $cleanStatus = strtolower(str_replace(' ', '_', $order->status));
                                $isActive = in_array($cleanStatus, ['processing', 'in_progress']);
                                $isSuccess = in_array($cleanStatus, ['completed', 'finished', 'received']);
                                $isFailed = in_array($cleanStatus, ['failed']);
                                $isCanceled = in_array($cleanStatus, ['canceled', 'cancelled', 'timeout']);
                                $isRefunded = in_array($cleanStatus, ['refunded']);
                                $isPending = in_array($cleanStatus, ['pending']);
                            @endphp

                            @if($isSuccess)
                                <span class="badge bg-success text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fa-solid fa-check me-1"></i>COMPLETED
                                </span>
                            @elseif($isActive)
                                <span class="badge rounded-pill px-2 py-1 fw-bold shadow-sm" style="background-color: #ff8c00 !important; color: white !important; font-size: 0.65rem;">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i>IN PROGRESS
                                </span>
                            @elseif($isPending)
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fa-solid fa-clock me-1"></i>PENDING
                                </span>
                            @elseif($isRefunded)
                                <span class="badge bg-dark text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fa-solid fa-rotate-left me-1"></i>REFUNDED
                                </span>
                            @elseif($isFailed)
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fa-solid fa-xmark me-1"></i>FAILED
                                </span>
                            @elseif($isCanceled)
                                <span class="badge bg-secondary text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                    <i class="fa-solid fa-ban me-1"></i>CANCELED
                                </span>
                            @else
                                <span class="badge bg-secondary text-white rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                    {{ strtoupper(str_replace('_', ' ', $cleanStatus)) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="small text-muted" title="{{ $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '' }}">
                                {{ $order->created_at ? $order->created_at->diffForHumans() : 'N/A' }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ $order->url }}" class="btn btn-sm btn-outline-primary px-2 py-1 rounded-pill" title="View in Management">
                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted small">No recent orders found across the platform.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = {!! json_encode($chartData['labels']) !!};
    
    // Growth Chart (Orders & Users)
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Orders',
                    data: {!! json_encode($chartData['orders']) !!},
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'New Users',
                    data: {!! json_encode($chartData['users']) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Profit Chart
    const profitCtx = document.getElementById('profitChart').getContext('2d');
    new Chart(profitCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Profit',
                data: {!! json_encode($chartData['profits']) !!},
                backgroundColor: '#f59e0b',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
