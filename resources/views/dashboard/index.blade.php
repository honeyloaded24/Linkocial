@extends('layouts.app')

@section('header_title', 'Dashboard')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $currentCurrency = \App\Models\Currency::where('code', Session::get('currency_code', $user->currency_code ?? 'USD'))->first() ?? \App\Models\Currency::where('is_default', true)->first();
@endphp
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

    <!-- Balance Hero Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="premium-card border-0 shadow-sm position-relative overflow-hidden" 
                 style="border-radius: 35px; border: 1px solid var(--border-color) !important;">
                
                <!-- Soft Glow Backgrounds -->
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.05), transparent 50%); pointer-events: none;"></div>
                <div class="position-absolute bottom-0 end-0 w-100 h-100" style="background: radial-gradient(circle at 100% 100%, rgba(236, 72, 153, 0.05), transparent 50%); pointer-events: none;"></div>

                <div class="p-4 p-md-5 d-flex flex-column flex-md-row align-items-center justify-content-between position-relative" style="z-index: 1;">
                    <div class="text-center text-md-start mb-4 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-wallet small"></i>
                            </div>
                            <h6 class="text-uppercase small text-muted fw-bold mb-0" style="letter-spacing: 2px; font-size: 0.7rem;">Account Liquidity</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3">
                        <h1 class="display-4 fw-bold mb-0" style="color: var(--text-main); font-family: var(--heading-font); letter-spacing: -1px;">{{ money($user->balance) }}</h1>
                        </div>
                        <p class="text-muted mt-3 mb-0 small fw-medium">
                            <i class="fa-solid fa-circle-check text-success me-1"></i> Verified & Protected Funds
                        </p>
                    </div>
                    <div class="d-flex flex-column align-items-center align-items-md-end gap-3">
                        <a href="{{ route('wallet.index') }}" class="btn btn-premium btn-lg rounded-pill px-5 shadow-lg d-flex align-items-center gap-2" style="background: #1e293b; border: none; font-size: 0.95rem; padding-top: 0.9rem; padding-bottom: 0.9rem; color: #ffffff !important;">
                            <span class="text-white">Fund Your Wallet</span>
                            <i class="fa-solid fa-arrow-right small opacity-50 text-white"></i>
                        </a>
                        <div class="d-flex gap-4 mt-2">
                            <div class="text-center">
                                <div class="text-muted small fw-bold">Daily Limit</div>
                                <div class="text-main fw-bold small">Unlimited</div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small fw-bold">Currency</div>
                                <div class="text-main fw-bold small">{{ $currentCurrency->code }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Stat Cards -->
        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm" style="border-radius: 20px;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px; background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                        <i class="fa-solid fa-money-bill-wave small"></i>
                    </div>
                    <h6 class="text-muted mb-0 fw-bold small text-uppercase" style="letter-spacing: 1px; font-size: 0.65rem;">Total Spent</h6>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h4 class="fw-bold mb-0" style="font-size: 1.2rem;">{{ money($totalSpent) }}</h4>
                    @if($growth > 0)
                        <span class="text-success fw-bold" style="font-size: 0.65rem;"><i class="fa-solid fa-arrow-trend-up"></i> +{{ number_format($growth, 0) }}%</span>
                    @elseif($growth < 0)
                        <span class="text-danger fw-bold" style="font-size: 0.65rem;"><i class="fa-solid fa-arrow-trend-down"></i> {{ number_format($growth, 0) }}%</span>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm" style="border-radius: 20px;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px; background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                        <i class="fa-solid fa-wallet small"></i>
                    </div>
                    <h6 class="text-muted mb-0 fw-bold small text-uppercase" style="letter-spacing: 1px; font-size: 0.65rem;">Total Deposits</h6>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h4 class="fw-bold mb-0" style="font-size: 1.2rem;">{{ money($totalDeposits) }}</h4>
                    <span class="text-muted fw-bold" style="font-size: 0.65rem;">Added</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm" style="border-radius: 20px;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px; background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="fa-solid fa-spinner fa-spin-slow small"></i>
                    </div>
                    <h6 class="text-muted mb-0 fw-bold small text-uppercase" style="letter-spacing: 1px; font-size: 0.65rem;">Active Orders</h6>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h4 class="fw-bold mb-0" style="font-size: 1.2rem;">{{ number_format($activeOrders) }}</h4>
                    <span class="text-muted fw-bold" style="font-size: 0.65rem;">Running</span>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 border-0 shadow-sm" style="border-radius: 20px;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px; background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fa-solid fa-circle-check small"></i>
                    </div>
                    <h6 class="text-muted mb-0 fw-bold small text-uppercase" style="letter-spacing: 1px; font-size: 0.65rem;">Completed</h6>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h4 class="fw-bold mb-0" style="font-size: 1.2rem;">{{ number_format($completedOrders) }}</h4>
                    <span class="text-muted fw-bold" style="font-size: 0.65rem;">Total</span>
                </div>
            </div>
        </div>
<!-- Channels Section -->
<div class="row mb-4 pt-3">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-3 px-1">
            <h6 class="fw-bold mb-0 text-uppercase small text-muted" style="letter-spacing: 1px;">Service Channels</h6>
        </div>
        <div class="row g-2 g-md-3">
            @foreach($channels as $channel)
            <div class="col-6 col-md-auto">
                <a href="{{ route('orders.create', ['channel' => $channel['name']]) }}" class="text-decoration-none">
                    <div class="premium-card p-2 px-3 d-flex align-items-center gap-3 h-100" style="min-width: 100%; md-min-width: 160px; transition: all 0.3s ease; cursor: pointer; border-radius: 50px;" onmouseover="this.style.transform='scale(1.05)'; this.style.borderColor='var(--primary-color)'" onmouseout="this.style.transform='scale(1)'; this.style.borderColor='var(--border-color)'">
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 38px; height: 38px; background: {{ $channel['color'] }}15; color: {{ $channel['color'] }};">
                            <i class="fa-brands {{ $channel['icon'] }} fa-lg"></i>
                        </div>
                        <div class="fw-bold small text-muted text-truncate">{{ $channel['name'] }}</div>
                    </div>
                </a>
            </div>
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
                            <td class="fw-bold small">{{ money((float)$order->charge) }}</td>
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
