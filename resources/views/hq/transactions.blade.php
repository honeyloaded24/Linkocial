@extends('layouts.app')

@section('header_title', 'HQ: Transactions')

@section('content')
<div class="container-fluid">
    <div class="premium-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Global Financial Ledger</h5>
                <p class="text-muted small mb-0">Track all credits (deposits, refunds) and debits (orders) across the platform.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <form action="{{ route('hq.transactions') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search User or Reference..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                    <option value="">All Types</option>
                    <option value="credit" {{ request('type') == 'credit' ? 'selected' : '' }}>Credits (+)</option>
                    <option value="debit" {{ request('type') == 'debit' ? 'selected' : '' }}>Debits (-)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="transaction_type" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                    <option value="">All Categories</option>
                    <option value="deposit" {{ request('transaction_type') == 'deposit' ? 'selected' : '' }}>Deposits</option>
                    <option value="order" {{ request('transaction_type') == 'order' ? 'selected' : '' }}>Orders</option>
                    <option value="refund" {{ request('transaction_type') == 'refund' ? 'selected' : '' }}>Refunds</option>
                    <option value="referral" {{ request('transaction_type') == 'referral' ? 'selected' : '' }}>Referrals</option>
                    <option value="manual" {{ request('transaction_type') == 'manual' ? 'selected' : '' }}>Manual Adjustments</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
            </div>
            @if(request()->anyFilled(['search', 'type', 'transaction_type']))
            <div class="col-md-1">
                <a href="{{ route('hq.transactions') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">Date</th>
                        <th>User</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Wallet Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="px-3 small">
                            <div class="fw-bold">{{ $tx->created_at->format('M d, Y') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ $tx->created_at->format('H:i') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size: 0.85rem;">{{ $tx->user->username }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ $tx->user->email }}</small>
                        </td>
                        <td>
                            <div class="small fw-bold">{{ $tx->description }}</div>
                            <code class="text-muted" style="font-size: 0.65rem;">{{ $tx->reference }}</code>
                        </td>
                        <td>
                            <span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }} bg-opacity-10 text-{{ $tx->type === 'credit' ? 'success' : 'danger' }} px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                {{ strtoupper($tx->type) }}
                            </span>
                        </td>
                        <td class="fw-bold text-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">
                            {{ $tx->type === 'credit' ? '+' : '-' }} {{ money($tx->amount) }}
                        </td>
                        <td class="small">
                            <div class="text-muted" style="font-size: 0.7rem;">Before: {{ money($tx->balance_before) }}</div>
                            <div class="fw-bold">After: {{ money($tx->balance_after) }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-money-bill-transfer fa-3x mb-3 opacity-20"></i>
                            <p>No financial transactions recorded yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-4">
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
