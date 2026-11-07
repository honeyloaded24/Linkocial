@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('partials.service_ticker', ['service' => 'smm'])
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0" style="font-family: var(--heading-font); letter-spacing: -0.5px;">All Services</h3>
    </div>

    <!-- Bills & Top-up -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h5 class="fw-bold mb-0">Bills & Top-up</h5>
            <span class="text-muted small">4 features</span>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <!-- Airtime -->
            <div class="col">
                <a href="{{ route('vtu.airtime') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-mobile-screen fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Airtime</h6>
                                <p class="text-muted small mb-0 lh-sm">Recharge Nigerian mobile numbers instantly.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Mobile Data -->
            <div class="col">
                <a href="{{ route('vtu.data') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-wifi fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Mobile Data</h6>
                                <p class="text-muted small mb-0 lh-sm">Purchase affordable internet data bundles.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Cable TV -->
            <div class="col">
                <a href="{{ route('vtu.cable') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-tv fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Cable TV</h6>
                                <p class="text-muted small mb-0 lh-sm">Renew supported television subscriptions.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Electricity -->
            <div class="col">
                <a href="{{ route('vtu.electricity') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-lightbulb fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Electricity</h6>
                                <p class="text-muted small mb-0 lh-sm">Pay electricity bills and receive your token.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Digital Services -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h5 class="fw-bold mb-0">Digital Services</h5>
            <span class="text-muted small">6 features</span>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <!-- AI Marketplace -->
            <div class="col">
                <a href="{{ route('ai.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3" style="background: rgba(79, 70, 229, 0.12); color: #4f46e5; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-wand-magic-sparkles fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold mb-0">AI Marketplace</h6>
                                    <span class="badge bg-primary" style="font-size: 0.65rem;">NEW</span>
                                </div>
                                <p class="text-muted small mb-0 lh-sm">Buy ChatGPT Plus, Gemini Pro, CapCut Pro, Canva Pro & AI tools.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- SMS Verification -->
            <div class="col">
                <a href="{{ route('services.virtual-numbers') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3" style="background: rgba(236, 72, 153, 0.1); color: #ec4899; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-comment-sms fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">SMS Verification</h6>
                                <p class="text-muted small mb-0 lh-sm">Get virtual numbers for supported services.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Social Boost -->
            <div class="col">
                <a href="{{ route('orders.create') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-bullhorn fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Social Boost</h6>
                                <p class="text-muted small mb-0 lh-sm">Promote and grow your social media presence.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Logs & Accounts Marketplace -->
            <div class="col">
                <a href="{{ route('logs.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-box-open fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold mb-0">Logs & Accounts</h6>
                                    <span class="badge bg-danger" style="font-size: 0.65rem;">HOT</span>
                                </div>
                                <p class="text-muted small mb-0 lh-sm">Buy verified accounts, software licenses & digital logs.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Promote Brand -->
            <div class="col">
                <a href="{{ route('services.promote-brand') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-bullseye fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold mb-0">Promote Brand</h6>
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary" style="font-size: 0.65rem;">Soon</span>
                                </div>
                                <p class="text-muted small mb-0 lh-sm">Promote your brand with a dashboard banner.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Residential Proxy -->
            <div class="col">
                <a href="{{ route('services.residential-proxy') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-shield-halved fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold mb-0">Residential Proxy</h6>
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary" style="font-size: 0.65rem;">Soon</span>
                                </div>
                                <p class="text-muted small mb-0 lh-sm">Purchase secure residential proxy packages.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Your Account -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h5 class="fw-bold mb-0">Your Account</h5>
            <span class="text-muted small">3 features</span>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <!-- Order History -->
            <div class="col">
                <a href="{{ route('orders.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-receipt fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Order History</h6>
                                <p class="text-muted small mb-0 lh-sm">Review your marketplace purchases and orders.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Developer API -->
            <div class="col">
                <a href="{{ route('api.docs') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-code fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Developer API</h6>
                                <p class="text-muted small mb-0 lh-sm">Manage API access for supported services.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Profile & Security -->
            <div class="col">
                <a href="{{ route('profile.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-hover">
                        <div class="card-body p-4 d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-user-shield fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Profile & Security</h6>
                                <p class="text-muted small mb-0 lh-sm">Update your profile and account security.</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small mt-1"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
