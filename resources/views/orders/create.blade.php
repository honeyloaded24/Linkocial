@extends('layouts.app')

@section('header_title', 'New Order')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            @include('partials.service_ticker', ['service' => 'smm'])
            <!-- Channel Selector -->
            <div class="mb-4">
                <h6 class="text-muted small fw-bold text-uppercase mb-3">Choose a Channel</h6>
                <div class="d-flex flex-wrap gap-2 channel-container">
                    <button type="button" class="btn btn-light border channel-filter active-channel active px-4 py-2 rounded-pill" data-platform="All">
                        <i class="fa-solid fa-layer-group me-2"></i> Everything
                    </button>
                    @foreach($channels as $channel)
                    <button type="button" class="btn btn-light border channel-filter px-4 py-2 rounded-pill" data-platform="{{ $channel['name'] }}">
                        <i class="{{ $channel['icon'] }} me-2" style="color: {{ $channel['color'] }};"></i> {{ $channel['name'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="premium-card p-4 h-100">
                        <h4 class="fw-bold mb-4">Place a New Order</h4>
                        
                        <form action="{{ route('orders.store') }}" method="POST" id="newOrderForm">
                            @csrf
                            
                            <!-- Category Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase">Select Category</label>
                                <div class="position-relative">
                                    <div class="form-select form-select-lg d-flex align-items-center justify-content-between" id="categoryPickerTrigger" style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color); cursor: pointer; height: auto; min-height: 58px; padding-top: 12px; padding-bottom: 12px;">
                                        <span id="selectedCategoryLabel" class="text-wrap" style="line-height: 1.2;">-- Pick a platform first --</span>
                                    </div>
                                    
                                    <div class="premium-card p-0 mt-2 position-absolute w-100 d-none shadow-lg overflow-auto" id="categoryPickerDropdown" style="z-index: 1060; max-height: 300px; border: 1px solid var(--border-color); background: var(--bg-surface);">
                                        <div class="list-group list-group-flush" id="categoryList">
                                            <!-- Dynamic content -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase">Select Service</label>
                                <div class="position-relative">
                                    <div class="form-select form-select-lg d-flex align-items-center justify-content-between" id="servicePickerTrigger" style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color); cursor: pointer;">
                                        <span id="selectedServiceLabel" class="text-truncate">-- Choose a category first --</span>
                                    </div>
                                    <input type="hidden" name="service_id" id="service_id_input" required>
                                    
                                    <div class="premium-card p-0 mt-2 position-absolute w-100 d-none shadow-lg overflow-auto" id="servicePickerDropdown" style="z-index: 1050; max-height: 400px; border: 1px solid var(--border-color); background: var(--bg-surface);">
                                        <div class="p-2 border-bottom sticky-top bg-surface">
                                            <input type="text" id="serviceSearch" class="form-control form-control-sm" placeholder="Search for a service...">
                                        </div>
                                        <div class="list-group list-group-flush" id="serviceList">
                                            @foreach($services ?? [] as $service)
                                                @php /** @var \App\Models\Service $service */ @endphp
                                                <button type="button" class="list-group-item list-group-item-action border-0 py-3 service-item" 
                                                        data-id="{{ $service->id }}"
                                                        data-name="{{ $service->name }}"
                                                        data-category="{{ $service->category }}"
                                                        data-platform="{{ $service->getChannelName() }}"
                                                        data-min="{{ $service->min }}" 
                                                        data-max="{{ $service->max }}" 
                                                        data-price="{{ money($service->selling_price, false) }}"
                                                        data-desc="{{ $service->description }}"
                                                        data-average-time="{{ $service->average_time }}"
                                                        style="background: transparent; color: var(--text-main); display: none;">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <div class="flex-grow-1" style="white-space: normal; line-height: 1.4;">
                                                            <span class="fw-bold d-block mb-1">{{ $service->name }}</span>
                                                            <div class="d-flex gap-2 align-items-center">
                                                                <span class="badge bg-light bg-opacity-10 text-muted border" style="font-size: 0.65rem;">ID: {{ $service->id }}</span>
                                                                @if($service->is_hot) <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">HOT</span> @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-end" style="min-width: 80px;">
                                                            <div class="fw-bold text-primary-custom">{{ money($service->selling_price) }}</div>
                                                            <div class="text-muted small" style="font-size: 0.7rem;">per 1k</div>
                                                        </div>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Link -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase">Target Link</label>
                                <input type="url" name="link" class="form-control form-control-lg" placeholder="https://instagram.com/p/..." required style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color);">
                            </div>

                            <!-- Quantity -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Quantity</label>
                                    <input type="number" name="quantity" id="quantityInput" class="form-control form-control-lg" placeholder="1000" required style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color);">
                                    <div class="form-text mt-2" id="minMaxText">Min: 0 | Max: 0</div>
                                </div>
                            </div>

                            <div class="p-3 rounded text-center mb-4" style="background-color: rgba(20, 184, 166, 0.05); border: 1px dashed var(--accent-color);">
                                <div class="row g-0 align-items-center">
                                    <div class="col-6 border-end">
                                        <span class="text-muted small fw-bold d-block mb-1">Available Balance</span>
                                        <h5 class="fw-bold mb-0" id="userBalanceText" data-balance="{{ money(auth()->user()->balance, false) }}">
                                            {{ money(auth()->user()->balance) }}
                                        </h5>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted small fw-bold d-block mb-1">Total Charge</span>
                                        <h3 class="fw-bold mb-0 text-primary-custom" id="totalCharge">{{ get_currency_symbol() }} 0.00</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <!-- Service Info Card (Right Side) -->
                    <div class="premium-card p-4 h-100 d-none" id="serviceDetailCard">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-primary-custom d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-circle-info fa-xl text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-1" id="detailName">Service Details</h5>
                            <span class="badge bg-secondary rounded-pill" id="detailId">ID: 0</span>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small fw-bold">RATE PER 1000</span>
                                <span class="fw-bold text-primary-custom" id="detailRate">{{ get_currency_symbol() }} 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small fw-bold">AVG. TIME</span>
                                <span class="fw-bold text-info" id="detailAvgTime">Not Available</span>
                            </div>
                        </div>

                        <div class="border-top pt-4">
                            <h6 class="fw-bold mb-3 small text-uppercase">Description / Instructions</h6>
                            <div class="text-muted lh-base" id="detailDesc" style="white-space: pre-line; font-size: 0.75rem;">
                                No description provided for this service.
                            </div>
                        </div>

                        <div class="mt-auto pt-4">
                            <div class="alert alert-info border-0 small mb-0">
                                <i class="fa-solid fa-shield-halved me-2"></i>
                                Your profile or post must be <strong>public</strong> for this service to work correctly.
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder when no service selected -->
                    <div class="premium-card p-5 h-100 d-flex flex-column align-items-center justify-content-center text-center opacity-50" id="servicePlaceholder">
                        <i class="fa-solid fa-mouse-pointer fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">Select a service to view dynamic instructions and delivery times.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Order Guide Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="premium-card p-4 p-md-5 border-0 shadow-sm" style="background-color: var(--bg-surface);">
                    <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                        <i class="fa-solid fa-book-open text-primary fs-3 me-3"></i>
                        <h3 class="fw-bold mb-0">Order Guide & General Rules</h3>
                    </div>

                    <div class="accordion accordion-flush promo-accordion" id="orderGuideAccordion">
                        <div class="row g-3">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <!-- Rule 1 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-qty">
                                            <i class="fa-solid fa-list-ol text-primary me-2"></i> Quantity Rules
                                        </button>
                                    </h2>
                                    <div id="guide-qty" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• Some services require specific order multiples.</li>
                                                <li>• Wrong quantities will be auto-canceled & refunded.</li>
                                                <li>• Always check service details before ordering.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rule 2 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-status">
                                            <i class="fa-solid fa-circle-info text-primary me-2"></i> Order Status Meanings
                                        </button>
                                    </h2>
                                    <div id="guide-status" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• <strong>Pending:</strong> Order is waiting to start (0-24h).</li>
                                                <li>• <strong>In Progress:</strong> Delivery is running.</li>
                                                <li>• <strong>Partial / Canceled:</strong> Auto-refund for remaining balance.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rule 3 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-safety">
                                            <i class="fa-solid fa-link-slash text-primary me-2"></i> Profile & Link Safety
                                        </button>
                                    </h2>
                                    <div id="guide-safety" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• Do <strong>NOT</strong> change username or link during delivery.</li>
                                                <li>• Changes may cause completion <strong>without refund</strong>.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rule 4 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-speed">
                                            <i class="fa-solid fa-bolt-lightning text-primary me-2"></i> Delivery Speed
                                        </button>
                                    </h2>
                                    <div id="guide-speed" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• Speed depends on server daily limit.</li>
                                                <li>• Large or old accounts may process slower.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <!-- Rule 5 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-followers">
                                            <i class="fa-solid fa-users text-primary me-2"></i> Followers Orders
                                        </button>
                                    </h2>
                                    <div id="guide-followers" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• Delivered gradually by system speed.</li>
                                                <li>• Existing followers need extra time.</li>
                                                <li>• Re-ordering too soon may reset delivery.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rule 6 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-results">
                                            <i class="fa-solid fa-triangle-exclamation text-primary me-2"></i> Completed but No Results?
                                        </button>
                                    </h2>
                                    <div id="guide-results" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• Check drop rate / completion ratio / refill policy.</li>
                                                <li>• Some orders complete due to spam detection.</li>
                                                <li>• Contact support before placing a new order.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rule 7 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-refunds">
                                            <i class="fa-solid fa-ban text-danger me-2"></i> Refund & Duplicates
                                        </button>
                                    </h2>
                                    <div id="guide-refunds" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• <strong>Duplicates:</strong> Same link + active order = no refund.</li>
                                                <li>• <strong>No Refill:</strong> These cancel refill eligibility.</li>
                                                <li>• <strong>Cancellations:</strong> Orders cannot be canceled manually.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rule 8 -->
                                <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#guide-best">
                                            <i class="fa-solid fa-check-double text-success me-2"></i> Best Practice
                                        </button>
                                    </h2>
                                    <div id="guide-best" class="accordion-collapse collapse" data-bs-parent="#orderGuideAccordion">
                                        <div class="accordion-body small text-muted lh-lg">
                                            <ul class="list-unstyled mb-0">
                                                <li>• Read service descriptions carefully.</li>
                                                <li>• Avoid duplicate orders.</li>
                                                <li>• Choose <strong>stable</strong> services over fast ones.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-2 p-3 bg-warning bg-opacity-10 rounded-4 border border-warning border-opacity-25">
                        <p class="mb-0 small text-warning">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> 
                            <strong>Note:</strong> Engagement is not guaranteed. Cheap services may slow down during peak load.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const platformInstructions = {!! \App\Models\Setting::get('platform_instructions_json', '{}') !!};

    document.addEventListener('DOMContentLoaded', function() {
        const pickerTrigger = document.getElementById('servicePickerTrigger');
        const pickerDropdown = document.getElementById('servicePickerDropdown');
        const serviceIdInput = document.getElementById('service_id_input');
        const selectedServiceLabel = document.getElementById('selectedServiceLabel');
        const serviceItems = Array.from(document.querySelectorAll('.service-item'));
        const quantityInput = document.getElementById('quantityInput');
        const totalCharge = document.getElementById('totalCharge');
        const minMaxText = document.getElementById('minMaxText');
        
        const categoryPickerTrigger = document.getElementById('categoryPickerTrigger');
        const categoryPickerDropdown = document.getElementById('categoryPickerDropdown');
        const categoryList = document.getElementById('categoryList');
        const selectedCategoryLabel = document.getElementById('selectedCategoryLabel');

        const channelFilters = document.querySelectorAll('.channel-filter');
        const serviceSearch = document.getElementById('serviceSearch');
        
        const detailCard = document.getElementById('serviceDetailCard');
        const placeholder = document.getElementById('servicePlaceholder');
        const detailName = document.getElementById('detailName');
        const detailId = document.getElementById('detailId');
        const detailRate = document.getElementById('detailRate');
        const detailAvgTime = document.getElementById('detailAvgTime');
        const detailDesc = document.getElementById('detailDesc');

        let currentPlatform = 'All';
        let currentCategory = '';

        // 1. Channel Filter Click
        channelFilters.forEach(btn => {
            btn.addEventListener('click', function() {
                // Reset all
                channelFilters.forEach(b => {
                    b.classList.remove('active-channel', 'active');
                    // Reset icon colors handled by CSS mostly, but for brand icons:
                    const icon = b.querySelector('i');
                    if (icon && b.getAttribute('data-platform') !== 'All') {
                        icon.style.color = icon.getAttribute('data-original-color');
                    }
                });

                // Set active
                this.classList.add('active-channel', 'active');
                
                // Make icon white on active
                const activeIcon = this.querySelector('i');
                if (activeIcon) {
                    if (!activeIcon.getAttribute('data-original-color')) {
                        activeIcon.setAttribute('data-original-color', activeIcon.style.color);
                    }
                    activeIcon.style.color = 'white';
                }

                currentPlatform = this.getAttribute('data-platform');
                
                // Update Target Link Placeholder
                const linkInput = document.querySelector('input[name="link"]');
                const placeholders = {
                    'Instagram': 'https://www.instagram.com/p/...',
                    'TikTok': 'https://www.tiktok.com/@user/video/...',
                    'YouTube': 'https://www.youtube.com/watch?v=...',
                    'X': 'https://x.com/user/status/...',
                    'Facebook': 'https://www.facebook.com/...',
                    'Telegram': 'https://t.me/...',
                    'Spotify': 'https://open.spotify.com/track/...',
                    'All': 'https://social-link.com/...'
                };
                linkInput.placeholder = placeholders[currentPlatform] || 'https://social-link.com/...';

                updateCategories();
                resetServicePicker();
            });
        });

        // 2. Update Categories List based on Platform
        function updateCategories() {
            const categories = new Set();
            serviceItems.forEach(item => {
                if (currentPlatform === 'All' || item.getAttribute('data-platform') === currentPlatform) {
                    const cat = item.getAttribute('data-category');
                    if (cat) categories.add(cat);
                }
            });

            categoryList.innerHTML = '';
            Array.from(categories).sort().forEach(cat => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action border-0 py-3 small';
                btn.style.background = 'transparent';
                btn.style.color = 'var(--text-main)';
                btn.innerHTML = `<span class="text-wrap d-block" style="line-height: 1.3;">${cat}</span>`;
                
                btn.addEventListener('click', () => {
                    currentCategory = cat;
                    selectedCategoryLabel.innerText = cat;
                    categoryPickerDropdown.classList.add('d-none');
                    resetServicePicker();
                    filterServices();
                });
                
                categoryList.appendChild(btn);
            });

            if (categories.size === 0) {
                categoryList.innerHTML = '<div class="p-4 text-center text-muted small">No categories found for this platform.</div>';
            }
        }

        // 3. Category Picker Toggle
        categoryPickerTrigger.addEventListener('click', () => {
            categoryPickerDropdown.classList.toggle('d-none');
            pickerDropdown.classList.add('d-none'); // Close other picker
        });

        function resetServicePicker() {
            serviceIdInput.value = '';
            selectedServiceLabel.innerText = currentCategory ? 'Pick a service...' : '-- Choose a category first --';
            detailCard.classList.add('d-none');
            placeholder.classList.remove('d-none');
            totalCharge.innerText = '₦ 0.00';
        }

        // 4. Filter Services List
        function filterServices() {
            const searchTerm = serviceSearch.value.toLowerCase();
            serviceItems.forEach(item => {
                const matchesPlatform = currentPlatform === 'All' || item.getAttribute('data-platform') === currentPlatform;
                const matchesCategory = !currentCategory || item.getAttribute('data-category') === currentCategory;
                const matchesSearch = item.getAttribute('data-name').toLowerCase().includes(searchTerm);

                if (matchesPlatform && matchesCategory && matchesSearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        serviceSearch.addEventListener('input', filterServices);

        // 5. Service Picker Toggle
        pickerTrigger.addEventListener('click', () => {
            if (!currentCategory && currentPlatform !== 'All') {
                alert('Please select a category first.');
                return;
            }
            pickerDropdown.classList.toggle('d-none');
            categoryPickerDropdown.classList.add('d-none'); // Close other picker
        });

        document.addEventListener('click', (e) => {
            if (!pickerTrigger.contains(e.target) && !pickerDropdown.contains(e.target)) {
                pickerDropdown.classList.add('d-none');
            }
            if (!categoryPickerTrigger.contains(e.target) && !categoryPickerDropdown.contains(e.target)) {
                categoryPickerDropdown.classList.add('d-none');
            }
        });

        // 6. Select Service
        serviceItems.forEach(item => {
            item.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const min = this.getAttribute('data-min');
                const max = this.getAttribute('data-max');
                const price = this.getAttribute('data-price');
                const desc = this.getAttribute('data-desc');
                const avgTime = this.getAttribute('data-average-time');
                const platform = this.getAttribute('data-platform');

                serviceIdInput.value = id;
                selectedServiceLabel.innerText = name;
                pickerDropdown.classList.add('d-none');

                placeholder.classList.add('d-none');
                detailCard.classList.remove('d-none');
                
                minMaxText.innerHTML = `Min: ${min} | Max: ${max}`;
                quantityInput.setAttribute('min', min);
                quantityInput.setAttribute('max', max);

                detailName.innerText = name;
                detailId.innerText = `ID: ${id}`;
                detailRate.innerText = `${currencySymbol} ${parseFloat(price).toFixed(2)}`;
                detailAvgTime.innerText = (avgTime && avgTime !== 'null' && avgTime !== "") ? avgTime : '5mins - 24hrs';
                
                let finalDesc = 'No instructions provided.';
                if (desc && desc !== "null" && desc !== "") {
                    finalDesc = desc;
                } else if (platform && platformInstructions[platform]) {
                    finalDesc = platformInstructions[platform];
                }
                detailDesc.innerHTML = finalDesc;

                calculateCost();
            });
        });

        const currencySymbol = "{{ get_currency_symbol() }}";

        function calculateCost() {
            const selectedItem = serviceItems.find(item => item.getAttribute('data-id') === serviceIdInput.value);
            if (selectedItem) {
                const pricePerK = parseFloat(selectedItem.getAttribute('data-price'));
                const qty = parseInt(quantityInput.value) || 0;
                const cost = (qty / 1000) * pricePerK;
                totalCharge.innerText = `${currencySymbol} ${cost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

                // --- Balance Warning Logic ---
                const userBalanceText = document.getElementById('userBalanceText');
                const userBalance = parseFloat(userBalanceText.getAttribute('data-balance'));
                
                if (cost > userBalance) {
                    userBalanceText.classList.remove('text-main');
                    userBalanceText.classList.add('text-danger');
                    totalCharge.classList.add('text-danger');
                } else {
                    userBalanceText.classList.remove('text-danger');
                    userBalanceText.classList.add('text-main');
                    totalCharge.classList.remove('text-danger');
                }
            }
        }

        quantityInput.addEventListener('input', calculateCost);

        // Initial category load
        updateCategories();

        // 7. Auto-select platform from URL if provided
        @if(isset($selectedPlatform) && $selectedPlatform)
            const targetPlatform = "{{ $selectedPlatform }}";
            const targetBtn = Array.from(channelFilters).find(btn => 
                btn.getAttribute('data-platform').toLowerCase() === targetPlatform.toLowerCase()
            );
            if (targetBtn) {
                targetBtn.click();
            }
        @endif
    });
</script>
@endpush
@endsection
