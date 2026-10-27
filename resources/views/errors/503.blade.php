<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Service Under Maintenance | Linkocial</title>
    
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

        .error-code {
            font-family: 'Outfit', sans-serif;
            font-size: 6.5rem;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -2px;
        }

        .icon-circle {
            width: 84px;
            height: 84px;
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(99, 102, 241, 0.25);
            font-size: 2.2rem;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.2);
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
            color: #ffffff;
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
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-circle">
            <i class="fa-solid fa-wrench"></i>
        </div>
        
        <div class="error-code">503</div>
        
        <h2>Scheduled Maintenance In Progress</h2>
        
        <p class="description">
            We are performing essential system enhancements and optimizations. All services, orders, and balances are secure and will resume momentarily.
        </p>

        <div class="mb-4">
            <button onclick="window.location.reload()" class="btn-reload">
                <i class="fa-solid fa-arrows-rotate"></i> Check Status
            </button>
        </div>

        <div>
            <a href="{{ url('/contact') }}" class="btn-support">
                <i class="fa-solid fa-envelope"></i> Questions? Contact Support
            </a>
        </div>
    </div>
</body>
</html>
