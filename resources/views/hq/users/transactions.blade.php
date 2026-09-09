@extends('layouts.app')

@section('header_title')
<div class="d-flex align-items-center gap-2">
    <a href="{{ route('hq.users.show', $user->id) }}" class="btn btn-sm btn-light border-0 rounded-circle"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="fw-bold mb-0">Activity History: {{ $user->username }}</h5>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="premium-card p-0 border-0 shadow-lg overflow-hidden">
        <!-- Tab Navigation -->
        <div class="bg-light bg-opacity-5 p-2 border-bottom border-secondary border-opacity-10">
            <ul class="nav nav-pills nav-fill gap-2 p-1" id="transactionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3 rounded-3" id="deposits-tab" data-bs-toggle="tab" data-bs-target="#deposits" type="button" role="tab">
                        <i class="fa-solid fa-money-bill-transfer me-2"></i> User Deposits
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 rounded-3" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#all-transactions" type="button" role="tab">
                        <i class="fa-solid fa-list-check me-2"></i> Ledger Logs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 rounded-3" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                        <i class="fa-solid fa-cart-shopping me-2"></i> Recent Orders
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="transactionTabsContent">
            <!-- Deposits Pane -->
            <div class="tab-pane fade show active" id="deposits" role="tabpanel" aria-labelledby="deposits-tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-10">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-bold">DATE</th>
                                <th class="py-3 text-muted small fw-bold">AMOUNT</th>
                                <th class="py-3 text-muted small fw-bold">PAYMENT METHOD</th>
                                <th class="px-4 py-3 text-muted small fw-bold text-end">REF / STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deposits as $payment)
                            <tr>
                                <td class="px-4 py-3 small">{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                                <td class="py-3 fw-bold text-success">{{ money($payment->amount) }}</td>
                                <td class="py-3 small text-muted text-uppercase">{{ str_replace('_', ' ', $payment->payment_method ?? 'gateway') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="text-muted small mb-1" style="font-size: 0.7rem;">{{ $payment->reference }}</div>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill" style="font-size: 0.65rem;">{{ strtoupper($payment->status) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted small">
                                    <div class="opacity-20 mb-2"><i class="fa-solid fa-folder-open fa-3x"></i></div>
                                    No deposits found for this user.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ledger Logs Pane -->
            <div class="tab-pane fade" id="all-transactions" role="tabpanel" aria-labelledby="transactions-tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-10">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-bold">DATE / REF</th>
                                <th class="py-3 text-muted small fw-bold">TYPE</th>
                                <th class="py-3 text-muted small fw-bold">AMOUNT</th>
                                <th class="py-3 text-muted small fw-bold">BALANCE AFTER</th>
                                <th class="px-4 py-3 text-muted small fw-bold">DESCRIPTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions ?? [] as $tx)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-bold small">{{ $tx->created_at->format('M d, Y h:i A') }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">{{ $tx->reference }}</div>
                                </td>
                                <td class="py-3">
                                    <span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }} bg-opacity-10 text-{{ $tx->type === 'credit' ? 'success' : 'danger' }} px-3 py-1 rounded-pill" style="font-size: 0.65rem;">
                                        {{ strtoupper($tx->type) }} ({{ strtoupper($tx->transaction_type) }})
                                    </span>
                                </td>
                                <td class="py-3 fw-bold text-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">
                                    {{ $tx->type === 'credit' ? '+' : '-' }} {{ money($tx->amount) }}
                                </td>
                                <td class="py-3 small fw-bold">{{ money($tx->balance_after) }}</td>
                                <td class="px-4 py-3 small text-muted">{{ $tx->description }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    <div class="opacity-20 mb-2"><i class="fa-solid fa-receipt fa-3x"></i></div>
                                    No ledger transactions recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Orders Pane -->
            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-10">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-bold">ID / DATE</th>
                                <th class="py-3 text-muted small fw-bold">SERVICE NAME</th>
                                <th class="py-3 text-muted small fw-bold">QUANTITY</th>
                                <th class="py-3 text-muted small fw-bold text-end">CHARGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-bold small">{{ $order->display_id ?? ('#' . $order->id) }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">{{ $order->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-bold small text-truncate" style="max-width: 250px;">{{ $order->service_name ?? ($order->service->name ?? 'Service') }}</div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill" style="font-size: 0.6rem;">{{ strtoupper($order->status) }}</span>
                                </td>
                                <td class="py-3 small">{{ is_numeric($order->quantity) ? number_format($order->quantity) : ($order->quantity ?? '1') }}</td>
                                <td class="px-4 py-3 text-end fw-bold">{{ money($order->charge) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted small">
                                    <div class="opacity-20 mb-2"><i class="fa-solid fa-box-open fa-3x"></i></div>
                                    No orders found for this user.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#transactionTabs .nav-link {
    color: var(--text-muted);
    border: none;
    transition: all 0.3s ease;
}
#transactionTabs .nav-link:hover {
    background: rgba(var(--primary-rgb), 0.05);
    color: var(--primary-color);
}
#transactionTabs .nav-link.active {
    background: var(--primary-color) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.3);
}
</style>
@endsection
