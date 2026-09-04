@extends('layouts.app')

@section('header_title', 'HQ: VTU Orders')

@section('content')
<div class="container-fluid">
    <div class="premium-card p-4">
        <!-- Search and Filter Row -->
        <form action="{{ route('hq.vtu') }}" method="GET" class="row g-3 mb-4 align-items-center">
            <div class="col-md-4">
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
                <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
            </div>
            @if(request('search') || request('status') || request('service_type'))
            <div class="col-md-2">
                <a href="{{ route('hq.vtu') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">ID</th>
                        <th>User</th>
                        <th>Service / Details</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="px-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vtuOrders as $order)
                    <tr>
                        <td class="px-3">
                            <span class="fw-bold text-primary">#{{ $order->id }}</span>
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
