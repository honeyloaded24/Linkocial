@extends('layouts.app')

@section('header_title')
<div class="d-flex justify-content-between align-items-center w-100">
    <h4 class="fw-bold mb-0">User Detail - {{ $user->username }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('hq.users.impersonate', $user->id) }}" class="btn btn-sm btn-primary-custom rounded-1 px-3 py-1 fw-bold"><i class="fa-solid fa-right-to-bracket me-1"></i> Login as User</a>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">{{ session('error') }}</div>
    @endif

    <!-- Top Stats Grid -->
    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-2 h-100" style="border-left: 3px solid #6366f1 !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-2 text-primary">
                        <i class="fa-solid fa-wallet fa-sm"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold" style="font-size: 0.8rem;">Balance</small>
                        <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">{{ money((float)$user->balance) }}</h5>
                    </div>
                    <i class="fa-solid fa-chevron-right ms-auto text-muted small" style="font-size: 0.6rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-2 h-100" style="border-left: 3px solid #14b8a6 !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-success bg-opacity-10 p-2 rounded-2 text-success">
                        <i class="fa-solid fa-hand-holding-dollar fa-sm"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold" style="font-size: 0.8rem;">Deposits</small>
                        <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">{{ money($totalDeposits) }}</h5>
                    </div>
                    <i class="fa-solid fa-chevron-right ms-auto text-muted small" style="font-size: 0.6rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-2 h-100" style="border-left: 3px solid #f59e0b !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning bg-opacity-10 p-2 rounded-2 text-warning">
                        <i class="fa-solid fa-cart-shopping fa-sm"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold" style="font-size: 0.8rem;">Total Spent</small>
                        <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">{{ money($totalSpent) }}</h5>
                    </div>
                    <i class="fa-solid fa-chevron-right ms-auto text-muted small" style="font-size: 0.6rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="{{ route('hq.users.transactions', $user->id) }}" class="text-decoration-none h-100 d-block">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100" style="border-left: 3px solid #3b82f6 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-info bg-opacity-10 p-2 rounded-2 text-info">
                            <i class="fa-solid fa-repeat fa-sm"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-bold" style="font-size: 0.8rem;">Transactions</small>
                            <h5 class="fw-bold mb-0 text-main" style="font-size: 1.1rem;">{{ $totalTransactions }}</h5>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small" style="font-size: 0.6rem;"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="row g-2 mb-4">
        <div class="col">
            <button class="btn btn-success w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" style="font-size: 0.9rem;" data-bs-toggle="modal" data-bs-target="#addBalanceModal">
                <i class="fa-solid fa-circle-plus fa-sm"></i> Balance
            </button>
        </div>
        <div class="col">
            <button class="btn btn-danger w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" style="font-size: 0.9rem;" data-bs-toggle="modal" data-bs-target="#subtractBalanceModal">
                <i class="fa-solid fa-circle-minus fa-sm"></i> Balance
            </button>
        </div>
        <div class="col">
            <a href="{{ route('hq.users.logins', $user->id) }}" class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" style="font-size: 0.9rem;">
                <i class="fa-solid fa-list-ul fa-sm"></i> Logins
            </a>
        </div>
        <div class="col">
            <button class="btn btn-secondary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" style="font-size: 0.9rem;">
                <i class="fa-solid fa-bell fa-sm"></i> Notifications
            </button>
        </div>
        <div class="col">
            <form action="{{ route('hq.users.toggle-status', $user->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-warning w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 text-white" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-ban fa-sm"></i> {{ $user->is_active ? 'Ban User' : 'Unban User' }}
                </button>
            </form>
        </div>
        <div class="col">
            <form action="{{ route('hq.users.toggle-2fa', $user->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn {{ $user->two_factor_enabled ? 'btn-danger' : 'btn-outline-secondary' }} w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" style="font-size: 0.9rem;" onclick="return confirm('{{ $user->two_factor_enabled ? 'Disable 2FA for ' . $user->username . '? They will be able to log in without email verification code.' : 'Enable 2FA for ' . $user->username . '?' }}')">
                    <i class="fa-solid fa-shield-halved fa-sm"></i> {{ $user->two_factor_enabled ? 'Disable 2FA' : 'Enable 2FA' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Information Card -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4">
        <form action="{{ route('hq.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h5 class="fw-bold mb-0">Information of {{ $user->first_name }} {{ $user->last_name }}</h5>
                <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
                </button>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.9rem;">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control bg-light border-0 py-2 px-3 fw-bold" value="{{ $user->first_name }}" style="font-size: 1rem; color: var(--text-main);">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.9rem;">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control bg-light border-0 py-2 px-3 fw-bold" value="{{ $user->last_name }}" style="font-size: 1rem; color: var(--text-main);">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.9rem;">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control bg-light border-0 py-2 px-3 fw-bold" value="{{ $user->email }}" style="font-size: 1rem; color: var(--text-main);">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.9rem;">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control bg-light border-0 py-2 px-3 fw-bold" value="{{ $user->username }}" style="font-size: 1rem; color: var(--text-main);">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.9rem;">Verification Status</label>
                    <div class="form-control bg-light border-0 py-2 px-3 d-flex align-items-center gap-2 fw-bold" style="font-size: 1rem; opacity: 0.8; color: var(--text-main);">
                        @if($user->email_verified_at)
                            <i class="fa-solid fa-circle-check text-success"></i> Verified
                        @else
                            <i class="fa-solid fa-circle-xmark text-danger"></i> Unverified
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.9rem;">
                        <span>Security (Two-Factor 2FA)</span>
                        <span class="badge {{ $user->two_factor_enabled ? 'bg-danger' : 'bg-secondary' }}">
                            {{ $user->two_factor_enabled ? '2FA ACTIVE' : '2FA DISABLED' }}
                        </span>
                    </label>
                    <div class="form-control bg-light border-0 py-2 px-3 d-flex align-items-center justify-content-between fw-bold" style="font-size: 1rem; opacity: 0.9; color: var(--text-main);">
                        <div class="d-flex align-items-center gap-2">
                            @if($user->two_factor_enabled)
                                <i class="fa-solid fa-circle-check text-success"></i> <span class="text-success">Enabled (Email OTP on login)</span>
                            @else
                                <i class="fa-solid fa-circle-xmark text-muted"></i> <span class="text-muted">Disabled (Direct login without OTP)</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Balance Modal -->
<div class="modal fade" id="addBalanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="background-color: var(--bg-surface); color: var(--text-main);">
            <form action="{{ route('hq.users.balance', $user->id) }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="add">
                <div class="modal-header border-bottom-0 p-3">
                    <h5 class="modal-title fw-bold">Add Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 pt-0">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">AMOUNT ($)</label>
                        <input type="number" name="amount" class="form-control py-2" step="0.01" required placeholder="0.00" style="color: var(--text-main); background-color: transparent;">
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-3 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Add Funds</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Subtract Balance Modal -->
<div class="modal fade" id="subtractBalanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hq.users.balance', $user->id) }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="subtract">
                <div class="modal-header border-bottom-0 p-3">
                    <h5 class="modal-title fw-bold">Deduct Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 pt-0">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">AMOUNT ($)</label>
                        <input type="number" name="amount" class="form-control py-2" step="0.01" required placeholder="0.00" style="color: var(--text-main); background-color: transparent;">
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-3 pt-0">
                    <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">Deduct Funds</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [data-bs-theme="dark"] .card { background-color: var(--bg-surface); border: 1px solid var(--border-color) !important; }
    [data-bs-theme="dark"] .form-control { background-color: rgba(255,255,255,0.05) !important; color: var(--text-main); }
    [data-bs-theme="light"] .card { background-color: #fff; }
</style>
@endsection
