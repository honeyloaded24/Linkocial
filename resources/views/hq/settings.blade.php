@extends('layouts.app')

@section('header_title', 'HQ: Site Settings')

@section('content')
<div class="container-fluid">
    <!-- Top Stats/Info Row -->
    <div class="row g-4 mb-4">
        <div class="col-xl-12">
            <div class="premium-card p-0 shadow-sm border-0 overflow-hidden">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-fill border-0 bg-light bg-opacity-10" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 fw-bold border-0" id="global-tab" data-bs-toggle="tab" data-bs-target="#global-panel" type="button" role="tab">
                            <i class="fa-solid fa-earth-africa me-1"></i> Global
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="platform-tab" data-bs-toggle="tab" data-bs-target="#platform-panel" type="button" role="tab">
                            <i class="fa-solid fa-puzzle-piece me-1"></i> Instructions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="promo-tab" data-bs-toggle="tab" data-bs-target="#promo-panel" type="button" role="tab">
                            <i class="fa-solid fa-fire me-1"></i> Marketing
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-panel" type="button" role="tab">
                            <i class="fa-solid fa-key me-1"></i> APIs & Services
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees-panel" type="button" role="tab">
                            <i class="fa-solid fa-percent me-1"></i> Fees & Markups
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="slider-tab" data-bs-toggle="tab" data-bs-target="#slider-panel" type="button" role="tab">
                            <i class="fa-solid fa-images me-1"></i> Dashboard Slider
                        </button>
                    </li>
                </ul>

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
                    <button type="submit" form="mainSettingsForm" class="btn btn-primary-custom px-5 py-2 shadow-sm fw-bold rounded-pill">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
                    </button>
                </div>

                <div class="tab-content p-4" id="settingsTabsContent">
                    <form id="mainSettingsForm" action="{{ route('hq.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
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

                        <!-- Tab 4: APIs & Services -->
                        <div class="tab-pane fade" id="api-panel" role="tabpanel">
                            <!-- Korapay -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">Korapay (Fiat Payments)</h6>
                                    <small class="text-muted d-block mb-3">Sign up and get your API keys at <a href="https://korapay.com" target="_blank">korapay.com</a>.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Environment Mode</label>
                                    <select name="korapay_mode" class="form-select border-secondary bg-transparent py-2" style="color: var(--text-main);">
                                        <option value="test" {{ \App\Models\Setting::get('korapay_mode') === 'test' ? 'selected' : '' }}>Test Mode</option>
                                        <option value="live" {{ \App\Models\Setting::get('korapay_mode') === 'live' ? 'selected' : '' }}>Live Mode</option>
                                    </select>
                                </div>
                                <div class="col-12 m-0"></div> <!-- Break line -->
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Test Public Key</label>
                                    <input type="text" name="korapay_test_public_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('korapay_test_public_key') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Test Secret Key</label>
                                    <input type="text" name="korapay_test_secret_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('korapay_test_secret_key') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Live Public Key</label>
                                    <input type="text" name="korapay_live_public_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('korapay_live_public_key') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Live Secret Key</label>
                                    <input type="text" name="korapay_live_secret_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('korapay_live_secret_key') }}" style="color: var(--text-main);">
                                </div>
                            </div>

                            <!-- Heleket -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">Heleket (Crypto Payments)</h6>
                                    <small class="text-muted d-block mb-3">Sign up and get your Merchant ID & API Key at <a href="https://heleket.com" target="_blank">heleket.com</a>.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Merchant ID</label>
                                    <input type="text" name="heleket_merchant_id" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('heleket_merchant_id') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">API Key (Secret)</label>
                                    <input type="text" name="heleket_api_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('heleket_api_key') }}" style="color: var(--text-main);">
                                </div>
                            </div>

                            <!-- SMSPool -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">SMSPool (Virtual Numbers)</h6>
                                    <small class="text-muted d-block mb-3">Sign up and get your API Key at <a href="https://smspool.net" target="_blank">smspool.net</a>.</small>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label text-muted small fw-bold text-uppercase">SMSPool API Key</label>
                                    <input type="text" name="smspool_api_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('smspool_api_key') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-4">
                                    @php
                                        $rawMarkup = (float) \App\Models\Setting::get('vn_markup', 1.00);
                                        if ($rawMarkup > 5.0 || ($rawMarkup > 1.0 && $rawMarkup < 2.0)) {
                                            $rawMarkup = 1.00;
                                        }
                                    @endphp
                                    <label class="form-label text-muted small fw-bold text-uppercase">Markup Fee (USD)</label>
                                    <input type="number" step="0.01" min="0" name="vn_markup" class="form-control border-secondary bg-transparent py-2" value="{{ number_format($rawMarkup, 2) }}" style="color: var(--text-main);">
                                    <small class="text-muted" style="font-size: 0.75rem;">e.g., 1.00 adds a flat $1.00 fee to SMSPool cost.</small>
                                </div>
                            </div>

                            <!-- Fadded Logs Marketplace -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">Fadded.net (Logs Marketplace)</h6>
                                    <small class="text-muted d-block mb-3">Sign up and get your API key at <a href="https://fadded.net" target="_blank">fadded.net</a>.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Fadded API Key</label>
                                    <input type="text" name="fadded_api_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('fadded_api_key', config('services.fadded.key')) }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Fadded API URL / Endpoint</label>
                                    <input type="text" name="fadded_api_url" class="form-control border-secondary bg-transparent py-2" placeholder="https://fadded.net/api/v1/reseller" value="{{ \App\Models\Setting::get('fadded_api_url', config('services.fadded.url')) }}" style="color: var(--text-main);">
                                    <small class="text-muted" style="font-size: 0.75rem;">Default: <code>https://fadded.net/api/v1/reseller</code></small>
                                </div>
                            </div>

                            <!-- Gongozconcept VTU -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">Gongozconcept (VTU Services)</h6>
                                    <small class="text-muted d-block mb-3">Sign up and get your API key at <a href="https://gongozconcept.com" target="_blank">gongozconcept.com</a>.</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Gongozconcept API Key</label>
                                    <input type="text" name="gongozconcept_api_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('gongozconcept_api_key') }}" style="color: var(--text-main);">
                                </div>
                            </div>

                            <!-- Exchange Rate API -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">Exchange Rate API (Currency Sync)</h6>
                                    <small class="text-muted d-block mb-3">Sign up for a free API key at <a href="https://exchangerate-api.com" target="_blank">exchangerate-api.com</a>.</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label text-muted small fw-bold text-uppercase">ExchangeRate-API Key</label>
                                    <input type="text" name="exchangerate_api_key" class="form-control border-secondary bg-transparent py-2" value="{{ \App\Models\Setting::get('exchangerate_api_key') }}" style="color: var(--text-main);">
                                </div>
                            </div>

                            <!-- SMTP Settings -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">SMTP Mail Settings (Email Deliverability)</h6>
                                    <small class="text-muted d-block mb-3">Configure SMTP settings to ensure emails (verification, reset, notifications) are delivered reliably.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">SMTP Host</label>
                                    <input type="text" name="mail_host" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. smtp.gmail.com" value="{{ \App\Models\Setting::get('mail_host') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">SMTP Port</label>
                                    <input type="number" name="mail_port" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. 587" value="{{ \App\Models\Setting::get('mail_port') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Encryption</label>
                                    <select name="mail_encryption" class="form-select border-secondary bg-transparent py-2" style="color: var(--text-main);">
                                        <option value="tls" {{ \App\Models\Setting::get('mail_encryption') === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ \App\Models\Setting::get('mail_encryption') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="none" {{ \App\Models\Setting::get('mail_encryption') === 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Username</label>
                                    <input type="text" name="mail_username" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. your_email@gmail.com" value="{{ \App\Models\Setting::get('mail_username') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Password</label>
                                    <input type="password" name="mail_password" class="form-control border-secondary bg-transparent py-2" placeholder="••••••••" value="{{ \App\Models\Setting::get('mail_password') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Sender Email (From Address)</label>
                                    <input type="email" name="mail_from_address" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. noreply@yourdomain.com" value="{{ \App\Models\Setting::get('mail_from_address') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Sender Name (From Name)</label>
                                    <input type="text" name="mail_from_name" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. Linkocial" value="{{ \App\Models\Setting::get('mail_from_name') }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-12 mt-3 text-end">
                                    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#testEmailModal">
                                        <i class="fa-solid fa-paper-plane me-2"></i> Send Test Email
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 5: Fees & Markups -->
                        <div class="tab-pane fade" id="fees-panel" role="tabpanel">
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary small fw-bold text-uppercase mb-1 border-bottom pb-2" style="letter-spacing: 1px;">Service Markups (Percentage %)</h6>
                                    <small class="text-muted d-block mb-3">Define the percentage markup added to the base cost for each service. For example, setting <b>5</b> means a 5% markup is applied to the base price.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Logs Marketplace (%)</label>
                                    <input type="number" step="0.01" min="0" name="markup_logs_market" class="form-control border-secondary bg-transparent py-2 fw-bold" value="{{ \App\Models\Setting::get('markup_logs_market', 20) }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">VTU Airtime (%)</label>
                                    <input type="number" step="0.01" min="0" name="markup_vtu_airtime" class="form-control border-secondary bg-transparent py-2 fw-bold" value="{{ \App\Models\Setting::get('markup_vtu_airtime', 0) }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">VTU Data (%)</label>
                                    <input type="number" step="0.01" min="0" name="markup_vtu_data" class="form-control border-secondary bg-transparent py-2 fw-bold" value="{{ \App\Models\Setting::get('markup_vtu_data', 0) }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Cable TV (%)</label>
                                    <input type="number" step="0.01" min="0" name="markup_vtu_cable" class="form-control border-secondary bg-transparent py-2 fw-bold" value="{{ \App\Models\Setting::get('markup_vtu_cable', 0) }}" style="color: var(--text-main);">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Electricity (%)</label>
                                    <input type="number" step="0.01" min="0" name="markup_vtu_electricity" class="form-control border-secondary bg-transparent py-2 fw-bold" value="{{ \App\Models\Setting::get('markup_vtu_electricity', 0) }}" style="color: var(--text-main);">
                                </div>
                            </div>
                    </form>

                    <!-- Tab 6: Dashboard Slider Management -->
                    <div class="tab-pane fade" id="slider-panel" role="tabpanel">
                        <div class="row g-4 mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 flex-wrap gap-2">
                                    <div>
                                        <h5 class="fw-bold mb-1">
                                            <i class="fa-solid fa-images text-primary me-2"></i> Dashboard Banner Slider
                                        </h5>
                                        <p class="text-muted small mb-0">Manage promotional image slides displayed on the user dashboard.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sizing Notice (Standardized ~120px Height, Mobile Prioritised) -->
                            <div class="col-12">
                                <div class="p-3 rounded-4 d-flex align-items-start gap-3 border border-primary border-opacity-25" style="background: rgba(99, 102, 241, 0.05);">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                                        <i class="fa-solid fa-mobile-screen fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-main" style="font-size: 0.95rem;">Standardized Slide Height: ~120px (Mobile & Web Optimized)</div>
                                        <div class="text-muted small mt-1">
                                            To ensure a clean, seamless appearance across mobile screens and desktop browsers, the slider displays at a height of <strong>120px</strong> with responsive cover fit.
                                            Recommended image dimensions: <strong>600x120 px</strong> or <strong>720x120 px</strong> (aspect ratio ~5:1 or 6:1). Supported formats: JPG, PNG, WEBP, GIF (Max 5MB).
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add New Slide Form Card -->
                            <div class="col-lg-5">
                                <div class="premium-card p-4 border shadow-sm h-100" style="border-radius: 16px; border-color: var(--border-color) !important;">
                                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-plus-circle text-primary"></i> Add New Slide
                                    </h6>

                                    <form action="{{ route('hq.sliders.store') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Banner Image <span class="text-danger">*</span></label>
                                            <input type="file" name="image" id="sliderImageInput" class="form-control border-secondary bg-transparent py-2" accept="image/jpeg,image/png,image/webp,image/gif" required onchange="previewSliderImage(event)" style="color: var(--text-main);">
                                            <small class="text-muted d-block mt-1">Single size optimized for mobile and web (~120px height)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Target URL (Optional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent border-secondary text-muted"><i class="fa-solid fa-link"></i></span>
                                                <input type="text" name="target_url" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. /vtu/data, /logs, /wallet or https://..." style="color: var(--text-main);">
                                            </div>
                                            <small class="text-muted d-block mt-1">Users will be redirected to this link when they tap the slide.</small>
                                        </div>

                                        <!-- Live 120px Height Preview -->
                                        <div class="mb-4">
                                            <label class="form-label text-muted small fw-bold text-uppercase">Live 120px Preview</label>
                                            <div id="sliderPreviewContainer" class="border border-dashed rounded-3 d-flex align-items-center justify-content-center overflow-hidden position-relative" style="height: 120px; background: rgba(0,0,0,0.03); border-color: var(--border-color) !important;">
                                                <img id="sliderPreviewImg" src="" alt="Slide Preview" class="w-100 h-100 d-none" style="object-fit: cover; object-position: center;">
                                                <div id="sliderPreviewPlaceholder" class="text-center text-muted small p-2">
                                                    <i class="fa-solid fa-image fa-2x opacity-25 d-block mb-1"></i>
                                                    Select an image to preview 120px display
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-bold rounded-pill shadow-sm">
                                            <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload & Add Slide
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Current Slides List -->
                            <div class="col-lg-7">
                                <div class="premium-card p-4 border shadow-sm h-100" style="border-radius: 16px; border-color: var(--border-color) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-layer-group text-primary"></i> Current Slides
                                        </h6>
                                        <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold" style="font-size: 0.75rem;">
                                            {{ count($sliders ?? []) }} Slides
                                        </span>
                                    </div>

                                    @if(isset($sliders) && count($sliders) > 0)
                                        <div class="d-flex flex-column gap-3">
                                            @foreach($sliders as $slide)
                                            <div class="border rounded-4 p-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-3 overflow-hidden border border-secondary border-opacity-25 flex-shrink-0" style="width: 140px; height: 60px;">
                                                        <img src="{{ asset($slide->image_path) }}" alt="Slide" class="w-100 h-100" style="object-fit: cover; object-position: center;">
                                                    </div>
                                                    <div class="overflow-hidden">
                                                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Target Link</div>
                                                        @if($slide->target_url)
                                                            <a href="{{ $slide->target_url }}" target="_blank" class="text-primary text-decoration-none small fw-semibold text-truncate d-block" style="max-width: 250px;">
                                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> {{ $slide->target_url }}
                                                            </a>
                                                        @else
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size: 0.72rem;">No link (Display only)</span>
                                                        @endif
                                                        <div class="text-muted mt-1" style="font-size: 0.7rem;">Added: {{ $slide->created_at->format('M d, Y') }}</div>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end">
                                                    <form action="{{ route('hq.sliders.destroy', $slide->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this slide from the dashboard?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold" title="Delete Slide">
                                                            <i class="fa-solid fa-trash-can me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-5 text-muted bg-light bg-opacity-10 rounded-4">
                                            <i class="fa-solid fa-images fa-3x mb-3 opacity-25"></i>
                                            <h6 class="fw-bold">No slides uploaded yet</h6>
                                            <p class="small mb-0">Upload a banner image on the left to display it on the user dashboard.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>

        <!-- System Actions below Settings -->
        <div class="col-xl-6 col-md-12">
            <div class="premium-card p-4 shadow-sm bg-primary bg-opacity-10 border-0 mb-4 h-100">
                <h6 class="fw-bold text-primary mb-3">Platform Maintenance</h6>
                <p class="small text-muted mb-4">Running a critical update or changing prices? Use maintenance mode to prevent conflicts.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-primary border-0 bg-white shadow-sm text-start py-2 px-3 fw-bold rounded-pill" onclick="location.reload()">
                        <i class="fa-solid fa-arrows-rotate me-2"></i> Refresh Variables
                    </button>
                    <a href="{{ route('hq.activity-logs') }}" class="btn btn-outline-secondary border-0 bg-white shadow-sm text-start py-2 px-3 fw-bold text-decoration-none rounded-pill">
                        <i class="fa-solid fa-history me-2 text-primary"></i> Review System Audit
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-12">
            <div class="premium-card p-4 shadow-sm border-0 mb-4 h-100">
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

    function previewSliderImage(event) {
        const input = event.target;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('sliderPreviewImg');
                const placeholder = document.getElementById('sliderPreviewPlaceholder');
                if (img && placeholder) {
                    img.src = e.target.result;
                    img.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || window.location.hash.replace('#', '');
        if (activeTab) {
            const triggerEl = document.querySelector(`#settingsTabs button[data-bs-target="#${activeTab}-panel"]`);
            if (triggerEl) {
                const tabInstance = bootstrap.Tab.getOrCreateInstance(triggerEl);
                tabInstance.show();
            }
        }
    });
</script>
@endpush

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background-color: #ffffff; color: #2d3748;">
            <form action="{{ route('hq.settings.test-email') }}" method="POST">
                @csrf
                <div class="modal-header border-0 bg-light bg-opacity-50 p-4">
                    <h5 class="modal-title fw-bold" id="testEmailModalLabel">
                        <i class="fa-solid fa-envelope-circle-check text-primary me-2"></i> Test SMTP Configuration
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">Make sure you have clicked <strong>"Save Changes"</strong> to save your current SMTP credentials before sending the test email.</p>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Recipient Email Address</label>
                        <input type="email" name="test_email" class="form-control border-secondary bg-transparent py-2" placeholder="e.g. admin@yourdomain.com" required style="color: #2d3748;">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 bg-light bg-opacity-50">
                    <button type="button" class="btn btn-secondary px-4 py-2 rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2 rounded-pill fw-bold">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
