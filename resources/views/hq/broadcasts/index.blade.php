@extends('layouts.app')

@section('header_title', 'HQ: Broadcast Manager')

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <!-- Create Broadcast -->
        <div class="col-md-4">
            <div class="premium-card p-4">
                <h5 class="fw-bold mb-3">Create New Broadcast</h5>
                <form action="{{ route('hq.broadcasts.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Message</label>
                        <textarea name="message" class="form-control border-secondary bg-transparent" rows="4" placeholder="e.g. We are performing system maintenance at 10 PM GMT." required style="color: var(--text-main);"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Alert Type</label>
                        <select name="type" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                            <option value="info">Info (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="danger">Danger (Red)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100">Publish Broadcast</button>
                </form>
            </div>
        </div>

        <!-- Broadcast List -->
        <div class="col-md-8">
            <div class="premium-card p-4">
                <h5 class="fw-bold mb-4">Active & Past Broadcasts</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th>Message</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($broadcasts as $b)
                            <tr>
                                <td>
                                    <div class="small text-main" style="max-width: 400px; white-space: pre-wrap;">{{ $b->message }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $b->type }} text-uppercase" style="font-size: 0.65rem;">{{ $b->type }}</span>
                                </td>
                                <td class="small text-muted">{{ $b->created_at->format('M d, Y') }}</td>
                                <td>
                                    <form action="{{ route('hq.broadcasts.destroy', $b->id) }}" method="POST" onsubmit="return confirm('Remove this broadcast?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">No broadcasts created yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
