@extends('layouts.app')

@section('header_title', 'Account Settings')

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-lg-4">
            <div class="premium-card p-4 text-center shadow-lg">
                <div class="mb-4">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->first_name . ' ' . $user->last_name) }}&background=6366f1&color=fff&size=128" alt="Profile" class="rounded-circle shadow-lg border border-primary border-4" width="100">
                </div>
                <h4 class="fw-bold mb-1">{{ $user->first_name }} {{ $user->last_name }}</h4>
                <p class="text-muted small mb-4">{{ $user->email }}</p>
                
                <div class="bg-dark bg-opacity-25 rounded-4 p-3 mb-4 border border-secondary border-opacity-10">
                    <div class="d-flex justify-content-between mb-2 text-start">
                        <span class="text-muted small">Username</span>
                        <span class="fw-bold small text-primary-custom">{{ $user->username }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-start">
                        <span class="text-muted small">Joined</span>
                        <span class="fw-bold small">{{ $user->created_at->format('M Y') }}</span>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info-emphasis small mb-0 text-start">
                    <i class="fa-solid fa-circle-info me-2 text-info"></i>
                    To change your personal details, please contact support.
                </div>
            </div>
        </div>

        <!-- API Key Management -->
        <div class="col-lg-8">
            <div class="premium-card shadow-lg mb-4">
                <div class="p-4 border-bottom border-secondary bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-key text-primary-custom me-2"></i> Developer Credentials</h5>
                    <p class="text-muted small mb-0 d-none d-md-block">API management</p>
                </div>
                <div class="p-4">
                    <label class="form-label text-muted small fw-bold text-uppercase mb-3">Your API Key</label>
                    
                    @if($user->api_key)
                    @php 
                        $newKey = session('new_api_key');
                        $isNew = (bool)$newKey;
                        $displayKey = $isNew ? $newKey : substr($user->api_key, 0, 8) . '********************************' . substr($user->api_key, -4);
                    @endphp

                    @if($isNew)
                    <div class="alert alert-success border-0 small bg-success bg-opacity-10 text-success mb-4">
                        <i class="fa-solid fa-circle-check me-2"></i>
                        <strong>Success!</strong> Your new API key is shown below. Copy it now as it will be starred after you leave this page.
                    </div>
                    @endif

                    <div class="bg-body-secondary bg-opacity-50 p-4 rounded-4 border border-secondary border-opacity-10 mb-4 position-relative overflow-hidden shadow-sm">
                        <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
                            <div class="flex-grow-1 overflow-hidden me-3">
                                <span class="text-muted small d-block mb-1 fw-bold">
                                    ACTIVE SECRET KEY @if($isNew)<span class="badge bg-success ms-2">NEWLY GENERATED</span>@endif
                                </span>
                                <code class="fs-5 fw-bold text-primary-custom text-break" id="apiKeyDisplay" style="letter-spacing: 1px;">{{ $displayKey }}</code>
                                <input type="hidden" id="fullApiKey" value="{{ $user->api_key }}">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn bg-primary-custom text-white btn-sm py-2 px-3 shadow-sm border-0" onclick="copyKey(this)" title="Copy Full Key">
                                    <i class="fa-solid fa-copy me-1"></i> Copy
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm py-2 px-3 border-0 bg-opacity-25" onclick="toggleKeyVisibility(this)">
                                    <i class="fa-solid fa-eye text-white"></i>
                                </button>
                            </div>
                        </div>
                        <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 8rem; margin-top: -2rem; margin-right: -1rem; color: var(--primary-color);">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <form action="{{ route('profile.api-key.generate') }}" method="POST" class="flex-grow-1">
                            @csrf
                            <button type="submit" class="btn btn-primary-custom w-100 py-3 fw-bold shadow-sm" onclick="return confirm('Generating a new key will instantly invalidate your current one. Proceed?')">
                                <i class="fa-solid fa-arrows-rotate me-2"></i> Generate API Key
                            </button>
                        </form>
                        <form action="{{ route('profile.api-key.delete') }}" method="POST" class="flex-grow-1">
                            @csrf
                            <button type="submit" class="btn bg-danger bg-opacity-10 text-danger w-100 py-3 fw-bold border-0" onclick="return confirm('Are you sure you want to delete your API key? This will stop all your automated integrations.')">
                                <i class="fa-solid fa-trash-can me-2"></i> Delete Key
                            </button>
                        </form>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <div class="opacity-20 mb-3">
                            <i class="fa-solid fa-key fa-4x"></i>
                        </div>
                        <h6 class="text-muted">No API key found</h6>
                        <p class="text-muted small mb-4">You need an API key to access our reseller services programmatically.</p>
                        <form action="{{ route('profile.api-key.generate') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn bg-primary-custom px-5 py-3 fw-bold shadow-lg text-white">
                                Generate API Key
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Two-Factor Authentication -->
            <div class="premium-card shadow-lg mb-4 overflow-hidden border-primary border-opacity-10">
                <div class="p-4 border-bottom border-secondary bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-shield-halved text-primary-custom me-2"></i> Security Center</h5>
                    @if($user->two_factor_enabled)
                        <span class="badge bg-success text-white rounded-pill px-3 shadow-sm">ACTIVATED</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-25 text-muted rounded-pill px-3">INACTIVE</span>
                    @endif
                </div>
                <div class="p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">Two-Factor Authentication (2FA)</h6>
                            <p class="text-muted small mb-0">Protect your account with a secondary verification code sent to your email.</p>
                        </div>
                        <form action="{{ route('profile.2fa.toggle') }}" method="POST">
                            @csrf
                            <div class="form-check form-switch">
                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="twoFactorToggle" 
                                    {{ $user->two_factor_enabled ? 'checked' : '' }} 
                                    onchange="this.form.submit()" 
                                    style="width: 3rem; height: 1.5rem; cursor: pointer;">
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Log -->
            <div class="premium-card shadow-sm">
                <div class="p-4 border-bottom border-secondary bg-dark bg-opacity-25 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Recent Security Activity</h5>
                    <span class="badge bg-primary text-white px-3 shadow-sm">PROTECTED</span>
                </div>
                <div class="p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            @php 
                                $logs = \App\Models\ActivityLog::where('user_id', $user->id)
                                    ->where('description', 'like', '%API key%')
                                    ->latest()->limit(5)->get();
                            @endphp
                            @forelse($logs as $log)
                            <tr>
                                <td class="ps-4">
                                    <div class="small fw-bold">{{ $log->description }}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="pe-4 text-end">
                                    <span class="text-muted small">ID #{{ $log->id }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-center py-4 text-muted small">No security events logged.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyKey(btn) {
    const fullKey = document.getElementById("fullApiKey").value;
    
    // Modern API
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(fullKey).then(() => {
            showSuccess(btn);
        });
    } else {
        // Fallback for non-HTTPS/older browsers
        const textArea = document.createElement("textarea");
        textArea.value = fullKey;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showSuccess(btn);
        } catch (err) {
            console.error('Fallback copy failed', err);
        }
        document.body.removeChild(textArea);
    }
}

function showSuccess(btn) {
    const icon = btn.querySelector('i');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
    btn.classList.replace('btn-primary-custom', 'btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalContent;
        btn.classList.replace('btn-success', 'btn-primary-custom');
    }, 2000);
}

function toggleKeyVisibility(btn) {
    const display = document.getElementById("apiKeyDisplay");
    const fullKey = document.getElementById("fullApiKey").value;
    const icon = btn.querySelector('i');
    
    if (display.innerText.includes('****')) {
        display.innerText = fullKey;
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        btn.classList.replace('btn-outline-secondary', 'btn-primary-custom');
    } else {
        display.innerText = fullKey.substring(0, 8) + '********************************' + fullKey.substring(fullKey.length - 4);
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        btn.classList.replace('btn-primary-custom', 'btn-outline-secondary');
    }
}
</script>
@endsection
