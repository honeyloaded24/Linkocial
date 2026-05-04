@extends('layouts.app')

@section('header_title', 'HQ: Manage Services')

@push('scripts')
<style>
    .services-container { min-height: 700px; }
    .dropdown-item:hover { background-color: var(--primary-color) !important; color: white !important; }
</style>
<script>
    function setFilterValue(field, value, text) {
        document.getElementById('filter_' + field).value = value;
        document.getElementById('selected' + field.charAt(0).toUpperCase() + field.slice(1) + 'Text').innerText = text;
    }

    function toggleAll(status) {
        document.querySelectorAll('.service-active-switch').forEach(checkbox => {
            checkbox.checked = status;
        });
    }
</script>
@endpush

@section('content')
<div class="row mb-4 services-container">
    <div class="col-12">
        <div class="premium-card p-4 services-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Service Catalog</h5>
                    <p class="text-muted small mb-0">Set pricing and activate services synced from providers.</p>
                </div>
                <form action="{{ route('hq.services.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary-custom rounded-pill px-4">
                        Run Sync Now
                    </button>
                </form>
            </div>

            @if(session('success'))
                <div class="alert alert-success bg-success text-white border-0">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger bg-danger text-white border-0">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Filter Bar -->
            <form action="{{ route('hq.services') }}" method="GET" class="row g-3 mb-4 p-3 rounded-3 bg-light bg-opacity-10 border border-secondary">
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">SEARCH NAME</label>
                    <input type="text" name="search" class="form-control form-control-sm bg-transparent border-secondary" placeholder="e.g. Instagram" value="{{ request('search') }}" style="color: var(--text-main);">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">PROVIDER</label>
                    <select name="provider_id" class="form-select form-select-sm bg-transparent border-secondary" style="color: var(--text-main);">
                        <option value="" class="text-dark">All Providers</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" {{ request('provider_id') == $provider->id ? 'selected' : '' }} class="text-dark">{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">CHANNEL/TYPE</label>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary w-100 text-start dropdown-toggle bg-transparent border-secondary d-flex justify-content-between align-items-center" type="button" id="typeDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--text-main);">
                            <span id="selectedTypeText">{{ request('type') ?: 'All Types' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark shadow-lg overflow-auto" aria-labelledby="typeDropdown" style="max-height: 250px; border: 1px solid var(--border-color);">
                            <li><a class="dropdown-item py-2" href="#" onclick="setFilterValue('type', '', 'All Types')">All Types</a></li>
                            @foreach($types as $type)
                                <li><a class="dropdown-item py-2" href="#" onclick="setFilterValue('type', '{{ $type }}', '{{ $type }}')">{{ $type }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <input type="hidden" name="type" id="filter_type" value="{{ request('type') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">SALES FILTER</label>
                    <div class="form-check form-switch pt-1">
                        <input class="form-check-input" type="checkbox" name="recently_sold" id="recentlySoldSwitch" value="1" {{ request('recently_sold') == '1' ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label text-muted small" for="recentlySoldSwitch">Recently Sold (24h)</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary-custom w-100">Filter</button>
                    <a href="{{ route('hq.services') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>

            <form action="{{ route('hq.services.bulk-update') }}" method="POST">
                @csrf
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleAll(true)">
                            Enable All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAll(false)">
                            Disable All
                        </button>
                    </div>
                    <button type="submit" class="btn btn-success-custom rounded-pill px-4">
                        Save All Changes
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Service Name</th>
                                <th>Provider(s)</th>
                                <th>Cost (1k)</th>
                                <th>Selling Price (1k)</th>
                                <th>Status / Badges</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                            <tr>
                                <td class="text-muted fw-bold">#{{ $service->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $service->name }}</div>
                                    <div class="d-flex gap-1 mt-1">
                                        <span class="badge bg-light bg-opacity-10 text-muted border small" style="font-size: 0.6rem;">{{ $service->type }}</span>
                                        @if($service->is_hot) <span class="badge bg-danger rounded-pill px-2" style="font-size: 0.6rem;"><i class="fa-solid fa-fire me-1"></i> HOT</span> @endif
                                        @if($service->is_trending) <span class="badge bg-primary rounded-pill px-2" style="font-size: 0.6rem;"><i class="fa-solid fa-bolt me-1"></i> TRENDING</span> @endif
                                        @if($service->recent_orders_count > 0) 
                                            <span class="badge bg-success rounded-pill px-2" style="font-size: 0.6rem;"><i class="fa-solid fa-cart-shopping me-1"></i> RECENT SALE</span> 
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @foreach($service->mappings as $mapping)
                                        <span class="badge bg-secondary rounded-pill" title="Priority {{ $mapping->priority }}">{{ $mapping->provider->name }}</span>
                                    @endforeach
                                </td>
                                <td class="text-danger fw-bold">
                                    $ {{ number_format($service->mappings->min('original_price'), 2) }}
                                </td>
                                <td>
                                    <div class="input-group input-group-sm" style="width: 140px;">
                                        <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--border-color); color: var(--text-muted);">₦</span>
                                        <input type="number" name="services[{{ $service->id }}][selling_price]" class="form-control border-start-0 ps-0" value="{{ $service->selling_price }}" step="any" style="background-color: transparent; color: var(--text-main); border-color: var(--border-color);">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="form-check form-switch small">
                                            <input class="form-check-input service-active-switch" type="checkbox" name="services[{{ $service->id }}][is_active]" value="1" {{ $service->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label text-muted small">Active</label>
                                        </div>
                                        <div class="form-check form-switch small">
                                            <input class="form-check-input" type="checkbox" name="services[{{ $service->id }}][is_hot]" value="1" {{ $service->is_hot ? 'checked' : '' }}>
                                            <label class="form-check-label text-muted small">Hot</label>
                                        </div>
                                        <div class="form-check form-switch small">
                                            <input class="form-check-input" type="checkbox" name="services[{{ $service->id }}][is_trending]" value="1" {{ $service->is_trending ? 'checked' : '' }}>
                                            <label class="form-check-label text-muted small">Trending</label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>

            
            <!-- Pagination Placeholder -->
            <div class="d-flex justify-content-end mt-4">
                {{ $services->links('pagination::bootstrap-5') }}
            </div>
            
        </div>
    </div>
</div>
@endsection
