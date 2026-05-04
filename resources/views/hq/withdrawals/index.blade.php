@extends('layouts.app')

@section('header_title', 'HQ: Withdrawal Management')

@section('content')
<div class="container-fluid">
    <div class="premium-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Affiliate Withdrawal Requests</h5>
                <p class="text-muted small mb-0">Review and process commission payout requests from users.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('hq.withdrawals', ['status' => 'pending']) }}" class="btn btn-sm @if(request('status') == 'pending') btn-primary-custom @else btn-outline-secondary @endif px-3">Pending</a>
                <a href="{{ route('hq.withdrawals', ['status' => 'approved']) }}" class="btn btn-sm @if(request('status') == 'approved') btn-primary-custom @else btn-outline-secondary @endif px-3">Approved</a>
                <a href="{{ route('hq.withdrawals') }}" class="btn btn-sm @if(!request('status')) btn-primary-custom @else btn-outline-secondary @endif px-3">All</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Bank Details</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                    <tr>
                        <td class="px-3">
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $w->user->username }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">Bal: ₦{{ number_format($w->user->commission_balance, 2) }}</small>
                        </td>
                        <td class="fw-bold text-success">₦ {{ number_format($w->amount, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $w->status === 'approved' ? 'success' : ($w->status === 'declined' ? 'danger' : 'warning') }} bg-opacity-10 text-{{ $w->status === 'approved' ? 'success' : ($w->status === 'declined' ? 'danger' : 'warning') }} px-2 py-1 rounded-pill text-uppercase" style="font-size: 0.7rem;">
                                {{ $w->status }}
                            </span>
                        </td>
                        <td>
                            <div class="small text-muted" style="max-width: 250px; white-space: pre-wrap;">{{ $w->bank_details }}</div>
                        </td>
                        <td class="small">{{ $w->created_at->format('M d, H:i') }}</td>
                        <td>
                            @if($w->status === 'pending')
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-success px-3" data-bs-toggle="modal" data-bs-target="#processModal{{ $w->id }}">Process</button>
                            </div>

                            <!-- Process Modal -->
                            <div class="modal fade" id="processModal{{ $w->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content bg-surface border-secondary shadow-lg">
                                        <div class="modal-header border-secondary">
                                            <h5 class="modal-title fw-bold">Process Withdrawal #{{ $w->id }}</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info border-0 small mb-4">
                                                <strong>User Details:</strong> {{ $w->user->username }} ({{ $w->user->email }})<br>
                                                <strong>Amount:</strong> ₦ {{ number_format($w->amount, 2) }}<br>
                                                <hr>
                                                <strong>Payment Instructions:</strong><br>
                                                {{ $w->bank_details }}
                                            </div>
                                            
                                            <form id="form{{ $w->id }}" method="POST">
                                                @csrf
                                                <div class="mb-3">
                                                    <label class="form-label small text-muted text-uppercase fw-bold">Admin Note (Internal/User Visible)</label>
                                                    <textarea name="admin_note" class="form-control border-secondary bg-transparent" placeholder="e.g. Paid via Bank Transfer. Trans ID: 12345" style="color: var(--text-main);"></textarea>
                                                </div>
                                                
                                                <div class="d-grid gap-2">
                                                    <button type="submit" formaction="{{ route('hq.withdrawals.approve', $w->id) }}" class="btn btn-success fw-bold">Approve & Deduct Balance</button>
                                                    <button type="submit" formaction="{{ route('hq.withdrawals.decline', $w->id) }}" class="btn btn-outline-danger btn-sm">Decline Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @else
                            <small class="text-muted">{{ $w->admin_note }}</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No withdrawal requests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $withdrawals->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
