<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email - {{ \App\Models\Setting::get('site_name', 'Linkocial') }}</title>

    @php
        $favicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/png" href="{{ $favicon }}">
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;900&display=swap" rel="stylesheet">
    
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

        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            padding: 1rem;
            border-radius: 16px;
            font-weight: 700;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .btn-primary-custom:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
            filter: brightness(1.1);
        }

        .fw-black { font-weight: 900; }
    </style>
</head>
<body>

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
                    @if($logoDark)
                        <img src="{{ $logoDark }}" alt="{{ $siteName }}" class="img-fluid" style="max-height: 35px;">
                    @else
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-custom rounded-circle shadow-lg"
                            style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-bolt-lightning text-white fs-4"></i>
                        </div>
                    @endif
                </div>
                <h2 class="fw-black text-white mb-2" style="font-family: 'Outfit', sans-serif;">Verify Email</h2>
                <p class="text-muted opacity-75 fw-medium">Check your inbox for a link</p>
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 mb-4 small p-3">
                    A new verification link has been sent to your email.
                </div>
            @endif

            <div class="text-center text-white mb-4">
                <p class="opacity-75 small">Before getting started, please verify your email address by clicking on the link we just emailed to you. Didn't receive it?</p>
            </div>

            <div class="d-flex flex-column gap-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary-custom w-100 d-flex align-items-center justify-content-center gap-2" id="submitBtn">
                        <span id="btnText">Resend Email</span>
                        <div class="spinner-border spinner-border-sm d-none" role="status" id="btnSpinner"></div>
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link text-white opacity-50 text-decoration-none w-100 small fw-bold">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('verification.send') }}"]');
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            if (form) {
                form.addEventListener('submit', function() {
                    btn.disabled = true;
                    btnText.innerText = "Sending...";
                    btnSpinner.classList.remove('d-none');
                });
            }
        });
    </script>
</body>
</html>
