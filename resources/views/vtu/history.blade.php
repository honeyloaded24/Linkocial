@extends('layouts.app')

@section('header_title', 'VTU Order History')

@section('content')
<div class="container-fluid">
    @include('partials.service_ticker', ['service' => 'vtu'])
    @include('partials.vtu_nav')

    <div class="row mb-4">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 rounded-3" style="background-color: rgba(20, 184, 166, 0.1); color: var(--accent-color);" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <strong>Success!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <strong>Error!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>
    </div>

    <div class="premium-card p-0">
        <div class="p-4 border-bottom border-secondary d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h5 class="fw-bold mb-0">Your VTU Orders</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('vtu.airtime') }}" class="btn bg-primary-custom text-white btn-sm px-3 rounded-pill shadow-sm">
                    <i class="fa-solid fa-mobile-screen me-1"></i> Buy Airtime
                </a>
                <a href="{{ route('vtu.data') }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm">
                    <i class="fa-solid fa-wifi me-1"></i> Buy Data
                </a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light bg-opacity-10">
                    <tr>
                        <th class="px-3 text-nowrap">Order ID</th>
                        <th class="text-nowrap">Date</th>
                        <th class="text-nowrap">Service</th>
                        <th class="text-nowrap">Recipient / Target</th>
                        <th class="text-nowrap">Plan / Details</th>
                        <th class="text-nowrap">Cost</th>
                        <th class="text-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="px-3 text-nowrap">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace px-2 py-1" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.3px;">#{{ $order->id }}</span>
                        </td>
                        <td>
                            <div class="small fw-medium">{{ $order->created_at->format('M d, Y') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ $order->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <span class="fw-bold text-capitalize">
                                @if(strtolower($order->service_type) === 'airtime')
                                    <i class="fa-solid fa-mobile-screen text-success me-1"></i>
                                @elseif(strtolower($order->service_type) === 'data')
                                    <i class="fa-solid fa-wifi text-primary me-1"></i>
                                @elseif(strtolower($order->service_type) === 'electricity')
                                    <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                                @elseif(strtolower($order->service_type) === 'cable')
                                    <i class="fa-solid fa-tv text-secondary me-1"></i>
                                @else
                                    <i class="fa-solid fa-bolt text-primary me-1"></i>
                                @endif
                                {{ ucfirst($order->service_type) }}
                            </span>
                            @if($order->network)
                                <small class="text-muted d-block" style="font-size: 0.75rem;">{{ $order->network }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="font-monospace fw-semibold" style="font-size: 0.85rem;">{{ $order->phone_or_meter }}</span>
                        </td>
                        <td>
                            <span class="small">{{ $order->plan_or_amount ?: '—' }}</span>
                        </td>
                        <td class="fw-bold text-nowrap">
                            {{ money((float)$order->cost, true, 'NGN') }}
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'pending'    => ['bg' => 'warning',   'icon' => 'fa-clock'],
                                    'processing' => ['bg' => 'info',      'icon' => 'fa-spinner fa-spin'],
                                    'completed'  => ['bg' => 'success',   'icon' => 'fa-check-circle'],
                                    'failed'     => ['bg' => 'danger',    'icon' => 'fa-circle-xmark'],
                                    'refunded'   => ['bg' => 'secondary', 'icon' => 'fa-rotate-left'],
                                ];
                                $st = strtolower($order->status);
                                $map = $statusMap[$st] ?? ['bg' => 'secondary', 'icon' => 'fa-circle'];
                            @endphp
                            <span class="badge bg-{{ $map['bg'] }} bg-opacity-10 text-{{ $map['bg'] }} border border-{{ $map['bg'] }} border-opacity-25 px-2 py-1 rounded-pill fw-medium" style="font-size: 0.75rem;">
                                <i class="fa-solid {{ $map['icon'] }} me-1"></i> {{ ucfirst($order->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-bolt-slash fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                            <h6 class="fw-bold">No VTU Orders Yet</h6>
                            <p class="small mb-3">You haven't placed any airtime, data, cable, or electricity orders yet.</p>
                            <a href="{{ route('vtu.airtime') }}" class="btn btn-primary-custom btn-sm rounded-pill px-4 fw-bold">
                                Place Your First VTU Order
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="p-3 border-top border-secondary border-opacity-10">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
