<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $siteName . ' - Professional SMM Solutions')</title>
    <meta name="description" content="@yield('meta_description', 'Boost your social presence with ' . $siteName . '. High-quality followers, likes, and engagement.')">

    @php
        $favicon = \App\Models\Setting::get('site_favicon');
        $siteLogoLight = \App\Models\Setting::get('site_logo_light');
        $siteLogoDark = \App\Models\Setting::get('site_logo_dark');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/png" href="{{ $favicon }}">
    @endif

    <script>
        // Apply theme IMMEDIATELY to prevent flash of wrong colors
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --bg-body: #ffffff;
            --bg-soft: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            padding-top: 80px;
        }

        .premium-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-premium {
            background: var(--primary);
            color: white;
            border: none;
            font-weight: 700;
            border-radius: 12px;
            padding: 12px 24px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }
        .btn-premium:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .btn-outline-premium {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            font-weight: 700;
            border-radius: 12px;
            padding: 10px 24px;
            transition: all 0.3s;
        }
        .btn-outline-premium:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .bg-soft { background-color: var(--bg-soft); }
        .text-primary-custom { color: var(--primary); }
        .text-primary-soft { color: rgba(99, 102, 241, 0.7); }
        .text-gradient {
            background: linear-gradient(45deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
    @stack('scripts')
</body>
</html>
