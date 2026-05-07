@extends('layouts.marketing')

@section('title', 'Download Linkocial App - World\'s #1 SMM Platform')
@section('meta_description', 'Experience the world\'s most powerful social media marketing infrastructure on your phone. Download Linkocial today.')

@push('styles')
<style>
    .hero-section {
        padding: 120px 0 80px;
        background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
    }

    .btn-store {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.1);
        background: #000;
        color: #fff;
    }

    .btn-store:hover {
        transform: translateY(-5px);
        border-color: var(--primary);
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        background: #000;
        color: #fff;
    }

    .phone-mockup {
        width: 300px;
        height: 600px;
        background: #000;
        border-radius: 3rem;
        border: 8px solid #1e293b;
        box-shadow: 0 50px 100px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .phone-inner {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    }

    .blur-circle {
        position: absolute;
        width: 300px;
        height: 300px;
        background: var(--primary);
        filter: blur(100px);
        opacity: 0.1;
        z-index: -1;
    }
</style>
@endpush

@section('content')
<!-- Hero Content -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-center text-lg-start">
                <div class="badge rounded-pill px-3 py-2 mb-4 fw-bold" style="background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.2);">
                    MOBILE APPS COMING SOON
                </div>
                <h1 class="display-3 fw-bold mb-4" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                    Master SMM <br> <span class="text-primary-custom">In Your Pocket.</span>
                </h1>
                <p class="fs-5 text-muted mb-5 pe-lg-5">
                    Experience the world's most powerful social media marketing infrastructure. Manage orders, top up your wallet, and scale your influence on the go.
                </p>

                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start mb-5">
                    <!-- Apple -->
                    <a href="#" class="btn btn-store btn-lg rounded-4 px-4 py-3 d-flex align-items-center gap-3 text-decoration-none">
                        <i class="fa-brands fa-apple fs-1"></i>
                        <div class="text-start">
                            <div class="small opacity-50" style="font-size: 0.7rem; line-height: 1;">Download on the</div>
                            <div class="fw-bold" style="font-size: 1.1rem; line-height: 1.2;">App Store</div>
                        </div>
                    </a>

                    <!-- Google -->
                    <a href="#" class="btn btn-store btn-lg rounded-4 px-4 py-3 d-flex align-items-center gap-3 text-decoration-none">
                        <i class="fa-brands fa-google-play fs-2 text-primary"></i>
                        <div class="text-start">
                            <div class="small opacity-50" style="font-size: 0.7rem; line-height: 1;">GET IT ON</div>
                            <div class="fw-bold" style="font-size: 1.1rem; line-height: 1.2;">Google Play</div>
                        </div>
                    </a>
                </div>

                <div class="d-flex align-items-center gap-4 text-muted justify-content-center justify-content-lg-start">
                    <div class="small"><i class="fa-solid fa-bolt-lightning text-warning me-1"></i> Instant Sync</div>
                    <div class="small"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Secure Storage</div>
                    <div class="small"><i class="fa-solid fa-circle-check text-success me-1"></i> v1.0 Preview</div>
                </div>
            </div>

            <div class="col-lg-5 d-flex justify-content-center position-relative">
                <div class="blur-circle"></div>
                <div class="phone-mockup">
                    <div class="phone-inner">
                        <div class="text-center p-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary mb-4 shadow-lg" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-bolt-lightning text-white fs-1"></i>
                            </div>
                            <h3 class="fw-bold text-white mb-2">Linkocial</h3>
                            <p class="text-muted small">Professional SMM App</p>
                            <div class="mt-4 pt-4 border-top border-secondary border-opacity-10">
                                <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
