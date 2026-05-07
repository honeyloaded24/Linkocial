@extends('layouts.app')

@section('header_title', $title)

@section('content')
<div class="row justify-content-center align-items-center min-vh-75">
    <div class="col-12 col-md-8 col-lg-6 text-center">
        <div class="premium-card p-5 border-0 shadow-lg position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(20, 184, 166, 0.05) 100%);">
            <!-- Animated Background Circles -->
            <div class="position-absolute" style="width: 200px; height: 200px; background: var(--primary-color); opacity: 0.03; border-radius: 50%; top: -100px; right: -100px;"></div>
            <div class="position-absolute" style="width: 150px; height: 150px; background: var(--accent-color); opacity: 0.03; border-radius: 50%; bottom: -75px; left: -75px;"></div>

            <div class="mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-custom rounded-circle p-4 text-white shadow-lg mb-4" style="width: 100px; height: 100px;">
                    <i class="fa-solid {{ $icon }} fs-1"></i>
                </div>
            </div>

            <h2 class="fw-bold mb-3" style="font-family: var(--heading-font);">{{ $title }}</h2>
            <div class="badge bg-primary-custom px-3 py-2 rounded-pill mb-5 text-uppercase fw-bold" style="letter-spacing: 1.5px; font-size: 0.8rem;">Coming Soon</div>
            
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ route('dashboard') }}" class="btn btn-lg bg-light bg-opacity-10 text-main rounded-4 fw-bold px-4 border-0 shadow-sm">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
