@extends('layouts.app')

@section('header_title', 'Manage API Providers')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-3" style="background-color: rgba(20, 184, 166, 0.1); color: var(--accent-color);" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <!-- List of Providers -->
    <div class="col-12">
        <div class="premium-card p-0 h-100">
            <div class="p-4 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Configured Providers</h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('hq.providers.pricing') }}" class="btn btn-outline-warning btn-sm px-3 rounded-pill text-dark fw-bold" style="background-color: #fef08a; border-color: #facc15;">
                        <i class="fa-solid fa-tags me-1"></i> Pricing & Markup Settings
                    </a>
                    <button class="btn btn-primary btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#addProviderModal" onclick="resetModal()">
                        <i class="fa-solid fa-plus me-1"></i> Add Provider
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-borderless align-middle mb-0">
                    <thead class="bg-light bg-opacity-10">
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Services</th>
                            <th>Balance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($providers as $provider)
                        <tr>
                            <td>
                                <strong>{{ $provider->name }}</strong><br>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 150px;" title="{{ $provider->api_url }}">{{ $provider->api_url }}</small>
                            </td>
                            <td>
                                @if($provider->is_active)
                                    <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-2">Active</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-2">Inactive</span>
                                @endif
                            </td>
                            <td class="fw-bold fs-5 text-primary-custom" style="width: 120px;">
                                <span id="svc_{{ $provider->id }}">0</span>
                                <a href="#" class="text-decoration-none ms-1 refresh-btn" data-id="{{ $provider->id }}" data-type="services-count" data-target="svc_{{ $provider->id }}">
                                    <i class="fa-solid fa-arrows-rotate small"></i>
                                </a>
                            </td>
                            <td class="text-muted" style="width: 150px;">
                                <span id="bal_{{ $provider->id }}">0.00</span>
                                <a href="#" class="text-decoration-none ms-1 refresh-btn" data-id="{{ $provider->id }}" data-type="balance" data-target="bal_{{ $provider->id }}">
                                    <i class="fa-solid fa-arrows-rotate small"></i>
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hq.providers.pricing', ['provider_id' => $provider->id]) }}" class="btn btn-sm btn-outline-warning rounded-circle" title="Pricing & Markup" style="width: 32px; height: 32px; padding: 0; line-height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-tags" style="font-size: 0.75rem;"></i>
                                    </a>
                                    <a href="{{ route('hq.providers.debug', $provider->id) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-circle" title="Debug API Response" style="width: 32px; height: 32px; padding: 0; line-height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-bug" style="font-size: 0.75rem;"></i>
                                    </a>
                                    
                                    <button class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Provider" onclick="editProvider('{{ $provider->name }}', '{{ $provider->api_url }}')" data-bs-toggle="modal" data-bs-target="#addProviderModal" style="width: 32px; height: 32px; padding: 0;">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    
                                    <form action="{{ route('hq.providers.toggle', $provider->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-circle" title="{{ $provider->is_active ? 'Deactivate' : 'Activate' }}" style="width: 32px; height: 32px; padding: 0;">
                                            <i class="fa-solid {{ $provider->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('hq.providers.destroy', $provider->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this provider? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete Provider" style="width: 32px; height: 32px; padding: 0;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted mb-3">
                                    <i class="fa-solid fa-server fs-1 opacity-50"></i>
                                </div>
                                <h6 class="fw-bold">No providers configured</h6>
                                <p class="small text-muted mb-0">Add a provider like Momopanel to get started.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Update Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1" aria-labelledby="addProviderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title fw-bold" id="addProviderModalLabel"><i class="fa-solid fa-server text-primary-custom me-2"></i> <span id="modalTitleText">Add Provider</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--invert-close-btn);"></button>
            </div>
            <form action="{{ route('hq.providers.store') }}" method="POST">
                <div class="modal-body p-4">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Provider Name</label>
                        <input type="text" name="name" id="providerNameInput" class="form-control form-control-lg" placeholder="e.g. SMMKings" required style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color);">
                        <div class="form-text small mt-1">If updating, do not change this name.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">API Endpoint URL</label>
                        <input type="url" name="api_url" id="providerUrlInput" class="form-control form-control-lg" placeholder="https://example.com/api/v2" required style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color);">
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">API Key</label>
                        <input type="password" name="api_key" class="form-control form-control-lg" placeholder="Enter your secret API key" required style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color);">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function resetModal() {
        document.getElementById('modalTitleText').innerText = 'Add Provider';
        document.getElementById('providerNameInput').value = '';
        document.getElementById('providerNameInput').readOnly = false;
        document.getElementById('providerUrlInput').value = '';
    }

    function editProvider(name, url) {
        document.getElementById('modalTitleText').innerText = 'Update Provider';
        document.getElementById('providerNameInput').value = name;
        document.getElementById('providerNameInput').readOnly = true; // Prevent changing name during edit since it's the unique key
        document.getElementById('providerUrlInput').value = url;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const refreshBtns = document.querySelectorAll('.refresh-btn');

        async function fetchData(btn) {
            const providerId = btn.getAttribute('data-id');
            const type = btn.getAttribute('data-type');
            const targetId = btn.getAttribute('data-target');
            const targetSpan = document.getElementById(targetId);
            const icon = btn.querySelector('i');

            icon.classList.add('fa-spin');
            targetSpan.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary" role="status"></span>';

            try {
                const response = await fetch(`/hq/providers/${providerId}/${type}`);
                const data = await response.json();
                
                if (response.ok) {
                    targetSpan.innerText = type === 'balance' ? data.balance : data.count;
                } else {
                    targetSpan.innerText = 'Err';
                }
            } catch (e) {
                targetSpan.innerText = 'Err';
            } finally {
                icon.classList.remove('fa-spin');
            }
        }

        refreshBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                fetchData(this);
            });
        });

        refreshBtns.forEach(btn => fetchData(btn));
    });
</script>
@endpush
@endsection
