<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - {{ \App\Models\Setting::get('site_name', 'Linkocial SMM') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .maintenance-card {
            background: #1e293b;
            padding: 3rem;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            margin: 0 auto 1.5rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-box">
            <i class="fa-solid fa-wrench fa-2x"></i>
        </div>
        <h2 class="fw-bold mb-3">Under Maintenance</h2>
        <p class="text-muted mb-4">We're currently performing some scheduled updates to improve your experience. We'll be back online shortly!</p>
        <div class="text-muted small">
            If you need urgent assistance, contact us at:<br>
            <span class="text-primary fw-bold">{{ \App\Models\Setting::get('contact_email', 'hello@linkocial.com') }}</span>
        </div>
    </div>
</body>
</html>
