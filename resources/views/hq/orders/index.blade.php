@extends('layouts.app')

@section('header_title', 'HQ: Platform Orders')

@section('content')
<div class="container-fluid">
    <!-- Status Statistics Tabs -->
    <div class="row g-2 mb-4">
        @php
            $currentStatus = request('status', 'all');
            $statusConfigs = [
                'all' => ['label' => 'All Orders', 'icon' => 'fa-list', 'class' => 'primary'],
                'pending' => ['label' => 'Pending', 'icon' => 'fa-clock', 'class' => 'warning'],
                'processing' => ['label' => 'In Progress', 'icon' => 'fa-spinner', 'class' => 'info'],
                'completed' => ['label' => 'Completed', 'icon' => 'fa-check-circle', 'class' => 'success'],
                'failed' => ['label' => 'Failed/Canceled', 'icon' => 'fa-times-circle', 'class' => 'danger'],
            ];
        @endphp
        @foreach($statusConfigs as $key => $cfg)
        <div class="col">
            <a href="{{ route('hq.orders', ['status' => $key, 'search' => request('search')]) }}" class="text-decoration-none h-100 d-block">
                <div class="premium-card p-3 text-center transition-all h-100 @if($currentStatus === $key) border border-{{ $cfg['class'] }} bg-{{ $cfg['class'] }} bg-opacity-10 @endif" style="cursor: pointer;">
                    <div class="text-{{ $cfg['class'] }} mb-1">
                        <i class="fa-solid {{ $cfg['icon'] }} small"></i>
                    </div>
                    <h6 class="fw-bold mb-1" style="font-size: 0.8rem;">{{ $cfg['label'] }}</h6>
                    <h5 class="fw-bold mb-0 text-dark">{{ number_format($counts[$key] ?? 0) }}</h5>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <div class="premium-card p-4">
        <!-- Search and Filter Row -->
        <form action="{{ route('hq.orders') }}" method="GET" class="row g-3 mb-4 align-items-center">
            <input type="hidden" name="status" value="{{ request('status', 'all') }}">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search by Order ID, Username or Email..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary-custom w-100">Search</button>
            </div>
            @if(request('search') || request('status'))
            <div class="col-md-2">
                <a href="{{ route('hq.orders') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3 text-nowrap">Order ID</th>
                        <th class="text-nowrap">User</th>
                        <th class="text-nowrap">Service / Provider</th>
                        <th class="text-nowrap">Target Link</th>
                        <th class="text-nowrap">Quantity</th>
                        <th class="text-nowrap">Profit / Charge</th>
                        <th class="text-nowrap">Status</th>
                        <th class="text-nowrap">Date</th>
                        <th class="px-3 text-end text-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="px-3 text-nowrap">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace px-2 py-1" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.3px;">#{{ $order->id }}</span>
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $order->user->username }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ $order->user->email }}</small>
                        </td>
                        <td>
                            <div class="fw-bold text-truncate" style="max-width: 200px; font-size: 0.85rem;">{{ $order->service->name ?? 'Deleted Service' }}</div>
                            <small class="text-muted">ID: {{ $order->provider_order_id ?? 'N/A' }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-link text-muted" style="font-size: 0.7rem;"></i>
                                <a href="{{ $order->link }}" target="_blank" class="text-truncate d-inline-block small text-decoration-none" style="max-width: 150px;">{{ $order->link }}</a>
                            </div>
                        </td>
                        <td class="fw-bold small">{{ number_format($order->quantity) }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ money((float)$order->charge, true, 'USD') }} <span class="text-muted small">/ {{ money((float)$order->provider_cost, true, 'USD') }}</span></div>
                            <small class="text-success" style="font-size: 0.7rem;"><i class="fa-solid fa-chart-line"></i> Revenue Audit</small>
                        </td>
                        <td>
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
                            <span class="badge {{ $isActive ? '' : 'bg-opacity-10' }} px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-sm" 
                                  style="{{ $isActive ? 'background-color: #ff8c00 !important; color: white !important;' : 'background-color: rgba(var(--bs-'.$s['bg'].'-rgb), 0.1) !important; color: var(--bs-'.$s['bg'].') !important;' }} font-size: 0.7rem; font-weight: 800;">
                                <i class="fa-solid {{ $s['icon'] }}" style="font-size: 0.6rem;"></i>
                                {{ strtoupper(str_replace('_', ' ', $cleanStatus)) }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            {{ $order->created_at->format('M d, Y') }}<br>
                            {{ $order->created_at->format('H:i') }}
                        </td>
                        <td class="px-3 text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                                    Actions <i class="fa-solid fa-chevron-down ms-1 small"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 premium-card p-2" style="min-width: 180px;">
                                    <li>
                                        <a href="{{ route('hq.orders.show', $order->id) }}" class="dropdown-item rounded small py-2">
                                            View Details
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    @if(in_array($order->status, ['pending', 'failed', 'processing', 'in_progress']))
                                    <li>
                                        <form action="{{ route('hq.orders.complete', $order->id) }}" method="POST" onsubmit="return confirm('Mark this order as COMPLETED manually?')">
                                            @csrf
                                            <button type="submit" class="dropdown-item rounded small py-2">Force Complete</button>
                                        </form>
                                    </li>
                                    @endif
                                    
                                    @if($order->status !== 'canceled')
                                    <li>
                                        <form action="{{ route('hq.orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Cancel this order and REFUND the user?')">
                                            @csrf
                                            <button type="submit" class="dropdown-item rounded small py-2">Cancel & Refund</button>
                                        </form>
                                    </li>
                                    @endif

                                    @if(in_array($order->status, ['failed', 'canceled']))
                                    <li>
                                        <form action="{{ route('hq.orders.resubmit', $order->id) }}" method="POST" onsubmit="return confirm('Re-queue this order for processing?')">
                                            @csrf
                                            <button type="submit" class="dropdown-item rounded small py-2">Resubmit Order</button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>

                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fa-3x mb-3 opacity-20"></i>
                            <p>No orders found matching your criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-4">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<style>
    .transition-all { transition: all 0.2s ease-in-out; }
    .premium-card:hover { transform: translateY(-2px); }
</style>
@endsection
