@extends('layouts.app')

@section('header_title', 'BOOST Order History')

@section('content')
<div class="container-fluid">
    @include('partials.service_ticker', ['service' => 'smm'])
    @include('partials.boost_nav')

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
            <h5 class="fw-bold mb-0">Your Recent BOOST Orders</h5>
            <a href="{{ route('orders.create') }}" class="btn bg-primary-custom text-white btn-sm px-3 rounded-pill shadow-sm">
                <i class="fa-solid fa-rocket me-1"></i> New BOOST Order
            </a>
        </div>
    
    <div class="table-responsive">
        <table class="table table-hover table-borderless align-middle mb-0">
            <thead class="bg-light bg-opacity-10">
                <tr>
                    <th class="px-3 text-nowrap">ID</th>
                    <th class="text-nowrap">Date</th>
                    <th class="text-nowrap">Service</th>
                    <th class="text-nowrap">Link</th>
                    <th class="text-nowrap">Quantity</th>
                    <th class="text-nowrap">Charge</th>
                    <th class="text-nowrap">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="px-3 text-nowrap">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace px-2 py-1" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.3px;">#{{ $order->id }}</span>
                    </td>
                    <td>{{ $order->created_at->format('M d, Y h:i A') }}</td>
                    <td>
                        <span class="d-block text-truncate" style="max-width: 200px;" title="{{ $order->service->name ?? ($order->service_name ?? 'Service Unavailable') }}">
                            {{ $order->service->name ?? ($order->service_name ?? 'Service Unavailable') }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ $order->link }}" target="_blank" class="text-decoration-none" style="color: var(--primary-color);">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Link
                        </a>
                    </td>
                    <td class="fw-bold">{{ number_format($order->quantity) }}</td>
                    <td class="fw-bold">{{ money((float)$order->charge) }}</td>
                    <td>
                        @php
                            $cleanStatus = strtolower(str_replace(' ', '_', $order->status));
                            $isActive = in_array($cleanStatus, ['processing', 'in_progress']);
                        @endphp
                        @if($order->status === 'completed')
                            <span class="badge bg-success bg-opacity-25 text-success px-3 py-2 rounded-pill">Completed</span>
                        @elseif($isActive)
                            <span class="badge px-3 py-2 rounded-pill text-white shadow-sm" style="background-color: #ff8c00 !important; color: white !important;">In Progress</span>
                        @elseif($order->status === 'pending')
                            <span class="badge bg-warning bg-opacity-25 text-warning px-3 py-2 rounded-pill">Pending</span>
                        @elseif($order->status === 'failed' || $order->status === 'canceled')
                            <span class="badge bg-danger bg-opacity-25 text-danger px-3 py-2 rounded-pill">Failed</span>
                        @elseif($order->status === 'partial')
                            <span class="badge bg-info bg-opacity-25 text-info px-3 py-2 rounded-pill">Partial</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-25 text-secondary px-3 py-2 rounded-pill">{{ ucfirst($order->status) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="fa-solid fa-box-open fs-1 opacity-50"></i>
                        </div>
                        <h6 class="fw-bold">No orders found</h6>
                        <p class="small text-muted mb-0">You haven't placed any social media orders yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($orders->hasPages())
    <div class="p-3 border-top border-secondary d-flex justify-content-center">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
</div>
@endsection
