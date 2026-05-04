@extends('layouts.app')

@section('header_title')
<div class="d-flex align-items-center gap-2">
    <a href="{{ route('hq.users.show', $user->id) }}" class="btn btn-sm btn-light border-0 rounded-circle"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="fw-bold mb-0">Login History: {{ $user->username }}</h5>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 text-muted small fw-bold">DATE & TIME</th>
                        <th class="py-3 text-muted small fw-bold">IP ADDRESS</th>
                        <th class="py-3 text-muted small fw-bold">LOCATION</th>
                        <th class="px-4 py-3 text-muted small fw-bold">BROWSER / DEVICE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-4 small">
                            <div class="fw-bold">{{ $log->created_at->format('M d, Y') }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $log->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="small fw-bold text-primary">{{ $log->ip_address }}</td>
                        <td class="small">
                            <span class="badge bg-info bg-opacity-10 text-info px-2 py-1"><i class="fa-solid fa-location-dot me-1"></i> {{ $log->location ?? 'Unknown' }}</span>
                        </td>
                        <td class="px-4 small text-muted">
                            {{ Str::limit($log->user_agent, 60) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-history fa-3x mb-3 opacity-20"></i>
                            <p>No login history found for this user.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
