@extends('layouts.app')

@section('header_title', 'Queue Monitoring')

@section('content')
<div class="container-fluid">
    <div class="row g-4 mb-4">
        <!-- Redis Status -->
        <div class="col-12 col-md-6">
            <div class="premium-card p-4 bg-primary-custom text-white border-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="text-white opacity-75 mb-1 fw-bold">Redis Queue Status</h6>
                        <p class="small text-white opacity-50 mb-0">Live background jobs in default queue</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-server fa-lg text-white"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h2 class="fw-bold mb-0">{{ number_format($queueSize) }}</h2>
                    <span class="small opacity-75 fw-bold">Jobs Waiting</span>
                </div>
                <div class="mt-3">
                    <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-1">
                        <i class="fa-solid fa-circle-play me-1 small"></i> Supervisor Active
                    </span>
                </div>
            </div>
        </div>

        <!-- System Heartbeat -->
        <div class="col-12 col-md-6">
            <div class="premium-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="text-muted mb-1 fw-bold">Platform Heartbeat</h6>
                        <p class="small text-muted mb-0">Cron & Worker health check</p>
                    </div>
                    <div class="bg-success-soft text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-heart-pulse fa-lg"></i>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="p-2 px-3 border rounded-pill bg-light d-flex align-items-center gap-2">
                        <i class="fa-solid fa-clock text-primary"></i>
                        <span class="small fw-bold">Cron: Active</span>
                    </div>
                    <div class="p-2 px-3 border rounded-pill bg-light d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bolt text-warning"></i>
                        <span class="small fw-bold">Redis: Connected</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Tasks Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="premium-card p-4 border-0 shadow-sm" style="background: var(--bg-surface); border-left: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1 text-primary-custom"><i class="fa-solid fa-calendar-check me-2"></i>Automation Schedule (Cron)</h5>
                        <p class="text-muted small mb-0">High-authority timeline for recurring background tasks</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase fw-bold" style="letter-spacing: 1px;">
                                <th class="ps-0">Task Command</th>
                                <th>Frequency</th>
                                <th>Next Scheduled Run</th>
                                <th class="text-end pe-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scheduledTasks as $task)
                            <tr class="border-bottom border-light">
                                <td class="ps-0">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-white rounded-3 p-2 shadow-sm border">
                                            <i class="fa-solid fa-terminal text-primary small"></i>
                                        </div>
                                        <div>
                                            <code class="text-primary-custom fw-bold small">{{ $task['command'] }}</code>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-white text-dark border fw-medium px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-repeat me-1 opacity-50"></i> {{ $task['expression'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold small text-main">{{ $task['next_run'] }}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">UTC Time</div>
                                </td>
                                <td class="text-end pe-0">
                                    <span class="badge bg-success-soft text-success rounded-pill px-3 py-2">
                                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Standing By
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted small">No scheduled tasks identified.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Failed Jobs Section -->
    <div class="row">
        <div class="col-12">
            <div class="premium-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Failed Jobs Log</h5>
                        <p class="text-muted small mb-0">Background tasks that encountered errors during execution</p>
                    </div>
                    @if($failedJobs->isNotEmpty())
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-4" disabled>
                        Clear All (Admin Only)
                    </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Connection / Queue</th>
                                <th>Payload (Job Class)</th>
                                <th>Failed At</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($failedJobs as $job)
                            <tr>
                                <td class="ps-4"><span class="text-muted fw-bold">#{{ $job->id }}</span></td>
                                <td>
                                    <span class="badge bg-light text-main border fw-bold">{{ $job->connection }}</span>
                                    <span class="badge bg-light text-primary border fw-bold">{{ $job->queue }}</span>
                                </td>
                                <td>
                                    @php
                                        $payload = json_decode($job->payload, true);
                                        $jobClass = $payload['displayName'] ?? 'Unknown Job';
                                    @endphp
                                    <div class="fw-bold small">{{ class_basename($jobClass) }}</div>
                                    <div class="text-muted truncate-2" style="font-size: 0.65rem; max-width: 300px;">
                                        {{ Str::limit($job->exception, 150) }}
                                    </div>
                                </td>
                                <td><small class="text-muted">{{ $job->failed_at }}</small></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-xs btn-light border rounded-circle shadow-none" title="View Exception" onclick="alert(`{{ addslashes($job->exception) }}`)">
                                        <i class="fa-solid fa-eye fa-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="opacity-20 mb-3"><i class="fa-solid fa-shield-check fa-3x text-success"></i></div>
                                    <h6 class="text-muted fw-bold">The Automation Engine is Healthy</h6>
                                    <p class="text-muted small">No failed background jobs found in the logs.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $failedJobs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .bg-success-soft {
        background-color: rgba(16, 185, 129, 0.1);
    }
    .btn-xs {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
</style>
@endsection
