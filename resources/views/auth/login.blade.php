<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ \App\Models\Setting::get('site_name', 'Linkocial') }}</title>

    @php
        $favicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/png" href="{{ $favicon }}">
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <style>
        body {
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .bg-glow {
            position: fixed;
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
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 3.5rem;
            border-radius: 32px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.7);
        }

        .form-control {
            background-color: rgba(15, 23, 42, 0.6);
            border: 2px solid rgba(255, 255, 255, 0.05);
            color: #ffffff;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.9);
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            color: white !important;
            padding: 1.1rem;
            border-radius: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.9rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            opacity: 1 !important;
            cursor: pointer;
        }

        .btn-primary-custom:hover:not(:disabled) {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 30px -10px rgba(99, 102, 241, 0.6);
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        }

        .btn-primary-custom:disabled {
            background: #334155 !important;
            color: #94a3b8 !important;
            transform: none !important;
            box-shadow: none !important;
            cursor: not-allowed;
            opacity: 0.8 !important;
        }
    </style>
</head>

<body>

    <div class="bg-glow">
        <div class="glow-1"></div>
        <div class="glow-2"></div>
        <div class="glow-3"></div>
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
                <h2 class="fw-black text-white mb-2" style="font-family: 'Outfit', sans-serif;">Welcome Back</h2>
                <p class="text-muted opacity-75 fw-medium">Access your growth center</p>
            </div>

            <form action="{{ route('login') }}" method="POST">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 mb-4 small p-3">
                        <ul class="mb-0 fw-bold list-unstyled text-center">
                            @foreach ($errors->all() as $error)
                                <li><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mb-4">
                    <label class="form-label text-muted fw-bold small text-uppercase mb-2 ps-1"
                        style="letter-spacing: 1.5px;">Email or Username</label>
                    <input type="text" name="login" class="form-control" placeholder="Email or Username" required
                        autofocus>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2 ps-1">
                        <label class="form-label text-muted fw-bold small text-uppercase"
                            style="letter-spacing: 1.5px;">Password</label>
                        <a href="{{ route('password.request') }}"
                            class="text-decoration-none small fw-black text-primary">Lost Access?</a>
                    </div>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="mb-4 form-check ps-1">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember"
                        style="margin-left: 0;">
                    <label class="form-check-label text-white small fw-bold ms-2" for="remember">Keep me logged
                        in for 5 days</label>
                </div>

                <div class="mb-4">
                    <div class="cf-turnstile" data-sitekey="{{ env('TURNSTILE_SITE_KEY') }}" data-theme="dark"></div>
                </div>

                <button type="submit"
                    class="btn btn-primary-custom w-100 mb-4 d-flex align-items-center justify-content-center gap-2"
                    id="loginBtn" disabled>
                    <span id="btnText">Sign In</span>
                    <div class="spinner-border spinner-border-sm d-none" role="status" id="btnSpinner"></div>
                </button>

                <p class="text-center text-muted mb-0 small">New to {{ $siteName }}? <a href="{{ route('register') }}"
                        class="text-decoration-none fw-bold" style="color: var(--primary-color);">Create an account</a>
                </p>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const inputs = form.querySelectorAll('input[required]');

            // Define High-Contrast Colors
            const colorActive = "#6366f1"; // Vibrant Indigo
            const colorInactive = "#334155"; // Dark Slate
            const textActive = "#ffffff";
            const textInactive = "#94a3b8";

            function checkForm() {
                let allFilled = true;
                inputs.forEach(input => {
                    if (!input.value.trim()) allFilled = false;
                });

                if (allFilled) {
                    btn.disabled = false;
                    btn.style.backgroundColor = colorActive;
                    btn.style.borderColor = colorActive;
                    btn.style.color = textActive;
                    btn.style.opacity = "1";
                    btn.style.boxShadow = "0 10px 15px -3px rgba(99, 102, 241, 0.4)";
                } else {
                    btn.disabled = true;
                    btn.style.backgroundColor = colorInactive;
                    btn.style.borderColor = colorInactive;
                    btn.style.color = textInactive;
                    btn.style.opacity = "0.6";
                    btn.style.boxShadow = "none";
                }
            }

            inputs.forEach(input => {
                input.addEventListener('input', checkForm);
            });

            form.addEventListener('submit', function () {
                btn.disabled = true;
                btnText.innerText = "Signing In...";
                btnSpinner.classList.remove('d-none');
                btn.style.backgroundColor = "#4f46e5"; // Darker indigo on click
            });

            // Initial check
            checkForm();
        });
    </script>

</body>

</html>