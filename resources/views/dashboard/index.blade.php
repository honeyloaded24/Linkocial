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
    <div class="row mb-3">
        <div class="col-12">
            <div class="border-0 shadow-sm position-relative overflow-hidden" 
                 style="border-radius: 16px; background: linear-gradient(135deg, #090e36 0%, #030617 100%);">
                
                <div class="p-3 p-md-4 d-flex flex-column flex-md-row align-items-start justify-content-between position-relative" style="z-index: 1;">
                    <div class="text-start mb-0">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="text-white-50 small fw-medium mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">Available Balance</h6>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <h1 class="fs-2 fw-medium mb-0 text-white" style="font-family: var(--heading-font); letter-spacing: -0.5px;">
                                <span id="actual-balance">{{ money($user->balance) }}</span>
                                <span id="hidden-balance" style="display: none;">🤑🤑🤑🤑</span>
                                <i class="fa-solid fa-eye-slash fa-xs text-white-50 ms-2" id="toggle-balance-icon" style="font-size: 0.4em; cursor: pointer;" onclick="toggleBalance()"></i>
                            </h1>
                        </div>
                        
                        <a href="{{ route('wallet.index') }}" class="btn btn-sm bg-white text-dark rounded-pill px-3 py-1 mt-3 fw-bold d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 0.85rem; transition: transform 0.2s;">
                            <i class="fa-solid fa-plus-circle" style="color: #090e36;"></i> Add Funds
                        </a>
                    </div>
                    
                    <!-- Decorative Right Side -->
                    <div class="d-none d-md-flex align-items-center justify-content-center position-absolute end-0 top-50 translate-middle-y pe-4" style="pointer-events: none;">
                        <i class="fa-solid fa-wallet text-white opacity-10" style="font-size: 5rem; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.5)); transform: rotate(-15deg);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Promotional Slider (Standardized ~120px Height) -->
    @if(isset($sliders) && $sliders->isNotEmpty())
    <div class="row mb-3">
        <div class="col-12">
            <div id="dashboardSliderCarousel" class="carousel slide carousel-fade shadow-sm overflow-hidden position-relative" data-bs-ride="carousel" data-bs-interval="4500" style="border-radius: 16px; height: 120px; background: var(--bg-surface);">
                @if($sliders->count() > 1)
                <div class="carousel-indicators mb-2" style="z-index: 5;">
                    @foreach($sliders as $idx => $slide)
                        <button type="button" data-bs-target="#dashboardSliderCarousel" data-bs-slide-to="{{ $idx }}" class="{{ $idx === 0 ? 'active' : '' }}" aria-current="{{ $idx === 0 ? 'true' : 'false' }}" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    @endforeach
                </div>
                @endif
                <div class="carousel-inner h-100">
                    @foreach($sliders as $idx => $slide)
                    <div class="carousel-item {{ $idx === 0 ? 'active' : '' }} h-100">
                        @if($slide->target_url)
                            <a href="{{ $slide->target_url }}" target="{{ Str::startsWith($slide->target_url, ['http://', 'https://']) ? '_blank' : '_self' }}" class="d-block w-100 h-100 position-relative">
                                <img src="{{ asset($slide->image_path) }}" class="d-block w-100 h-100" style="object-fit: cover; object-position: center; border-radius: 16px;" alt="Banner Slide">
                            </a>
                        @else
                            <div class="w-100 h-100 position-relative">
                                <img src="{{ asset($slide->image_path) }}" class="d-block w-100 h-100" style="object-fit: cover; object-position: center; border-radius: 16px;" alt="Banner Slide">
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @if($sliders->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#dashboardSliderCarousel" data-bs-slide="prev" style="width: 7%;">
                    <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.6));"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#dashboardSliderCarousel" data-bs-slide="next" style="width: 7%;">
                    <span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.6));"></span>
                    <span class="visually-hidden">Next</span>
                </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Our Services -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="premium-card p-4 border-0 shadow-sm" style="border-radius: 20px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">Our Services</h6>
                    <a href="{{ route('dashboard.services') }}" class="text-primary text-decoration-none small fw-bold">View All</a>
                </div>
                
                <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
                    <div class="col">
                        <a href="{{ route('vtu.airtime') }}" class="text-decoration-none">
                            <div class="border rounded-4 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center transition-hover" style="border-color: var(--border-color) !important; background: var(--bg-surface);">
                                <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-mobile-screen fs-5"></i>
                                </div>
                                <span class="small fw-bold" style="font-size: 0.8rem; color: var(--text-main);">Airtime</span>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('vtu.data') }}" class="text-decoration-none">
                            <div class="border rounded-4 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center transition-hover" style="border-color: var(--border-color) !important; background: var(--bg-surface);">
                                <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-wifi fs-5"></i>
                                </div>
                                <span class="small fw-bold" style="font-size: 0.8rem; color: var(--text-main);">Data</span>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('vtu.electricity') }}" class="text-decoration-none">
                            <div class="border rounded-4 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center transition-hover" style="border-color: var(--border-color) !important; background: var(--bg-surface);">
                                <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-lightbulb fs-5"></i>
                                </div>
                                <span class="small fw-bold" style="font-size: 0.8rem; color: var(--text-main);">Electricity</span>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('vtu.cable') }}" class="text-decoration-none">
                            <div class="border rounded-4 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center transition-hover" style="border-color: var(--border-color) !important; background: var(--bg-surface);">
                                <div class="rounded-3 bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-tv fs-5"></i>
                                </div>
                                <span class="small fw-bold" style="font-size: 0.8rem; color: var(--text-main);">Cable TV</span>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('logs.index') }}" class="text-decoration-none">
                            <div class="border rounded-4 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center transition-hover" style="border-color: var(--border-color) !important; background: var(--bg-surface); position: relative;">
                                <span class="badge bg-danger position-absolute" style="top: -5px; right: -5px; font-size: 0.6rem;">HOT</span>
                                <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-box-open fs-5"></i>
                                </div>
                                <span class="small fw-bold" style="font-size: 0.8rem; color: var(--text-main);">Logs & Accs</span>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('orders.create') }}" class="text-decoration-none">
                            <div class="border rounded-4 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center transition-hover" style="border-color: var(--border-color) !important; background: var(--bg-surface);">
                                <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-bullhorn fs-5"></i>
                                </div>
                                <span class="small fw-bold" style="font-size: 0.8rem; color: var(--text-main);">Social Boost</span>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('services.virtual-numbers') }}" class="text-decoration-none">
                            <div class="border rounded-4 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center transition-hover" style="border-color: var(--border-color) !important; background: var(--bg-surface);">
                                <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-sim-card fs-5"></i>
                                </div>
                                <span class="small fw-bold" style="font-size: 0.75rem; color: var(--text-main);">Virtual Number</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 shadow-sm" style="border-radius: 15px;">
                <h6 class="text-muted fw-bold small text-uppercase mb-2" style="font-size: 0.65rem;">Total Spent</h6>
                <h4 class="fw-bold mb-0" style="color: var(--text-main);">{{ money($totalSpent) }}</h4>
            </div>
        </div>
        
        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 shadow-sm" style="border-radius: 15px;">
                <h6 class="text-muted fw-bold small text-uppercase mb-2" style="font-size: 0.65rem;">Total Deposits</h6>
                <h4 class="fw-bold mb-0" style="color: var(--text-main);">{{ money($totalDeposits) }}</h4>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 shadow-sm" style="border-radius: 15px;">
                <h6 class="text-muted fw-bold small text-uppercase mb-2" style="font-size: 0.65rem;">Active Orders</h6>
                <h4 class="fw-bold mb-0" style="color: var(--text-main);">{{ number_format($activeOrders) }}</h4>
            </div>
        </div>
        
        <div class="col-12 col-md-3">
            <div class="premium-card p-3 h-100 shadow-sm" style="border-radius: 15px;">
                <h6 class="text-muted fw-bold small text-uppercase mb-2" style="font-size: 0.65rem;">Completed Orders</h6>
                <h4 class="fw-bold mb-0" style="color: var(--text-main);">{{ number_format($completedOrders) }}</h4>
            </div>
        </div>
    </div>

<div class="row g-4">
    <!-- Recent Activities Table -->
    <div class="col-12">
        <div class="premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Recent Activities</h5>
                <a href="{{ route('wallet.history') }}" class="btn btn-sm bg-primary-custom text-white rounded-pill px-4 py-2 shadow-sm fw-bold">
                    View All
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th class="pe-4 text-end">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivities as $activity)
                        <tr>
                            <td class="ps-4"><span class="text-muted fw-medium" style="font-size: 0.85rem;">#{{ $activity->reference }}</span></td>
                            <td>
                                <div class="fw-medium" style="font-size: 0.85rem; color: var(--text-main);">{{ $activity->created_at->format('M d, Y') }}</div>
                                <small class="text-muted" style="font-size: 0.7rem;">{{ $activity->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <span class="fw-medium" style="font-size: 0.85rem; color: var(--text-main);">{{ $activity->description ?? ucfirst(str_replace('_', ' ', $activity->transaction_type)) }}</span>
                            </td>
                            <td class="fw-medium {{ $activity->type === 'credit' ? 'text-success' : 'text-danger' }}" style="font-size: 0.85rem;">
                                {{ $activity->type === 'credit' ? '+' : '-' }}{{ money((float)$activity->amount) }}
                            </td>
                            <td class="pe-4 text-end">
                                @if($activity->type === 'credit')
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                        CREDIT
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                        DEBIT
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="opacity-20 mb-3"><i class="fa-solid fa-list fa-3x"></i></div>
                                <h6 class="text-muted fw-bold">No activities found yet.</h6>
                                <p class="text-muted small">Your recent account activities will appear here.</p>
                                <a href="{{ route('orders.create') }}" class="btn btn-sm bg-primary-custom text-white px-4 py-2 rounded-pill mt-2">Explore Services</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleBalance() {
        const actualBalance = document.getElementById('actual-balance');
        const hiddenBalance = document.getElementById('hidden-balance');
        const icon = document.getElementById('toggle-balance-icon');
        
        if (actualBalance.style.display === 'none') {
            actualBalance.style.display = 'inline';
            hiddenBalance.style.display = 'none';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            actualBalance.style.display = 'none';
            hiddenBalance.style.display = 'inline';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endsection
