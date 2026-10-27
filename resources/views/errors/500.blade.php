<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | Linkocial</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: #111827;
            --border-card: rgba(255, 255, 255, 0.08);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
        }

        .error-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 28px;
            padding: 3.5rem 2.5rem;
            max-width: 620px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7);
            position: relative;
            overflow: hidden;
        }

        .error-card::before {
            content: '';
            position: absolute;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.15) 0%, rgba(99, 102, 241, 0.05) 50%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .error-code {
            font-family: 'Outfit', sans-serif;
            font-size: 6.5rem;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -2px;
        }

        .icon-circle {
            width: 84px;
            height: 84px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
            font-size: 2.2rem;
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.2);
        }

        h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.85rem;
            color: var(--text-main);
            margin-bottom: 1rem;
        }

        p.description {
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn-reload {
            background: var(--primary-gradient);
            color: #ffffff;
            border: none;
            padding: 0.85rem 2rem;
            font-weight: 700;
            border-radius: 50rem;
            transition: all 0.25s ease;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-reload:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.5);
            color: #ffffff;
        }

        .btn-home {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border-card);
            padding: 0.85rem 1.75rem;
            font-weight: 600;
            border-radius: 50rem;
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-home:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateY(-2px);
        }

        .btn-support {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .btn-support:hover {
            color: #6366f1;
        }

        .debug-box {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 14px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.85rem;
            color: #fca5a5;
            text-align: left;
            margin-top: 2rem;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-circle">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        
        <div class="error-code">500</div>
        
        <h2>Server Encountered an Error</h2>
        
        <p class="description">
            Something unexpected occurred on our end while processing your request. Our automated telemetry has recorded the issue and our team is actively investigating.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
            <button onclick="window.location.reload()" class="btn-reload">
                <i class="fa-solid fa-arrows-rotate"></i> Try Again
            </button>
            <a href="{{ url('/') }}" class="btn-home">
                <i class="fa-solid fa-house"></i> Return Home
            </a>
        </div>

        <div>
            <a href="{{ url('/contact') }}" class="btn-support">
                <i class="fa-solid fa-headset"></i> Need assistance? Contact Support
            </a>
        </div>

        @if(config('app.debug') && isset($exception) && $exception instanceof \Throwable)
            <div class="debug-box mt-4">
                <div class="fw-bold mb-1 text-danger"><i class="fa-solid fa-bug me-1"></i> Debug Info (App Debug is Active):</div>
                <div>{{ $exception->getMessage() }}</div>
                <small class="text-white-50 d-block mt-1">in {{ $exception->getFile() }}:{{ $exception->getLine() }}</small>
            </div>
        @endif
    </div>
</body>
</html>
