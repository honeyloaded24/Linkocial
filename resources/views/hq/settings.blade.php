@extends('layouts.app')

@section('header_title', 'HQ: Site Settings')

@section('content')
<div class="container-fluid">
    <!-- Top Stats/Info Row -->
    <div class="row g-4 mb-4">
        <div class="col-xl-9">
            <div class="premium-card p-0 shadow-sm border-0 overflow-hidden">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-fill border-0 bg-light bg-opacity-10" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 fw-bold border-0" id="global-tab" data-bs-toggle="tab" data-bs-target="#global-panel" type="button" role="tab">
                            <i class="fa-solid fa-earth-africa me-2"></i> Global Configuration
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="platform-tab" data-bs-toggle="tab" data-bs-target="#platform-panel" type="button" role="tab">
                            <i class="fa-solid fa-puzzle-piece me-2"></i> User Instructions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="promo-tab" data-bs-toggle="tab" data-bs-target="#promo-panel" type="button" role="tab">
                            <i class="fa-solid fa-fire me-2"></i> Marketing Popup
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-panel" type="button" role="tab">
                            <i class="fa-solid fa-key me-2"></i> APIs & Integrations
                        </button>
                    </li>
                </ul>

                <form action="{{ route('hq.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="d-flex justify-content-between align-items-center p-3 px-4 border-bottom bg-light bg-opacity-10">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary">
                                <i class="fa-solid fa-gears fs-5"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">Platform Control Center</h5>
                                <small class="text-muted">Global overrides and system-wide parameters</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary-custom px-5 py-2 shadow-sm fw-bold rounded-pill">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
                        </button>
                    </div>

                    <div class="tab-content p-4" id="settingsTabsContent">
                        <!-- Tab 1: Global Config -->
                        <div class="tab-pane fade show active" id="global-panel" role="tabpanel">
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-3 border-bottom pb-2" style="letter-spacing: 1px;">Branding & Identity</h6>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Platform Name</label>
                                    <input type="text" name="site_name" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('site_name') }}" required style="color: var(--text-main);">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Light Mode Logo</label>
                                    <input type="file" name="site_logo_light" class="form-control border-secondary bg-transparent py-1" accept="image/*">
                                    @if(\App\Models\Setting::get('site_logo_light'))
                                        <small class="text-success mt-1 d-block"><i class="fa-solid fa-check-circle me-1"></i> Current logo uploaded</small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Dark Mode Logo</label>
                                    <input type="file" name="site_logo_dark" class="form-control border-secondary bg-transparent py-1" accept="image/*">
                                    @if(\App\Models\Setting::get('site_logo_dark'))
                                        <small class="text-success mt-1 d-block"><i class="fa-solid fa-check-circle me-1"></i> Current logo uploaded</small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Site Favicon</label>
                                    <input type="file" name="site_favicon" class="form-control border-secondary bg-transparent py-1" accept="image/x-icon,image/png,image/jpeg">
                                    @if(\App\Models\Setting::get('site_favicon'))
                                        <small class="text-success mt-1 d-block"><i class="fa-solid fa-check-circle me-1"></i> Current favicon uploaded</small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control border-secondary bg-transparent py-2 text-center" value="{{ \App\Models\Setting::get('currency_symbol') }}" required style="color: var(--text-main); font-weight: bold;">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Contact Email</label>
                                    <input type="email" name="contact_email" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('contact_email') }}" required style="color: var(--text-main);">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Social Share (OG) Image</label>
                                    <input type="file" name="site_og_image" class="form-control border-secondary bg-transparent py-1" accept="image/png,image/jpeg,image/webp">
                                    @if(\App\Models\Setting::get('site_og_image'))
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <small class="text-success"><i class="fa-solid fa-check-circle me-1"></i> Current OG image uploaded</small>
                                            <a href="{{ \App\Models\Setting::get('site_og_image') }}" target="_blank" class="badge bg-light bg-opacity-10 text-muted border text-decoration-none x-small">Preview</a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-3 border-bottom pb-2" style="letter-spacing: 1px;">Telegram & Community Links</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase"><i class="fa-brands fa-telegram text-info me-1"></i> Telegram Channel URL</label>
                                    <input type="text" name="telegram_channel_url" class="form-control border-secondary bg-transparent py-2" placeholder="https://t.me/yourchannel" value="{{ \App\Models\Setting::get('telegram_channel_url', 'https://t.me/linkocial') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase"><i class="fa-solid fa-comments text-primary me-1"></i> Telegram Direct Chat URL</label>
                                    <input type="text" name="telegram_chat_url" class="form-control border-secondary bg-transparent py-2" placeholder="https://t.me/yourusername" value="{{ \App\Models\Setting::get('telegram_chat_url', 'https://t.me/linkocial_support') }}" style="color: var(--text-main);">
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-3 border-bottom pb-2" style="letter-spacing: 1px;">Financial Parameters</h6>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Min. Withdrawal (₦)</label>
                                    <input type="number" name="min_withdrawal" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('min_withdrawal') }}" required style="color: var(--text-main);">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Reseller Disc. (%)</label>
                                    <input type="number" name="reseller_discount" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('reseller_discount', 5) }}" required style="color: var(--text-main);">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted small fw-bold text-uppercase">VIP Disc. (%)</label>
                                    <input type="number" name="vip_discount" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('vip_discount', 10) }}" required style="color: var(--text-main);">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Aff. First Dep. (%)</label>
                                    <input type="number" name="affiliate_commission_first" class="form-control border-primary border-opacity-50 bg-primary bg-opacity-5 py-2 fw-bold" value="{{ \App\Models\Setting::get('affiliate_commission_first', 10) }}" required style="color: var(--text-main);">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Aff. Recurring (%)</label>
                                    <input type="number" name="affiliate_commission_recurring" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('affiliate_commission_recurring', 5) }}" required style="color: var(--text-main);">
                                </div>
                            </div>

                            <div class="premium-card p-4 bg-danger bg-opacity-10 border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-shield-halved me-2"></i>Maintenance Mode</h6>
                                        <p class="text-muted small mb-0">Immediately blocks all non-admin users from using the platform.</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="maintenance_mode" value="0">
                                        <input class="form-check-input fs-3" type="checkbox" name="maintenance_mode" value="1" {{ \App\Models\Setting::get('maintenance_mode') == '1' ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Platform Instructions -->
                        <div class="tab-pane fade" id="platform-panel" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="fw-bold mb-1">Service Instructions</h5>
                                    <p class="text-muted small mb-0">Provide "How to use" guides for specific platform channels.</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary-custom px-3 rounded-pill" onclick="addNewInstruction()">
                                    <i class="fa-solid fa-plus me-2"></i> Add Platform Guide
                                </button>
                            </div>

                            <div class="accordion border-0 shadow-sm rounded-4 overflow-hidden" id="instructionsAccordion">
                                @php
                                    $rawInstructions = \App\Models\Setting::get('platform_instructions_json', '[]');
                                    $instructions = json_decode($rawInstructions, true) ?: [];
                                    $idx = 0;
                                @endphp

                                @forelse($instructions as $platform => $text)
                                @php $idx++; @endphp
                                <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden shadow-sm border">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-3 fw-bold bg-light bg-opacity-25" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $idx }}">
                                            <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                                <span><i class="fa-solid fa-bookmark me-2 text-primary"></i> {{ $platform }} Instructions</span>
                                                <span class="badge bg-primary bg-opacity-10 text-primary small rounded-pill px-3">{{ Str::limit($text, 50) }}</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $idx }}" class="accordion-collapse collapse" data-bs-parent="#instructionsAccordion">
                                        <div class="accordion-body bg-white p-4">
                                            <div class="row g-4 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label text-muted small fw-bold text-uppercase">Target Platform</label>
                                                    <select name="platforms[]" class="form-select border-secondary bg-transparent py-2" required style="color: var(--text-main);">
                                                        @foreach($channels as $channel)
                                                            <option value="{{ $channel['name'] }}" {{ $platform == $channel['name'] ? 'selected' : '' }}>{{ $channel['name'] }}</option>
                                                        @endforeach
                                                        <option value="Other" {{ !in_array($platform, array_column($channels, 'name')) ? 'selected' : '' }}>Other (Keyword)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label text-muted small fw-bold text-uppercase">Instruction Content (HTML Allowed)</label>
                                                    <textarea name="descriptions[]" class="form-control border-secondary bg-transparent" rows="4" required style="color: var(--text-main);">{{ $text }}</textarea>
                                                </div>
                                                <div class="col-md-1 text-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm border-0 mb-2" onclick="this.closest('.accordion-item').remove()">
                                                        <i class="fa-solid fa-trash-can fs-5"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-5 text-muted bg-light bg-opacity-10 rounded-4" id="no-instructions-msg">
                                    <i class="fa-solid fa-info-circle fa-2x mb-3 opacity-25"></i>
                                    <p>No platform-specific instructions have been configured yet.</p>
                                </div>
                                @endforelse
                            </div>
                            <div id="instructionsContainer"></div>
                        </div>

                        <!-- Tab 3: Promo Popup -->
                        <div class="tab-pane fade" id="promo-panel" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <h5 class="fw-bold mb-1">Marketing Activation</h5>
                                        <p class="text-muted small">Configure the global welcome/promo popup for your users.</p>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 mb-3">
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded-4 border border-primary border-opacity-25 bg-primary bg-opacity-5">
                                                <div>
                                                    <h6 class="fw-bold mb-0">Enable Marketing Popup</h6>
                                                    <p class="text-muted small mb-0">Toggle this to show/hide the popup on user login.</p>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="promo_enabled" value="0">
                                                    <input class="form-check-input fs-3" type="checkbox" name="promo_enabled" value="1" {{ \App\Models\Setting::get('promo_enabled') == '1' ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Popup Title</label>
                                            <input type="text" name="promo_title" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. MEGA DEPOSIT BONUS!" value="{{ \App\Models\Setting::get('promo_title', 'Special Offer For You!') }}" style="color: var(--text-main);">
                                        </div>

                                        <div class="col-12 mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Popup Message / Content</label>
                                            <textarea name="promo_message" class="form-control border-secondary bg-transparent" rows="4" placeholder="Enter your marketing message here..." style="color: var(--text-main);">{{ \App\Models\Setting::get('promo_message', 'Get an instant bonus when you top up your account today!') }}</textarea>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Image URL (Optional)</label>
                                            <input type="text" name="promo_image" class="form-control border-secondary bg-transparent py-2" placeholder="https://example.com/banner.jpg" value="{{ \App\Models\Setting::get('promo_image') }}" style="color: var(--text-main);">
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Bonus %</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent border-secondary text-primary-custom fw-bold">+</span>
                                                <input type="number" name="promo_bonus_percent" class="form-control border-secondary bg-transparent" value="{{ \App\Models\Setting::get('promo_bonus_percent', 10) }}" style="color: var(--text-main);">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Min. Spend (₦)</label>
                                            <input type="number" name="promo_min_amount" class="form-control border-secondary bg-transparent" value="{{ \App\Models\Setting::get('promo_min_amount', 1000) }}" style="color: var(--text-main);">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Button Text</label>
                                            <input type="text" name="promo_button_text" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. Deposit Now" value="{{ \App\Models\Setting::get('promo_button_text', 'Get Started') }}" style="color: var(--text-main);">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Button Link</label>
                                            <input type="text" name="promo_button_link" class="form-control border-secondary bg-transparent py-2" placeholder="/wallet" value="{{ \App\Models\Setting::get('promo_button_link', '/wallet') }}" style="color: var(--text-main);">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="premium-card p-4 border border-primary border-opacity-10 bg-primary bg-opacity-5 h-100">
                                        <h6 class="fw-bold mb-3">Popup Preview</h6>
                                        <div class="border rounded-4 bg-white p-3 shadow-sm text-center">
                                            <div class="bg-primary bg-opacity-10 rounded-3 mb-3 d-flex align-items-center justify-content-center" style="height: 100px;">
                                                <i class="fa-solid fa-image fa-3x text-primary opacity-25"></i>
                                            </div>
                                            <div class="fw-bold text-dark mb-1">Offer Headline</div>
                                            <div class="text-muted x-small mb-3">Your marketing message will appear right here in this section...</div>
                                            <div class="btn btn-primary-custom btn-sm w-100 rounded-pill">Action Button</div>
                                        </div>
                                        <div class="mt-4 small text-muted">
                                            <i class="fa-solid fa-clock me-2"></i> Expiry:
                                            <input type="datetime-local" name="promo_expires_at" class="form-control form-control-sm border-secondary bg-transparent mt-2" value="{{ \App\Models\Setting::get('promo_expires_at') }}" style="color: var(--text-main);">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- System Actions Sidebar -->
        <div class="col-xl-3">
            <div class="premium-card p-4 shadow-sm bg-primary bg-opacity-10 border-0 mb-4">
                <h6 class="fw-bold text-primary mb-3">Platform Maintenance</h6>
                <p class="small text-muted mb-4">Running a critical update or changing prices? Use maintenance mode to prevent conflicts.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary border-0 bg-white shadow-sm text-start py-2" onclick="location.reload()">
                        <i class="fa-solid fa-arrows-rotate me-2"></i> Refresh Variables
                    </button>
                    <a href="{{ route('hq.logs') }}" class="btn btn-outline-secondary border-0 bg-white shadow-sm text-start py-2 text-decoration-none">
                        <i class="fa-solid fa-history me-2 text-primary"></i> Review System Audit
                    </a>
                </div>
            </div>

            <div class="premium-card p-4 shadow-sm border-0">
                <h6 class="fw-bold mb-3">Configuration Guide</h6>
                <div class="small text-muted">
                    <div class="mb-3">
                        <div class="fw-bold text-dark mb-1">Markup Strategy:</div>
                        Your <strong>VIP</strong> and <strong>Reseller</strong> discounts are deducted from the base selling price you set in the Services tab.
                    </div>
                    <div class="mb-3">
                        <div class="fw-bold text-dark mb-1">Instruction Context:</div>
                        Instructions defined here will override standard API descriptions for better user guidance.
                    </div>
                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 border border-warning border-opacity-25">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Always double-check currency symbols before saving.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template for JS (Enhanced for Accordion) -->
<div id="instructionTemplate" class="d-none">
    <div class="accordion-item border-0 mb-3 rounded-4 overflow-hidden shadow-sm border border-primary border-opacity-25">
        <h2 class="accordion-header">
            <button class="accordion-button py-3 fw-bold bg-primary bg-opacity-10 text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNEW">
                <i class="fa-solid fa-plus-circle me-2"></i> New Platform Guide Configuration
            </button>
        </h2>
        <div id="collapseNEW" class="accordion-collapse collapse show">
            <div class="accordion-body bg-white p-4">
                <div class="row g-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Target Platform</label>
                        <select name="platforms[]" class="form-select border-secondary bg-transparent py-2" style="color: var(--text-main);">
                            @foreach($channels as $channel)
                                <option value="{{ $channel['name'] }}">{{ $channel['name'] }}</option>
                            @endforeach
                            <option value="Other">Other (Keyword)</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label text-muted small fw-bold text-uppercase">Instruction Content</label>
                        <textarea name="descriptions[]" class="form-control border-secondary bg-transparent" rows="4" placeholder="Enter instructions here..." style="color: var(--text-main);"></textarea>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm border-0 mb-2" onclick="this.closest('.accordion-item').remove()">
                            <i class="fa-solid fa-trash-can fs-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function addNewInstruction() {
        const container = document.getElementById('instructionsContainer');
        const noDataMsg = document.getElementById('no-instructions-msg');
        
        if (noDataMsg) {
            noDataMsg.remove();
        }

        const template = document.getElementById('instructionTemplate').firstElementChild.cloneNode(true);
        
        // Ensure inputs are required in the new row
        template.querySelectorAll('select, textarea').forEach(el => el.required = true);
        
        container.appendChild(template);
    }
</script>
@endpush
@endsection
