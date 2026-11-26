@extends('layouts.app')

@section('header_title', 'Virtual Numbers History')

@section('content')
<div class="container-fluid">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="{{ route('services.virtual-numbers') }}" class="text-decoration-none text-muted small fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Virtual Numbers
        </a>
    </div>

    <div class="premium-card p-0 overflow-hidden shadow-sm">
        <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center bg-light bg-opacity-10">
            <h6 class="fw-bold mb-0">Your Purchased Virtual Numbers</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light bg-opacity-25">
                    <tr>
                        <th class="px-4">Order ID / Date</th>
                        <th>Service / Region</th>
                        <th>Phone Number</th>
                        <th>Code / SMS</th>
                        <th>Cost</th>
                        <th class="pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="px-4 text-nowrap">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace px-2 py-1" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.3px;">#{{ $order->id }}</span>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">{{ $order->created_at->format('M d, Y H:i') }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $order->product }}</div>
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ $order->country }}</small>
                                    @php
                                        $serverBadge = match ($order->server) {
                                            'server4' => ['Server 4', 'bg-info'],
                                            'server3' => ['Server 3', 'bg-primary'],
                                            'server2' => ['Server 2', 'bg-secondary'],
                                            default => ['Server 1 (HOT)', 'bg-danger'],
                                        };
                                    @endphp
                                    <span class="badge {{ $serverBadge[1] }} text-white" style="font-size: 0.62rem; padding: 2px 6px;">{{ $serverBadge[0] }}</span>
                                </div>
                            </td>
                            <td class="fw-bold text-nowrap">
                                <span class="font-monospace" style="font-size: 0.88rem;">{{ $order->phone_number }}</span>
                                <button class="btn btn-link text-muted p-0 ms-1" onclick="navigator.clipboard.writeText('{{ $order->phone_number }}'); const icon = this.querySelector('i'); icon.className = 'fa-solid fa-check text-success'; setTimeout(() => { icon.className = 'fa-regular fa-copy text-muted'; }, 2000);" style="border: none; background: transparent; text-decoration: none;" title="Copy Phone Number">
                                    <i class="fa-regular fa-copy" style="font-size: 0.8rem;"></i>
                                </button>
                            </td>
                            <td>
                                @if($order->status === 'received' || $order->status === 'finished')
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <div class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1 rounded">
                                            {{ $order->sms_code ?: 'Received' }}
                                        </div>
                                        @if($order->sms_code)
                                            <button class="btn btn-link text-muted p-0" onclick="navigator.clipboard.writeText('{{ $order->sms_code }}'); const icon = this.querySelector('i'); icon.className = 'fa-solid fa-check text-success'; setTimeout(() => { icon.className = 'fa-regular fa-copy text-muted'; }, 2000);" style="border: none; background: transparent; text-decoration: none;" title="Copy Code">
                                                <i class="fa-regular fa-copy" style="font-size: 0.8rem;"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($order->sms_text)
                                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">{{ $order->sms_text }}</small>
                                    @endif
                                @elseif($order->status === 'canceled')
                                    <span class="text-muted small">Canceled & Refunded</span>
                                @elseif($order->status === 'pending')
                                    <span class="text-warning small fw-bold">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" style="width: 0.8rem; height: 0.8rem;"></span> 
                                        Waiting for SMS...
                                    </span>
                                @else
                                    <span class="text-muted small">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                            <td class="fw-bold">{{ money($order->cost) }}</td>
                            <td class="pe-4">
                                @php
                                    $statusClasses = [
                                        'received' => 'bg-success text-white',
                                        'finished' => 'bg-success text-white',
                                        'pending' => 'bg-warning text-dark',
                                        'canceled' => 'bg-danger text-white'
                                    ];
                                    $statusClass = $statusClasses[$order->status] ?? 'bg-secondary text-white';
                                @endphp
                                <span class="badge {{ $statusClass }} rounded-pill px-2 py-1 fw-bold shadow-sm" style="font-size: 0.65rem;">
                                    {{ strtoupper($order->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted small">
                                <i class="fa-solid fa-phone-slash fa-2x mb-3 opacity-25"></i>
                                <p class="mb-0">You haven't purchased any virtual numbers yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="p-3 border-top border-secondary">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
