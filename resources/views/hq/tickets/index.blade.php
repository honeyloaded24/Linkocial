@extends('layouts.app')

@section('header_title', 'HQ: Support Management')

@section('content')
<div class="container-fluid">
    <!-- Status Overview -->
    <div class="row g-3 mb-4">
        @php
            $counts = [
                'all' => \App\Models\Ticket::count(),
                'open' => \App\Models\Ticket::where('status', 'open')->count(),
                'pending' => \App\Models\Ticket::where('status', 'pending')->count(),
                'closed' => \App\Models\Ticket::where('status', 'closed')->count(),
            ];
            $currentStatus = request('status');
        @endphp
        @foreach(['all' => 'Total', 'open' => 'Waiting', 'pending' => 'Replied', 'closed' => 'Closed'] as $status => $label)
        <div class="col-md-3">
            <a href="{{ route('hq.tickets', ['status' => ($status === 'all' ? '' : $status)]) }}" class="text-decoration-none h-100 d-block">
                <div class="premium-card p-3 text-center transition-all h-100 @if($currentStatus == $status || (!$currentStatus && $status == 'all')) border border-primary bg-primary bg-opacity-10 @endif">
                    <h6 class="text-muted small fw-bold text-uppercase mb-1">{{ $label }} Tickets</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($counts[$status]) }}</h3>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <div class="premium-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">ID</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td class="px-3 fw-bold">#{{ $ticket->id }}</td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $ticket->user->username }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ $ticket->user->email }}</small>
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $ticket->subject }}</div>
                        </td>
                        <td>
                            @php
                                $statusClass = match($ticket->status) {
                                    'open' => 'success',
                                    'pending' => 'warning',
                                    'closed' => 'secondary',
                                    default => 'primary'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }} bg-opacity-10 text-{{ $statusClass }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem; font-weight: 800;">
                                {{ $ticket->status }}
                            </span>
                        </td>
                        <td>
                             @php
                                $priorityClass = match($ticket->priority) {
                                    'high' => 'danger',
                                    'medium' => 'warning',
                                    'low' => 'info',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="text-{{ $priorityClass }} small fw-bold">
                                <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i>
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $ticket->created_at->format('M d, H:i') }}</td>
                        <td>
                            <a href="{{ route('hq.tickets.show', $ticket->id) }}" class="btn btn-sm btn-primary-custom px-3">Reply</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No tickets found in this category.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tickets->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
