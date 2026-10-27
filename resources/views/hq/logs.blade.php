@extends('layouts.app')

@section('header_title', 'HQ: System Activity Logs')

@section('content')
<div class="container-fluid">
    <div class="premium-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Administrative Activity Log</h5>
                <p class="text-muted small mb-0">Review all administrative actions performed across the HQ dashboard.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <form action="{{ route('hq.activity-logs') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 border-secondary bg-transparent" placeholder="Search Admin or IP Address..." value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
            </div>
            <div class="col-md-3">
                <input type="text" name="action" class="form-control border-secondary bg-transparent" placeholder="Filter by Action (e.g. Banned)..." value="{{ request('action') }}" style="color: var(--text-main);">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
            </div>
            @if(request()->anyFilled(['search', 'action']))
            <div class="col-md-2">
                <a href="{{ route('hq.activity-logs') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th class="px-3">Timestamp</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target Model</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-3">
                            <div class="fw-bold small">{{ $log->created_at->format('M d, Y') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            @if($log->user)
                                <div class="fw-bold" style="font-size: 0.85rem;">{{ $log->user->username }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">{{ $log->user->email }}</small>
                            @else
                                <span class="text-muted small">System / Guest</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 fw-bold" style="font-size: 0.75rem;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            @if($log->model_type)
                                <div class="small text-muted">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</div>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <code class="small text-muted">{{ $log->ip_address }}</code>
                        </td>
                        <td class="small">
                            @if($log->details)
                                @php $details = json_decode($log->details, true); @endphp
                                @if(is_array($details))
                                    @foreach($details as $key => $val)
                                        <div style="font-size: 0.7rem;"><span class="fw-bold text-capitalize">{{ str_replace('_', ' ', $key) }}:</span> {{ is_scalar($val) ? $val : '...' }}</div>
                                    @endforeach
                                @else
                                    <div class="text-truncate" style="max-width: 150px;" title="{{ $log->details }}">{{ $log->details }}</div>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clipboard-list fa-3x mb-3 opacity-20"></i>
                            <p>No activity logs found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination & Summary -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4">
            <div class="text-muted small">
                Showing <span class="fw-bold text-main">{{ $logs->firstItem() ?? 0 }}</span> to <span class="fw-bold text-main">{{ $logs->lastItem() ?? 0 }}</span> of <span class="fw-bold text-main">{{ $logs->total() }}</span> administrative actions
            </div>
            <div class="premium-pagination">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
