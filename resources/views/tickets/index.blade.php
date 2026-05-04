@extends('layouts.app')

@section('header_title', 'Support Tickets')

@section('content')
<div class="container-fluid">
    <div class="premium-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">My Support Tickets</h5>
                <p class="text-muted small mb-0">Need help? Open a ticket and our team will get back to you.</p>
            </div>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary-custom px-4">
                <i class="fa-solid fa-plus me-2"></i> New Ticket
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">ID</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td class="px-3 fw-bold text-muted">#{{ $ticket->id }}</td>
                        <td>
                            <div class="fw-bold">{{ $ticket->subject }}</div>
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
                        <td class="small text-muted">{{ $ticket->updated_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-primary-custom px-3 rounded-pill">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-ticket-simple fa-3x mb-3 opacity-20"></i>
                            <p>You haven't opened any support tickets yet.</p>
                        </td>
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
