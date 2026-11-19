@extends('layouts.app')

@section('header_title', 'HQ Support: Ticket #' . $ticket->id)

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <!-- Ticket Information Sidebar -->
        <div class="col-xl-3 col-lg-4">
            <div class="premium-card p-4 shadow-sm h-100">
                <div class="mb-4">
                    <h6 class="text-muted small fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">Ticket Intelligence</h6>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Ticket ID</span>
                        <span class="fw-bold text-primary">#{{ $ticket->id }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Created</span>
                        <span class="small">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-muted">Priority</span>
                        @php $pCol = $ticket->priority == 'high' ? 'danger' : ($ticket->priority == 'medium' ? 'warning' : 'info'); @endphp
                        <span class="badge bg-{{ $pCol }} bg-opacity-10 text-{{ $pCol }} px-2 py-1 rounded-pill" style="font-size: 0.65rem;">{{ strtoupper($ticket->priority) }}</span>
                    </div>
                </div>

                <hr class="border-secondary mb-4">

                <div class="mb-4">
                    <h6 class="text-muted small fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">User Context</h6>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px; font-size: 1.2rem;">
                            {{ strtoupper(substr($ticket->user->username, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold">{{ $ticket->user->username }}</div>
                            <div class="text-muted small">{{ $ticket->user->email }}</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ route('hq.users.show', $ticket->user->id) }}" class="btn btn-sm btn-outline-primary rounded-pill fw-bold">View Full Profile</a>
                        <a href="{{ route('hq.orders', ['search' => $ticket->user->username]) }}" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">Recent Orders</a>
                    </div>
                </div>

                <hr class="border-secondary mb-4">

                <form action="{{ route('hq.tickets.status', $ticket->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <h6 class="text-muted" style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem;">Lifecycle Status</h6>
                    <select name="status" class="form-select form-select-sm border-secondary bg-transparent mb-3 shadow-sm" style="color: var(--text-main); font-size: 0.75rem;">
                        <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open (Awaiting Agent)</option>
                        <option value="pending" {{ $ticket->status == 'pending' ? 'selected' : '' }}>Pending (Awaiting User)</option>
                        <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    <button type="submit" class="btn btn-primary-custom w-100 rounded-pill py-1 fw-bold" style="font-size: 0.7rem;">Update Status</button>
                </form>
            </div>
        </div>

        <!-- Conversation Area -->
        <div class="col-xl-9 col-lg-8">
            <div class="premium-card p-0 border-0 shadow-sm d-flex flex-column" style="min-height: 80vh;">
                <!-- Header -->
                <div class="p-3 px-4 bg-surface border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">{{ $ticket->subject }}</h5>
                        <small class="text-muted" style="font-size: 0.7rem;">Direct communication with user account</small>
                    </div>
                    <a href="{{ route('hq.tickets') }}" class="btn btn-sm btn-light border rounded-pill px-3" style="font-size: 0.7rem;">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>

                <!-- Chat Body -->
                <div class="flex-grow-1 p-4" style="background: var(--bg-body); overflow-y: auto;">
                    @foreach($ticket->messages as $msg)
                        <div class="d-flex mb-4 @if($msg->is_admin) justify-content-end @else justify-content-start @endif">
                            @if(!$msg->is_admin)
                                <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center text-secondary me-3 flex-shrink-0" style="width: 35px; height: 35px; font-weight: bold; font-size: 0.8rem;">
                                    {{ strtoupper(substr($ticket->user->username, 0, 1)) }}
                                </div>
                            @endif

                            <div style="max-width: 80%;">
                                <div class="d-flex align-items-center gap-2 mb-1 @if($msg->is_admin) justify-content-end @endif">
                                    <span class="fw-bold small" style="font-size: 0.7rem;">
                                        {{ $msg->is_admin ? 'SUPPORT TEAM' : strtoupper($ticket->user->username) }}
                                    </span>
                                    <span class="text-muted small" style="font-size: 0.6rem;">{{ $msg->created_at->format('H:i, M d') }}</span>
                                </div>
                                <div class="p-3 rounded-4 shadow-sm @if($msg->is_admin) bg-primary text-white @else border @endif" style="background: var(--bg-surface);" style="font-size: 0.85rem; line-height: 1.6;">
                                    {!! nl2br(preg_replace('/#([0-9]+)/', '<a href="' . route('hq.orders', ['search' => '$1']) . '" class="fw-bold ' . ($msg->is_admin ? 'text-white' : 'text-primary') . '">#$1</a>', e($msg->message))) !!}
                                </div>
                            </div>

                            @if($msg->is_admin)
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary ms-3 flex-shrink-0" style="width: 35px; height: 35px; font-weight: bold; font-size: 0.8rem;">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Footer Reply Box -->
                <div class="p-4 bg-surface border-top">
                    <form action="{{ route('hq.tickets.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="form-floating mb-3">
                            <textarea name="message" class="form-control" placeholder="Write your response..." id="replyTextarea" style="height: 100px; background: var(--bg-surface); color: var(--text-main); border-radius: 15px; font-size: 0.85rem;"></textarea>
                            <label for="replyTextarea" class="text-muted small">Type your reply here...</label>
                        </div>
                        
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-light border-0 rounded-pill px-3 py-1 text-muted" style="font-size: 0.65rem;" onclick="document.getElementById('replyTextarea').value = 'Your order # is being processed. Thank you for your patience.'">
                                    <i class="fa-solid fa-bolt me-1 text-warning"></i> Processing
                                </button>
                                <button type="button" class="btn btn-sm btn-light border-0 rounded-pill px-3 py-1 text-muted" style="font-size: 0.65rem;" onclick="document.getElementById('replyTextarea').value = 'We have successfully refunded your order #. The funds are back in your wallet.'">
                                    <i class="fa-solid fa-rotate-left me-1 text-danger"></i> Refunded
                                </button>
                                <button type="button" class="btn btn-sm btn-light border-0 rounded-pill px-3 py-1 text-muted" style="font-size: 0.65rem;" onclick="document.getElementById('replyTextarea').value = 'We are currently looking into order # and will provide an update as soon as possible.'">
                                    <i class="fa-solid fa-magnifying-glass me-1 text-info"></i> Investigating
                                </button>
                                <button type="button" class="btn btn-sm btn-light border-0 rounded-pill px-3 py-1 text-muted" style="font-size: 0.65rem;" onclick="document.getElementById('replyTextarea').value = 'The link you provided appears to be invalid or private. Please provide a working public link so we can assist you.'">
                                    <i class="fa-solid fa-link-slash me-1 text-secondary"></i> Invalid Link
                                </button>
                                <button type="button" class="btn btn-sm btn-light border-0 rounded-pill px-3 py-1 text-muted" style="font-size: 0.65rem;" onclick="document.getElementById('replyTextarea').value = 'This issue has been resolved and we have closed this ticket. Thank you for choosing us!'">
                                    <i class="fa-solid fa-check-double me-1 text-success"></i> Resolved
                                </button>
                            </div>
                            <button type="submit" class="btn btn-primary-custom px-4 rounded-pill py-1 fw-bold shadow-sm" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-paper-plane me-2"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
