@extends('layouts.app')

@section('header_title', 'Ticket #' . $ticket->id)

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <!-- Sidebar Info -->
        <div class="col-md-3">
            <div class="premium-card p-4">
                <h6 class="text-muted small fw-bold text-uppercase mb-3">Ticket Information</h6>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Status</small>
                    @php
                        $statusClass = match($ticket->status) {
                            'open' => 'success',
                            'pending' => 'warning',
                            'closed' => 'secondary',
                            default => 'primary'
                        };
                    @endphp
                    <span class="badge bg-{{ $statusClass }} bg-opacity-10 text-{{ $statusClass }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">
                        {{ $ticket->status }}
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Priority</small>
                    <span class="fw-bold small">{{ ucfirst($ticket->priority) }}</span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Created</small>
                    <span class="small">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                </div>

                <div class="mb-0">
                    <small class="text-muted d-block mb-1">Last Update</small>
                    <span class="small">{{ $ticket->updated_at->diffForHumans() }}</span>
                </div>
            </div>
            
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary w-100 mt-3">
                <i class="fa-solid fa-arrow-left me-2"></i> Back to Tickets
            </a>
        </div>

        <!-- Chat Content -->
        <div class="col-md-9">
            <div class="premium-card p-0 overflow-hidden shadow-sm">
                <div class="p-3 border-bottom border-secondary bg-light bg-opacity-10">
                    <h5 class="fw-bold mb-0">{{ $ticket->subject }}</h5>
                </div>

                <div class="p-4" style="max-height: 600px; overflow-y: auto; background: var(--bg-body);">
                    @foreach($ticket->messages as $msg)
                        <div class="d-flex mb-4 @if($msg->is_admin) justify-content-start @else justify-content-end @endif">
                            <div class="max-width-75">
                                <div class="d-flex align-items-center gap-2 mb-1 @if($msg->is_admin) flex-row @else flex-row-reverse @endif">
                                    <span class="fw-bold small" style="font-size: 0.75rem;">
                                        @if($msg->is_admin)
                                            <i class="fa-solid fa-shield-halved text-primary me-1"></i> Support Agent
                                        @else
                                            You
                                        @endif
                                    </span>
                                    <span class="text-muted" style="font-size: 0.65rem;">{{ $msg->created_at->format('H:i, M d') }}</span>
                                </div>
                                <div class="p-3 rounded-4 shadow-sm @if($msg->is_admin) bg-surface text-main border border-secondary @else bg-primary text-white @endif" style="font-size: 0.9rem; line-height: 1.5;">
                                    {!! nl2br(e($msg->message)) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($ticket->status !== 'closed')
                <div class="p-3 bg-surface border-top border-secondary">
                    <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <textarea name="message" class="form-control border-secondary bg-transparent" placeholder="Type your reply here..." rows="2" style="color: var(--text-main);"></textarea>
                            <button type="submit" class="btn btn-primary-custom px-4">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="p-4 text-center bg-light bg-opacity-10">
                    <span class="text-muted"><i class="fa-solid fa-lock me-2"></i> This ticket is closed. Please open a new one if you still need help.</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .max-width-75 { max-width: 75%; }
</style>
@endsection
