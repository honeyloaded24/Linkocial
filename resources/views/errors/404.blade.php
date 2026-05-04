@extends('layouts.app')

@section('header_title', '404 - Page Not Found')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 text-center">
            <div class="mb-5">
                <h1 class="display-1 fw-bold text-primary-custom" style="font-size: 10rem; opacity: 0.2;">404</h1>
                <div class="mt-n5 position-relative" style="margin-top: -80px;">
                    <img src="https://cdn-icons-png.flaticon.com/512/2748/2748558.png" alt="Not Found" width="200" class="img-fluid mb-4">
                    <h2 class="fw-bold text-dark">Lost in the sauce?</h2>
                    <p class="text-muted fs-5 mb-5">The page you're looking for has moved, expired, or never existed. Let's get you back to safety.</p>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary-custom px-5 py-3 rounded-pill fw-bold shadow-sm">
                            <i class="fa-solid fa-house me-2"></i> Take Me Home
                        </a>
                        <a href="{{ config('app.url') }}/support" class="btn btn-outline-secondary px-5 py-3 rounded-pill fw-bold">
                            <i class="fa-solid fa-headset me-2"></i> Get Help
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
