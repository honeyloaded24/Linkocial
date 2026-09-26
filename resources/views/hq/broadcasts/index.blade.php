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

    <!-- Service News Tickers & Page Notes Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="premium-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1">
                            <i class="fa-solid fa-bullhorn text-primary me-2"></i>Service News Tickers & Page Notes
                        </h5>
                        <p class="text-muted small mb-0">
                            Configure live news tickers and information notes displayed across the top of specific service pages or site-wide. Supports text, emojis, and HTML formatting.
                        </p>
                    </div>
                </div>

                <form action="{{ route('hq.service-notices.update') }}" method="POST">
                    @csrf
                    
                    <div class="row g-4">
                        <!-- Global Ticker -->
                        <div class="col-12">
                            <div class="p-3 rounded-3 border" style="background: rgba(var(--primary-rgb, 79, 70, 229), 0.04); border-color: var(--border-color) !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-uppercase" style="font-size: 0.7rem;">Site-Wide Fallback</span>
                                        <label class="form-label fw-bold mb-0 text-main">Global Announcement Ticker</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="notice_global_enabled" name="notice_global_enabled" value="1" {{ \App\Models\Setting::get('notice_global_enabled') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-muted" for="notice_global_enabled">Active</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">Displays across all service pages if a service-specific ticker is not active.</p>
                                <textarea name="notice_global_text" class="form-control border-secondary bg-transparent" rows="2" placeholder="e.g. 📢 Welcome to Linkocial! 24/7 automated delivery is active across all services." style="color: var(--text-main);">{{ \App\Models\Setting::get('notice_global_text') }}</textarea>
                            </div>
                        </div>

                        <!-- Virtual Numbers Ticker -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border h-100" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-sim-card text-info"></i>
                                        <label class="form-label fw-bold mb-0 text-main">Virtual Numbers (/orders/virtual-numbers)</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="notice_numbers_enabled" name="notice_numbers_enabled" value="1" {{ \App\Models\Setting::get('notice_numbers_enabled') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-muted" for="notice_numbers_enabled">Active</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">Shown at the top of the SMS Verification / Numbers page.</p>
                                <textarea name="notice_numbers_text" class="form-control border-secondary bg-transparent" rows="2" placeholder="e.g. ⚡ USA and UK numbers have 99% OTP delivery rates today! If an OTP fails to arrive, funds are automatically refunded." style="color: var(--text-main);">{{ \App\Models\Setting::get('notice_numbers_text') }}</textarea>
                            </div>
                        </div>

                        <!-- Log Marketplace Ticker -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border h-100" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-boxes-stacked text-warning"></i>
                                        <label class="form-label fw-bold mb-0 text-main">Log Marketplace (/logs)</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="notice_logs_enabled" name="notice_logs_enabled" value="1" {{ \App\Models\Setting::get('notice_logs_enabled') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-muted" for="notice_logs_enabled">Active</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">Shown at the top of the Log Marketplace catalog & product pages.</p>
                                <textarea name="notice_logs_text" class="form-control border-secondary bg-transparent" rows="2" placeholder="e.g. 🚀 Fresh aged Facebook & Instagram logs restocked! All accounts are verified with 2FA cookies." style="color: var(--text-main);">{{ \App\Models\Setting::get('notice_logs_text') }}</textarea>
                            </div>
                        </div>

                        <!-- SMM Services Ticker -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border h-100" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-share-nodes text-primary"></i>
                                        <label class="form-label fw-bold mb-0 text-main">SMM Orders & Services</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="notice_smm_enabled" name="notice_smm_enabled" value="1" {{ \App\Models\Setting::get('notice_smm_enabled') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-muted" for="notice_smm_enabled">Active</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">Shown at the top of New SMM Order (/orders/create) and Services catalog.</p>
                                <textarea name="notice_smm_text" class="form-control border-secondary bg-transparent" rows="2" placeholder="e.g. 🔥 Instagram Followers & TikTok Views server speed upgraded! Instant delivery on all new orders." style="color: var(--text-main);">{{ \App\Models\Setting::get('notice_smm_text') }}</textarea>
                            </div>
                        </div>

                        <!-- VTU & Utilities Ticker -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border h-100" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-bolt text-success"></i>
                                        <label class="form-label fw-bold mb-0 text-main">VTU & Utility Bills</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="notice_vtu_enabled" name="notice_vtu_enabled" value="1" {{ \App\Models\Setting::get('notice_vtu_enabled') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-muted" for="notice_vtu_enabled">Active</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">Shown on Airtime, Data bundles, Cable TV, and Electricity recharge pages.</p>
                                <textarea name="notice_vtu_text" class="form-control border-secondary bg-transparent" rows="2" placeholder="e.g. 💡 Instant SME data & Airtime top-ups available 24/7 across MTN, Airtel, Glo, and 9mobile." style="color: var(--text-main);">{{ \App\Models\Setting::get('notice_vtu_text') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary-custom px-4 py-2">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save News Tickers & Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
