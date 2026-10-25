@extends('layouts.app')

@section('header_title', 'HQ: Currency Management')

@section('content')
<div class="row mb-4">
    <div class="col-lg-8 mx-auto">
        <div class="premium-card p-4 p-md-5 text-center">
            <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                <span class="fs-1 fw-bold">₦</span>
            </div>
            
            <h3 class="fw-bold mb-2">Single Currency Enforced: Nigerian Naira (NGN)</h3>
            <p class="text-muted fs-6 mb-4 mx-auto" style="max-width: 550px;">
                Linkocial operates exclusively on Nigerian Naira (NGN). Multi-currency switching, foreign currency additions, and dynamic exchange-rate calculations have been permanently decommissioned across all frontend and backend services.
            </p>

            <div class="p-3 bg-light bg-opacity-10 border rounded-4 text-start mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-check text-success"></i>
                    <span class="fw-bold">Active Platform Currency:</span> <code>NGN (₦)</code>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-check text-success"></i>
                    <span class="fw-bold">Dynamic FX Conversions:</span> <span class="badge bg-secondary">Disabled (1:1 Native NGN)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check text-success"></i>
                    <span class="fw-bold">Payment Gateway Currency:</span> <span class="badge bg-success">Korapay NGN Enforced</span>
                </div>
            </div>

            <a href="{{ route('hq.dashboard') }}" class="btn btn-primary-custom rounded-pill px-4 py-2">
                <i class="fa-solid fa-arrow-left me-2"></i> Return to HQ Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
