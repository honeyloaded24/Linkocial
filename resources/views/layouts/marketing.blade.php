<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteName . ' - Professional SMM Solutions')</title>
    @include('partials.tracking')

    <meta name="description" content="@yield('meta_description', 'Boost your social presence with ' . $siteName . '. High-quality followers, likes, and engagement.')">
    <link rel="canonical" href="{{ request()->url() }}">

    @php
        $favicon = \App\Models\Setting::get('site_favicon');
        $siteLogoLight = \App\Models\Setting::get('site_logo_light');
        $siteLogoDark = \App\Models\Setting::get('site_logo_dark');
        $ogImage = \App\Models\Setting::get('site_og_image', asset('images/og-share-default.png'));
    @endphp

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', $siteName)">
    <meta property="og:description" content="@yield('meta_description', 'Professional SMM solutions for your brand.')">
    <meta property="og:image" content="{{ $ogImage }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', $siteName)">
    <meta name="twitter:description" content="@yield('meta_description', 'Professional SMM solutions for your brand.')">
    <meta name="twitter:image" content="{{ $ogImage }}">
    @if($favicon)
        <link rel="icon" type="image/png" href="{{ $favicon }}">
    @endif

    <script>
        // Force light theme on marketing pages to keep visuals consistent
        (function() {
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.setAttribute('data-bs-theme', 'light');
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #14b8a6;
            --bg-body: #ffffff;
            --bg-soft: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border: #e2e8f0;
            --card-shadow: 0 10px 30px -10px rgba(0,0,0,0.08);
        }


        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
        }

        .premium-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 24px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .premium-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .btn-premium {
            background: var(--primary);
            color: white;
            border: none;
            font-weight: 700;
            border-radius: 12px;
            padding: 12px 24px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }
        .btn-premium:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.4);
        }

        .btn-outline-premium {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            font-weight: 700;
            border-radius: 12px;
            padding: 10px 24px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .btn-outline-premium:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.15);
        }

        .bg-soft { background-color: var(--bg-soft); }
        .text-primary-custom { color: var(--primary); }
        .text-primary-soft { color: rgba(99, 102, 241, 0.7); }
        .text-gradient {
            background: linear-gradient(45deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Micro-animations */
        @keyframes pulse-soft {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .pulse-hover:hover i {
            animation: pulse-soft 0.6s infinite;
        }

        /* Logo Theme Switching */
        .logo-dark { display: none !important; }
        .logo-light { display: block !important; }

        [data-theme="dark"] .logo-dark { display: block !important; }
        [data-theme="dark"] .logo-light { display: none !important; }

        /* Blog Typography */
        .blog-content h2, .blog-content h3 {
            color: var(--primary) !important;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
    </style>
    @stack('styles')
</head>
<body>

    @include('partials.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 1000,
                once: true,
                offset: 100,
                easing: 'ease-out-cubic'
            });
        });
    </script>
    @stack('scripts')

    @include('partials.telegram-sticky')
</body>
</html>
