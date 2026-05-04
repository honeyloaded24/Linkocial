<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security Verification - {{ \App\Models\Setting::get('site_name', 'Linkocial') }}</title>

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

        .otp-input {
            letter-spacing: 0.5rem;
            font-size: 2.25rem;
            font-weight: 900;
            text-align: center;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            color: #6366f1;
            border-radius: 16px;
            padding: 1rem;
            font-family: 'Outfit', sans-serif;
        }

        .otp-input:focus {
            background: rgba(30, 41, 59, 0.8);
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
            outline: none;
            color: white;
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

        .btn-primary-custom:disabled {
            background: #334155;
            opacity: 0.6;
            cursor: not-allowed;
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
                <h2 class="fw-black text-white mb-2" style="font-family: 'Outfit', sans-serif;">Security Check</h2>
                <p class="text-muted opacity-75 fw-medium">Enter your 6-digit access code</p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 mb-4 fw-bold small p-3">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('two-factor.verify') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <input type="text" name="code" class="form-control otp-input w-100" maxlength="6" placeholder="000000" pattern="[0-9]*" inputmode="numeric" required autofocus autocomplete="one-time-code">
                </div>
                
                <button type="submit" class="btn btn-primary-custom w-100 py-3 mb-4 fw-bold d-flex align-items-center justify-content-center gap-2" id="submitBtn">
                    <span id="btnText">Verify Account</span>
                    <div class="spinner-border spinner-border-sm d-none" role="status" id="btnSpinner"></div>
                </button>
                
                <div class="text-center">
                    <p class="text-muted small mb-0">
                        Didn't get the code? 
                        <a href="{{ route('two-factor.resend') }}" class="text-white fw-bold text-decoration-none ms-1">Resend Code</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const input = document.querySelector('.otp-input');

            function checkForm() {
                btn.disabled = input.value.trim().length < 6;
            }

            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                checkForm();
            });

            form.addEventListener('submit', function() {
                btn.disabled = true;
                btnText.innerText = "Verifying...";
                btnSpinner.classList.remove('d-none');
            });

            checkForm();
        });
    </script>
</body>
</html>
