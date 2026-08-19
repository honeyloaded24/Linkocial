@extends('layouts.app')

@section('header_title', 'HQ: User Management')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Registered Users</h5>
                    <p class="text-muted small mb-0">Manage user accounts and manual balance adjustments.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success bg-success text-white border-0">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Wallet Balance</th>
                            <th>Joined Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="text-muted fw-bold">#{{ $user->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $user->first_name }} {{ $user->last_name }}</div>
                                <small class="text-muted">{{ $user->username }}</small>
                            </td>
                            <td>
                                @php
                                    $roleClasses = [
                                        'admin' => 'bg-primary text-white',
                                        'reseller' => 'bg-info text-white',
                                        'vip' => 'bg-warning text-dark',
                                        'user' => 'bg-secondary text-white'
                                    ];
                                    $roleClass = $roleClasses[$user->role] ?? 'bg-secondary text-white';
                                @endphp
                                <span class="badge {{ $roleClass }} px-3 py-1 rounded-pill small" style="font-size: 0.65rem;">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                    {{ money((float)$user->balance) }}
                                </span>
                            </td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="text-end d-flex justify-content-end gap-2">
                                <a href="{{ route('hq.users.show', $user->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    Details
                                </a>
                                <button class="btn btn-sm btn-info-custom rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#roleModal{{ $user->id }}">
                                    Role
                                </button>
                                <button class="btn btn-sm btn-primary-custom rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#balanceModal{{ $user->id }}">
                                    Balance
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@foreach($users as $user)
<!-- Balance Modal -->
<div class="modal fade" id="balanceModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-surface); border: 1px solid var(--border-color);">
            <form action="{{ route('hq.users.balance', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold">Adjust Balance: {{ $user->username }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4 text-center">
                        <small class="text-muted uppercase fw-bold d-block mb-1">CURRENT BALANCE</small>
                        <h3 class="fw-bold text-success">{{ money((float)$user->balance) }}</h3>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">ADJUSTMENT TYPE</label>
                        <select name="type" class="form-select bg-transparent border-secondary" style="color: var(--text-main);">
                            <option value="add" class="text-dark">Add Funds (+)</option>
                            <option value="subtract" class="text-dark">Deduct Funds (-)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">AMOUNT ($)</label>
                        <input type="number" name="amount" class="form-control bg-transparent border-secondary" step="0.01" required placeholder="0.00" style="color: var(--text-main);">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary bg-primary-custom border-0 px-4">Update Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Role Modal -->
<div class="modal fade" id="roleModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-surface); border: 1px solid var(--border-color);">
            <form action="{{ route('hq.users.role', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold">Update Role: {{ $user->username }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">SELECT NEW TIER</label>
                        <select name="role" class="form-select bg-transparent border-secondary" style="color: var(--text-main);">
                            <option value="user" class="text-dark" {{ $user->role === 'user' ? 'selected' : '' }}>Regular User</option>
                            <option value="reseller" class="text-dark" {{ $user->role === 'reseller' ? 'selected' : '' }}>Reseller</option>
                            <option value="vip" class="text-dark" {{ $user->role === 'vip' ? 'selected' : '' }}>VIP Partner</option>
                            <option value="admin" class="text-dark" {{ $user->role === 'admin' ? 'selected' : '' }}>HQ Administrator</option>
                        </select>
                    </div>
                    <div class="alert alert-warning border-0 small bg-warning bg-opacity-10 text-warning mb-0">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        Changing a user to <strong>ADMIN</strong> will grant them full HQ access.
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info bg-info text-white border-0 px-4">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
