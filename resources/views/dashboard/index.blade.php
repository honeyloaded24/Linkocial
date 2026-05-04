@extends('layouts.app')

@section('header_title', 'Dashboard Overview')

@section('content')
<div class="container-fluid">
    <!-- Broadcast / News Slider -->
    @php
        $activeBroadcasts = \App\Models\Broadcast::where('is_active', true)->latest()->get();
    @endphp
    @if($activeBroadcasts->isNotEmpty())
    <div id="broadcastCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-inner rounded-4 shadow-sm overflow-hidden border border-secondary border-opacity-10">
            @foreach($activeBroadcasts as $index => $broadcast)
            <div class="carousel-item @if($index === 0) active @endif" data-bs-interval="5000">
                <div class="p-3 px-4 d-flex align-items-center justify-content-between" style="background: linear-gradient(90deg, var(--bg-surface) 0%, rgba(99, 102, 241, 0.05) 100%); min-height: 70px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-{{ $broadcast->type }} bg-opacity-10 text-{{ $broadcast->type }} d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                            <i class="fa-solid @if($broadcast->type == 'danger') fa-triangle-exclamation @elseif($broadcast->type == 'warning') fa-circle-exclamation @elseif($broadcast->type == 'success') fa-check-circle @else fa-bullhorn @endif"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-muted small text-uppercase" style="letter-spacing: 1px; font-size: 0.65rem;">Platform Announcement</div>
                            <div class="text-main fw-bold" style="font-size: 0.95rem;">{!! nl2br(e($broadcast->message)) !!}</div>
                        </div>
                    </div>
                    @if($activeBroadcasts->count() > 1)
                    <div class="d-none d-md-flex gap-2">
                        <button class="btn btn-xs btn-light border rounded-circle" type="button" data-bs-target="#broadcastCarousel" data-bs-slide="prev">
                            <i class="fa-solid fa-chevron-left fa-xs"></i>
                        </button>
                        <button class="btn btn-xs btn-light border rounded-circle" type="button" data-bs-target="#broadcastCarousel" data-bs-slide="next">
                            <i class="fa-solid fa-chevron-right fa-xs"></i>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="row g-4 mb-4">
    <!-- Stat Cards -->
    <div class="col-12 col-md-3">
        <div class="premium-card p-4 h-100 bg-primary-custom text-white border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-white opacity-75 mb-0 fw-bold">Current Balance</h6>
                <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-wallet fa-lg text-white"></i>
                </div>
            </div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-naira-sign fs-4"></i> {{ number_format(Auth::user()->balance, 2) }}</h2>
            <a href="{{ route('wallet.index') }}" class="text-white small fw-bold text-decoration-none mt-2 d-inline-block">
                Fund Wallet <i class="fa-solid fa-circle-plus ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="premium-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted mb-0 fw-bold">Total Spent</h6>
                <div class="bg-primary-custom rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; opacity: 0.9;">
                    <i class="fa-solid fa-money-bill-wave fa-lg text-white"></i>
                </div>
            </div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-naira-sign fs-4"></i> {{ number_format($totalSpent, 2) }}</h2>
            @if($growth > 0)
                <small class="text-success fw-bold"><i class="fa-solid fa-arrow-trend-up"></i> +{{ number_format($growth, 1) }}% this week</small>
            @elseif($growth < 0)
                <small class="text-danger fw-bold"><i class="fa-solid fa-arrow-trend-down"></i> {{ number_format($growth, 1) }}% this week</small>
            @else
                <small class="text-muted fw-bold">Stable this week</small>
            @endif
        </div>
    </div>
    
    <div class="col-12 col-md-3">
        <div class="premium-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted mb-0 fw-bold">Active Orders</h6>
                <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-dark" style="width: 48px; height: 48px; opacity: 0.9;">
                    <i class="fa-solid fa-spinner fa-spin fa-lg"></i>
                </div>
            </div>
            <h2 class="fw-bold mb-1">{{ number_format($activeOrders) }}</h2>
            <small class="text-muted fw-bold">Currently processing</small>
        </div>
    </div>
    
    <div class="col-12 col-md-3">
        <div class="premium-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted mb-0 fw-bold">Completed Orders</h6>
                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; opacity: 0.9;">
                    <i class="fa-solid fa-circle-check fa-lg"></i>
                </div>
            </div>
            <h2 class="fw-bold mb-1">{{ number_format($completedOrders) }}</h2>
            <small class="text-muted fw-bold">Lifetime completed</small>
        </div>
    </div>
<!-- Channels Section -->
<div class="row mb-4 pt-3">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-3 px-1">
            <h6 class="fw-bold mb-0 text-uppercase small text-muted" style="letter-spacing: 1px;">Service Channels</h6>
        </div>
        <div class="d-flex flex-wrap gap-3 pb-2">
            @foreach($channels as $channel)
            <a href="{{ route('orders.create', ['channel' => $channel['name']]) }}" class="text-decoration-none">
                <div class="premium-card p-2 px-3 d-flex align-items-center gap-3" style="min-width: 160px; transition: all 0.3s ease; cursor: pointer; border-radius: 50px;" onmouseover="this.style.transform='scale(1.05)'; this.style.borderColor='var(--primary-color)'" onmouseout="this.style.transform='scale(1)'; this.style.borderColor='var(--border-color)'">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; background: {{ $channel['color'] }}15; color: {{ $channel['color'] }};">
                        <i class="fa-brands {{ $channel['icon'] }} fa-lg"></i>
                    </div>
                    <div class="fw-bold small text-muted">{{ $channel['name'] }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Orders Table -->
    <div class="col-12">
        <div class="premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Recent Orders</h5>
                <a href="{{ route('orders.index') }}" class="btn btn-sm bg-primary-custom text-white rounded-pill px-4 py-2 shadow-sm fw-bold">
                    View All
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Order ID</th>
                            <th>Service</th>
                            <th>Target Link</th>
                            <th>Quantity</th>
                            <th>Charge</th>
                            <th class="pe-4 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                        <tr>
                            <td class="ps-4"><span class="text-muted fw-bold">#{{ $order->id }}</span></td>
                            <td>
                                <div class="fw-bold small">{{ $order->service->name }}</div>
                                <small class="text-muted" style="font-size: 0.7rem;">{{ $order->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <a href="{{ $order->link }}" target="_blank" class="text-decoration-none small" style="color: var(--primary-color)">
                                    {{ Str::limit($order->link, 30) }} <i class="fa-solid fa-external-link ms-1" style="font-size: 0.6rem;"></i>
                                </a>
                            </td>
                            <td class="fw-bold small">{{ number_format($order->quantity) }}</td>
                            <td class="fw-bold small">₦ {{ number_format((float)$order->charge, 2) }}</td>
                            <td class="pe-4 text-end">
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
                                <span class="badge {{ $statusClass }} rounded-pill px-3 py-2 fw-bold shadow-sm" 
                                      style="{{ $isActive ? 'background-color: #ff8c00 !important; color: white !important;' : '' }} font-size: 0.65rem;">
                                    {{ strtoupper(str_replace('_', ' ', $cleanStatus)) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="opacity-20 mb-3"><i class="fa-solid fa-box-open fa-3x"></i></div>
                                <h6 class="text-muted fw-bold">No orders found yet.</h6>
                                <p class="text-muted small">Your recent service orders will appear here.</p>
                                <a href="{{ route('orders.create') }}" class="btn btn-sm bg-primary-custom text-white px-4 py-2 rounded-pill mt-2">Place First Order</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
