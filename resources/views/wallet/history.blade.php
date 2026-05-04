@extends('layouts.app')

@section('header_title', 'Transaction Ledger')

@section('content')
<div class="container-fluid">
    <!-- Summary Header -->
    <div class="premium-card p-4 mb-4 border-secondary">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="fw-bold mb-1">Financial History</h4>
                <p class="text-muted small mb-0">Track all your credits, debits, and platform expenditures in one place.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="d-inline-flex align-items-center gap-3">
                    <div class="text-end">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Available Balance</small>
                        <h4 class="fw-bold mb-0 text-primary-custom">₦{{ number_format((float)auth()->user()->balance, 2) }}</h4>
                    </div>
                    <a href="{{ route('wallet.index') }}" class="btn btn-sm bg-primary-custom text-white px-3 py-1 rounded-pill shadow-sm fw-bold" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-plus me-1"></i> Add Funds
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="premium-card shadow-lg border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3">Reference</th>
                        <th class="py-3">Type</th>
                        <th class="py-3">Amount</th>
                        <th class="py-3">Description</th>
                        <th class="py-3">Balance After</th>
                        <th class="pe-4 py-3 text-end">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="ps-4">
                            <code class="text-primary-custom fw-bold small">{{ $tx->reference }}</code>
                        </td>
                        <td>
                            @php
                                $typeClasses = [
                                    'deposit' => 'bg-success bg-opacity-10 text-success',
                                    'order' => 'bg-primary bg-opacity-10 text-primary-custom',
                                    'refund' => 'bg-info bg-opacity-10 text-info',
                                    'withdrawal' => 'bg-danger bg-opacity-10 text-danger',
                                ];
                                $typeClass = $typeClasses[$tx->transaction_type] ?? 'bg-secondary bg-opacity-10 text-secondary';
                            @endphp
                            <span class="badge {{ $typeClass }} px-3 py-2 rounded-pill text-uppercase fw-bold" style="font-size: 0.6rem;">
                                {{ str_replace('_', ' ', $tx->transaction_type) }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $tx->type === 'credit' ? '+' : '-' }} ₦{{ number_format((float)$tx->amount, 2) }}
                            </div>
                        </td>
                        <td>
                            <div class="text-muted small" style="max-width: 300px;">{{ $tx->description }}</div>
                        </td>
                        <td>
                            <div class="fw-bold small">₦{{ number_format((float)$tx->balance_after, 2) }}</div>
                            <div class="text-muted" style="font-size: 0.65rem;">Before: ₦{{ number_format((float)$tx->balance_before, 2) }}</div>
                        </td>
                        <td class="pe-4 text-end text-muted small">
                            {{ $tx->created_at->format('M d, Y') }}<br>
                            {{ $tx->created_at->format('H:i A') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="opacity-10 mb-3">
                                <i class="fa-solid fa-receipt fa-5x"></i>
                            </div>
                            <h6 class="fw-bold text-muted">No transactions found</h6>
                            <p class="text-muted small">Your financial activity will appear here.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($transactions->hasPages())
        <div class="p-4 border-top border-secondary border-opacity-10">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
