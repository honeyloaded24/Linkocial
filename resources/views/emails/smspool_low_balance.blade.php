<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f8f9fa; padding: 20px; border: 1px solid #e9ecef; border-top: none; border-radius: 0 0 5px 5px; }
        .balance { font-size: 24px; font-weight: bold; color: #dc3545; margin: 10px 0; }
        .button { display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🚨 SMSPool Balance Alert 🚨</h2>
        </div>
        <div class="content">
            <p>Hello Admin,</p>
            <p>Your SMSPool API balance has dropped below the minimum threshold ($1.00).</p>
            
            <div class="balance">
                Current Balance: ${{ number_format($balance, 2) }}
            </div>
            
            <p>If your balance reaches $0, your users will receive "Service Unavailable" errors when trying to buy Virtual Numbers.</p>
            
            <a href="https://www.smspool.net/my/deposit" class="button">Top Up SMSPool Now</a>
            
            <p style="margin-top: 25px; font-size: 12px; color: #6c757d;">
                This is an automated system alert from {{ config('app.name') }}.
            </p>
        </div>
    </div>
</body>
</html>
