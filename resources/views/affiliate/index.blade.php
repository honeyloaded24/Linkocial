@extends('layouts.app')

@section('header_title', 'Affiliate & Rewards')

@section('content')
<div class="container-fluid">
    <!-- Stats Header -->
    <div class="premium-card p-0 border-0 shadow-lg mb-5" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
        <div class="p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <h2 class="fw-bold text-white mb-0">Partner Dashboard</h2>
                        <span class="badge bg-primary bg-opacity-20 text-white px-3 py-2 rounded-pill border border-white border-opacity-20" style="font-size: 0.7rem; letter-spacing: 1px;">ACTIVE PARTNER</span>
                    </div>
                    @php
                        $firstRate = \App\Models\Setting::get('affiliate_commission_first', 10);
                        $recurringRate = \App\Models\Setting::get('affiliate_commission_recurring', 5);
                    @endphp
                    <p class="text-white text-opacity-75 mb-4 fs-5">
                        Share your link and earn <span class="text-primary-custom fw-bold">{{ $firstRate }}% commission</span> on first deposits and <span class="text-info fw-bold">{{ $recurringRate }}% for life</span> on all recurring top-ups!
                    </p>
                    
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <div class="bg-dark bg-opacity-50 p-3 rounded-4 border border-white border-opacity-10 d-inline-flex align-items-center gap-3 shadow-sm">
                            <code class="text-primary-custom fw-bold fs-5" id="refLinkText">{{ route('register', ['ref' => $user->username]) }}</code>
                            <button class="btn bg-white bg-opacity-10 text-white rounded-circle p-2 ms-2 border-0" onclick="copyRef()" title="Copy Link">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                        <button class="btn bg-primary-custom px-4 py-3 shadow-lg fw-bold text-white" data-bs-toggle="modal" data-bs-target="#withdrawalModal" style="border: 1px solid rgba(255,255,255,0.2);">
                            <i class="fa-solid fa-money-bill-transfer me-2"></i> Withdraw Funds
                        </button>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 text-white text-opacity-50 small">
                                <i class="fa-solid fa-circle-check text-primary-custom"></i>
                                <span><strong>{{ $firstRate }}%</strong> First-time deposit bonus</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 text-white text-opacity-50 small">
                                <i class="fa-solid fa-circle-check text-info"></i>
                                <span><strong>{{ $recurringRate }}%</strong> Lifetime recurring bonus</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 mt-4 mt-md-0">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-4 shadow-sm" style="background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(255,255,255,0.1);">
                                <small class="text-white text-opacity-75 d-block mb-1 text-uppercase fw-bold" style="font-size: 0.65rem;">Available</small>
                                <h4 class="fw-bold text-white mb-0">{{ money($user->commission_balance) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="premium-card p-4 h-100 bg-secondary bg-opacity-10 border-0 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 55px; height: 55px;">
                                <i class="fa-solid fa-sack-dollar fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-white opacity-50 small fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">Total Earned</h6>
                                <h4 class="fw-bold text-white mb-0">{{ money($user->total_earned_commission) }}</h4>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded-4 shadow-sm d-flex justify-content-between align-items-center px-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1);">
                                <div>
                                    <small class="text-white text-opacity-75 d-block mb-0 text-uppercase fw-bold" style="font-size: 0.65rem;">Total Referrals</small>
                                    <h4 class="fw-bold text-white mb-0">{{ number_format($referralsCount) }} <span class="fs-6 fw-normal text-white text-opacity-50">Users Joined</span></h4>
                                </div>
                                <div class="bg-primary-custom p-3 rounded-circle shadow-lg" style="width: 55px; height: 55px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-users text-white fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Affiliate Ledger -->
    <div class="premium-card shadow-sm">
        <div class="p-4 border-bottom border-secondary d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1">Affiliate Ledger</h5>
                <p class="text-muted small mb-0">Comprehensive history of your commissions and withdrawals.</p>
            </div>
            <span class="glass-badge">{{ count($transactions) }} Activities</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th class="pe-4 text-end">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold small">{{ $tx->description }}</div>
                        </td>
                        <td>
                            @if($tx->type === 'credit')
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.6rem;">COMMISSION</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.6rem;">WITHDRAWAL</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $tx->type === 'credit' ? '+' : '-' }} {{ money($tx->amount) }}
                            </div>
                        </td>
                        <td>
                            <div class="text-muted small">{{ money($tx->balance_after) }}</div>
                        </td>
                        <td class="pe-4 text-end text-muted small">
                            {{ $tx->created_at->format('M d, Y') }}<br>
                            {{ $tx->created_at->format('H:i A') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="opacity-10 mb-3">
                                <i class="fa-solid fa-list-check fa-5x"></i>
                            </div>
                            <h6 class="fw-bold text-muted">No affiliate activity found</h6>
                            <p class="text-muted small">Start sharing your link to earn commissions!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-surface border-secondary shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-secondary p-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-building-columns text-primary-custom me-2"></i> Request Payout</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 bg-primary bg-opacity-10 small mb-4">
                    <strong>Current Balance:</strong> {{ money($user->commission_balance) }}<br>
                    <strong>Minimum Payout:</strong> {{ money(10) }}
                </div>

                <form action="{{ route('affiliate.withdraw') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Amount to Withdraw</label>
                        <div class="input-group">
                            <span class="input-group-text border-secondary bg-transparent text-muted">{{ get_currency_symbol() }}</span>
                            <input type="number" name="amount" class="form-control" placeholder="1000" min="1000" max="{{ $user->commission_balance }}" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Bank Account Details</label>
                        <textarea name="bank_details" class="form-control" rows="5" placeholder="Bank Name: &#10;Account Number: &#10;Account Name: " required></textarea>
                    </div>

                    <button type="submit" class="btn bg-primary-custom w-100 py-3 fw-bold" @if($user->commission_balance < 1000) disabled @endif>
                        Submit Payout Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function copyRef() {
    const text = document.getElementById('refLinkText').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Referral link copied to clipboard!');
    });
}
</script>
@endsection
