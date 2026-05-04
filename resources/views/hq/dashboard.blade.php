@extends('layouts.app')

@section('header_title', 'HQ Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Section 1: User & Order Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="{{ route('hq.users') }}" class="text-decoration-none">
                <div class="premium-card p-3 h-100 border-start border-primary border-4 transition-all">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Users</h6>
                            <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalUsers) }}</h3>
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
                            <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalOrders) }}</h3>
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
                            <h3 class="fw-bold mb-0 text-dark">{{ number_format($pendingOrders) }}</h3>
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
                            <h3 class="fw-bold mb-0 text-dark">{{ number_format($completedOrders) }}</h3>
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
                <h3 class="fw-bold mb-0 text-primary">₦ {{ number_format($totalDeposited, 2) }}</h3>
                <small class="text-muted mt-2 d-block">Lifetime success deposits</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm">
                <h6 class="text-danger small fw-bold text-uppercase mb-1">Amount Spent</h6>
                <h3 class="fw-bold mb-0 text-danger">₦ {{ number_format($totalSpent, 2) }}</h3>
                <small class="text-muted mt-2 d-block">Total order volume</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm">
                <h6 class="text-warning small fw-bold text-uppercase mb-1">Cost of Services</h6>
                <h3 class="fw-bold mb-0 text-warning">₦ {{ number_format($totalCost, 2) }}</h3>
                <small class="text-muted mt-2 d-block">Provider expenses</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card p-3 h-100 bg-success bg-opacity-10 border-0">
                <h6 class="text-success small fw-bold text-uppercase mb-1">Platform Profit</h6>
                <h3 class="fw-bold mb-0 text-success">₦ {{ number_format($totalProfit, 2) }}</h3>
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
                <h6 class="fw-bold text-muted mb-4 text-uppercase small">Profit Trend (₦)</h6>
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
                        <h4 class="fw-bold mb-0">₦ {{ number_format($totalUserBalance, 2) }}</h4>
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
                        <h5 class="fw-bold mb-0 text-success">₦ {{ number_format($totalCommissionEarned, 2) }}</h5>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block mb-1">Pending</small>
                        <h5 class="fw-bold mb-0 text-warning">₦ {{ number_format($pendingCommission, 2) }}</h5>
                    </div>
                    <div class="col-3">
                        <small class="text-muted d-block mb-1">Paid</small>
                        <h5 class="fw-bold mb-0 text-info">₦ {{ number_format($totalCommissionWithdrawn, 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Recent Orders -->
    <div class="premium-card p-0 overflow-hidden shadow-sm">
        <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center bg-light bg-opacity-10">
            <h6 class="fw-bold mb-0">Recent Platform Orders</h6>
            <a href="{{ route('hq.orders') }}" class="btn btn-sm btn-outline-primary px-3 py-1 fw-bold">View All Orders</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light bg-opacity-25">
                    <tr>
                        <th class="px-3">Order ID</th>
                        <th>User</th>
                        <th>Service</th>
                        <th>Charge</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $recentOrders = \App\Models\Order::with(['user', 'service'])->latest()->take(8)->get();
                    @endphp
                    @forelse($recentOrders as $order)
                    <tr>
                        <td class="px-3 fw-bold">#{{ $order->id }}</td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $order->user->first_name }} {{ $order->user->last_name }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ $order->user->email }}</small>
                        </td>
                        <td class="small">{{ Str::limit($order->service->name, 40) }}</td>
                        <td class="fw-bold">₦ {{ number_format((float)$order->charge, 2) }}</td>
                        <td>
                            @php
                                $cleanStatus = strtolower(str_replace(' ', '_', $order->status));
                                $isActive = in_array($cleanStatus, ['processing', 'in_progress']);
                                
                                $statusClasses = [
                                    'completed' => 'bg-success text-white',
                                    'processing' => 'text-white',
                                    'pending' => 'bg-warning text-dark',
                                    'failed' => 'bg-danger text-white',
                                    'canceled' => 'bg-secondary text-white',
                                    'partial' => 'bg-warning text-white'
                                ];
                                $statusClass = $statusClasses[$cleanStatus] ?? 'bg-secondary text-white';
                            @endphp
                            <span class="badge {{ $statusClass }} rounded-pill px-2 py-1 fw-bold shadow-sm" 
                                  style="{{ $isActive ? 'background-color: #ff8c00 !important; color: white !important;' : '' }} font-size: 0.65rem;">
                                {{ strtoupper(str_replace('_', ' ', $cleanStatus)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted small">No recent orders found across the platform.</td>
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
