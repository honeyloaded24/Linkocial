<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notification' }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f1f5f9;
            padding-bottom: 40px;
        }
        .main {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-spacing: 0;
            font-family: sans-serif;
            color: #1e293b;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            background-color: #6366f1;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 40px 30px;
            line-height: 1.6;
        }
        .content h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0;
        }
        .content p {
            font-size: 16px;
            color: #475569;
            margin-bottom: 24px;
        }
        .button-wrapper {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            background-color: #6366f1;
            color: #ffffff !important;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #94a3b8;
        }
        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            .content { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <center>
            <table class="main" width="100%">
                <tr>
                    <td class="header">
                        <h1>Linkocial<span style="color: #a5b4fc;">.</span></h1>
                    </td>
                </tr>
                <tr>
                    <td class="content">
                        @yield('content')
                    </td>
                </tr>
                @if(empty($hideTelegramBanner))
                <tr>
                    <td style="padding: 0 30px 20px 30px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #0088cc 0%, #0077b5 100%); border-radius: 14px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 136, 204, 0.2);">
                            <tr>
                                <td style="padding: 22px 24px; text-align: center; color: #ffffff;">
                                    <div style="font-size: 16px; font-weight: 800; margin-bottom: 6px; letter-spacing: 0.2px;">
                                        📢 Join Our Official Telegram Channel
                                    </div>
                                    <div style="font-size: 13px; color: #e0f2fe; margin-bottom: 14px; line-height: 1.4;">
                                        Get instant updates on flash promos, virtual numbers, data discounts &amp; service alerts!
                                    </div>
                                    <a href="https://t.me/Linkocial" target="_blank" style="display: inline-block; background-color: #ffffff; color: #0088cc !important; font-weight: 700; font-size: 13px; padding: 10px 26px; border-radius: 25px; text-decoration: none; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                        👉 Join @Linkocial on Telegram
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="footer">
                        <p>&copy; {{ date('Y') }} Linkocial SMM Platform. All rights reserved.</p>
                        <p>You received this email because you are a registered member of Linkocial.</p>
                        <div style="margin-top: 10px;">
                            <a href="{{ config('app.url') }}" style="color: #6366f1; text-decoration: none; margin: 0 8px;">Website</a> &bull;
                            <a href="https://t.me/Linkocial" style="color: #0088cc; text-decoration: none; margin: 0 8px; font-weight: 600;">Telegram</a> &bull;
                            <a href="{{ config('app.url') }}/tickets" style="color: #6366f1; text-decoration: none; margin: 0 8px;">Support</a>
                        </div>
                    </td>
                </tr>
            </table>
        </center>
    </div>
</body>
</html>
