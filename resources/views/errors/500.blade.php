@extends('layouts.app')

@section('header_title', '500 - Server Error')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 text-center">
            <div class="mb-5">
                <h1 class="display-1 fw-bold text-danger" style="font-size: 10rem; opacity: 0.1;">500</h1>
                <div class="mt-n5 position-relative" style="margin-top: -80px;">
                    <img src="https://cdn-icons-png.flaticon.com/512/595/595067.png" alt="Server Error" width="200" class="img-fluid mb-4">
                    <h2 class="fw-bold text-dark">Whoops! Houston, we have a problem.</h2>
                    <p class="text-muted fs-5 mb-5">Something went wrong on our end. Our engineers have been alerted and are currently looking into it.</p>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <button onclick="location.reload()" class="btn btn-primary-custom px-5 py-3 rounded-pill fw-bold shadow-sm">
                            <i class="fa-solid fa-arrows-rotate me-2"></i> Try Again
                        </a>
                        <a href="{{ config('app.url') }}/support" class="btn btn-outline-danger px-5 py-3 rounded-pill fw-bold">
                            <i class="fa-solid fa-bug me-2"></i> Report Bug
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
