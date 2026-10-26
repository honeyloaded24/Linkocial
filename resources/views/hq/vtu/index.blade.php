@extends('layouts.app')

@section('header_title', 'HQ: VTU Orders & Financial Records')

@section('content')
<div class="container-fluid">
    <!-- Header Title & Action -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">VTU Airtime, Data & Utility Orders</h4>
            <small class="text-muted">Track virtual top-up transactions, customer billing, and network delivery statuses.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hq.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- VTU Financial Summary Cards -->
    @if(isset($vtuStats))
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Orders</span>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-mobile-screen fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">{{ number_format($vtuStats['total']) }}</h3>
                    <div class="small text-muted d-flex gap-2 flex-wrap" style="font-size: 0.72rem;">
                        <span class="text-success"><i class="fa-solid fa-check"></i> {{ number_format($vtuStats['completed']) }} Done</span>
                        <span class="text-warning"><i class="fa-solid fa-clock"></i> {{ number_format($vtuStats['pending']) }} Pend</span>
                        <span class="text-danger"><i class="fa-solid fa-times"></i> {{ number_format($vtuStats['failed']) }} Fail</span>
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
                    <h3 class="fw-bold mb-1 text-success">₦{{ number_format($vtuStats['revenue'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        Completed customer charges
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Provider Cost</span>
                    <div class="bg-secondary bg-opacity-10 text-muted rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-receipt fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-muted">₦0.00</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        <span class="badge bg-secondary bg-opacity-25 text-muted px-2 py-1"><i class="fa-solid fa-info-circle me-1"></i>Unrecorded Direct API</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="premium-card p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Tracked Margin</span>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-chart-line fs-6"></i>
                    </div>
                </div>
                <div>
                    <h3 class="fw-bold mb-1 text-info">₦{{ number_format($vtuStats['profit'], 2) }}</h3>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        Tracked revenue volume
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="premium-card p-4">
        <!-- Search and Date Filter Row -->
        <form action="{{ route('hq.vtu') }}" method="GET" class="row g-3 mb-4 align-items-center">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search Phone/Meter, Ref, or User..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="service_type" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                    <option value="">All Services</option>
                    <option value="airtime" {{ request('service_type') === 'airtime' ? 'selected' : '' }}>Airtime</option>
                    <option value="data" {{ request('service_type') === 'data' ? 'selected' : '' }}>Data</option>
                    <option value="electricity" {{ request('service_type') === 'electricity' ? 'selected' : '' }}>Electricity</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control border-secondary bg-transparent" value="{{ request('from_date') }}" title="From Date" style="color: var(--text-main);">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control border-secondary bg-transparent" value="{{ request('to_date') }}" title="To Date" style="color: var(--text-main);">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary-custom w-100" title="Filter"><i class="fa-solid fa-filter"></i></button>
            </div>
            @if(request('search') || request('status') || request('service_type') || request('from_date') || request('to_date'))
            <div class="col-12 mt-2">
                <a href="{{ route('hq.vtu') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-times me-1"></i> Clear Filters</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <th class="px-3 text-nowrap">ID</th>
                        <th class="text-nowrap">User</th>
                        <th class="text-nowrap">Service / Details</th>
                        <th class="text-nowrap">Amount</th>
                        <th class="text-nowrap">Status</th>
                        <th class="text-nowrap">Date</th>
                        <th class="px-3 text-end text-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vtuOrders as $order)
                    <tr>
                        <td class="px-3 text-nowrap">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace px-2 py-1" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.3px;">#{{ $order->id }}</span>
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $order->user->username ?? 'Unknown' }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ $order->user->email ?? 'N/A' }}</small>
                        </td>
                        <td>
                            <div class="fw-bold text-capitalize" style="font-size: 0.85rem;">
                                @if($order->service_type === 'airtime')
                                    <i class="fa-solid fa-mobile-screen text-success me-1"></i>
                                @elseif($order->service_type === 'data')
                                    <i class="fa-solid fa-wifi text-primary me-1"></i>
                                @elseif($order->service_type === 'electricity')
                                    <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                                @endif
                                {{ $order->service_type }} ({{ $order->network }})
                            </div>
                            <small class="text-muted">Target: {{ $order->phone_or_meter }}</small><br>
                            <small class="text-muted" style="font-size: 0.65rem;">Ref: {{ $order->api_reference }}</small>
                        </td>
                        <td class="fw-bold">
                            {{ money((float)$order->cost, true, 'NGN') }}
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'pending'   => ['bg' => 'warning', 'icon' => 'fa-clock'],
                                    'completed' => ['bg' => 'success', 'icon' => 'fa-check-circle'],
                                    'failed'    => ['bg' => 'danger',  'icon' => 'fa-times-circle'],
                                    'refunded'  => ['bg' => 'secondary','icon' => 'fa-undo'],
                                ];
                                $st = strtolower($order->status);
                                $map = $statusMap[$st] ?? ['bg' => 'secondary', 'icon' => 'fa-circle'];
                            @endphp
                            <span class="badge bg-{{ $map['bg'] }} bg-opacity-10 text-{{ $map['bg'] }} border border-{{ $map['bg'] }} px-2 py-1 rounded-pill fw-medium" style="font-size: 0.75rem;">
                                <i class="fa-solid {{ $map['icon'] }} me-1"></i> {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="small fw-medium">{{ $order->created_at->format('M d, Y') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ $order->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="px-3 text-end">
                            @if($order->status !== 'refunded')
                            <form action="{{ route('hq.vtu.refund', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to refund this VTU order? The amount will be added to the user\'s wallet.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger px-3 py-1 fw-bold rounded-pill" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-undo"></i> Refund
                                </button>
                            </form>
                            @else
                            <span class="text-muted small"><i class="fa-solid fa-check"></i> Refunded</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="fa-solid fa-satellite-dish fa-3x opacity-25"></i></div>
                            <h6 class="fw-bold text-muted">No VTU orders found</h6>
                            <p class="small text-muted mb-0">Adjust your search or filters to see more results.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vtuOrders->hasPages())
        <div class="mt-4 d-flex justify-content-end">
            {{ $vtuOrders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
