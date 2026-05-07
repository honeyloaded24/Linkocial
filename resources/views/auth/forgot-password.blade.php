@extends('layouts.marketing')

@section('title', 'Forgot Password - ' . \App\Models\Setting::get('site_name', 'Linkocial'))

@push('styles')
<style>
    .auth-page-wrapper {
        background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
        background-attachment: fixed;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow-x: hidden;
        padding-top: 100px; /* Space for fixed navbar */
        padding-bottom: 80px;
    }

    .bg-glow {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
        background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 80%);
    }

    .glow-1 {
        position: absolute;
        top: -10%;
        right: -10%;
        width: 70%;
        height: 70%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        filter: blur(80px);
    }

    .glow-2 {
        position: absolute;
        bottom: -15%;
        left: -5%;
        width: 60%;
        height: 60%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
        filter: blur(100px);
    }

    .login-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
        position: relative;
        z-index: 1;
    }

    .login-card {
        width: 100%;
        max-width: 450px;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 32px;
        padding: 3rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .form-control {
        background: rgba(30, 41, 59, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.05);
        color: white !important;
        padding: 0.85rem 1.25rem;
        border-radius: 16px;
        font-weight: 500;
    }

    .form-control:focus {
        background: rgba(30, 41, 59, 0.8);
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        color: white !important;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        border: none;
        padding: 1rem;
        border-radius: 16px;
        font-weight: 700;
        color: white !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    .btn-primary-custom:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
        filter: brightness(1.1);
    }

    .btn-primary-custom:disabled {
        background: #334155 !important;
        opacity: 0.6;
        cursor: not-allowed;
    }

    .fw-black { font-weight: 900; }

    /* Force light text for auth labels and placeholders */
    .login-card .form-label, .login-card .text-muted {
        color: rgba(255, 255, 255, 0.7) !important;
        font-weight: 700;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5) !important;
    }
</style>
@endpush

@section('content')
<div class="auth-page-wrapper">
    <div class="bg-glow">
        <div class="glow-1"></div>
        <div class="glow-2"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="text-center mb-5">
                @php
                    $logoDark = \App\Models\Setting::get('site_logo_dark');
                    $siteName = \App\Models\Setting::get('site_name', 'Linkocial');
                @endphp
                <div class="mb-4">
                    <a href="{{ route('landing') }}">
                        @if($logoDark)
                            <img src="{{ $logoDark }}" alt="{{ $siteName }}" class="img-fluid" style="max-height: 35px;">
                        @else
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary-custom rounded-circle shadow-lg"
                                style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-bolt-lightning text-white fs-4"></i>
                            </div>
                        @endif
                    </a>
                </div>
                <h2 class="fw-black text-white mb-2" style="font-family: 'Outfit', sans-serif;">Reset Password</h2>
                <p class="text-muted opacity-75 fw-medium">We'll help you get back in</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 mb-4 small p-3">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 mb-4 small p-3">
                    <ul class="mb-0 fw-bold">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label text-muted fw-bold small text-uppercase mb-2 ps-1"
                        style="letter-spacing: 1.5px;">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" value="{{ old('email') }}" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary-custom w-100 mb-4 d-flex align-items-center justify-content-center gap-2" id="submitBtn">
                    <span id="btnText">Send Reset Link</span>
                    <div class="spinner-border spinner-border-sm d-none" role="status" id="btnSpinner"></div>
                </button>
                
                <p class="text-center text-muted mb-0 small">
                    Remembered? <a href="{{ route('login') }}" class="text-white fw-bold text-decoration-none ms-1">Back to login</a>
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const inputs = form.querySelectorAll('input[required]');

        function checkForm() {
            let allFilled = true;
            inputs.forEach(input => {
                if (!input.value.trim()) allFilled = false;
            });
            btn.disabled = !allFilled;
        }

        inputs.forEach(input => {
            input.addEventListener('input', checkForm);
        });

        form.addEventListener('submit', function() {
            btn.disabled = true;
            btnText.innerText = "Sending...";
            btnSpinner.classList.remove('d-none');
        });

        checkForm();
    });
</script>
@endpush

