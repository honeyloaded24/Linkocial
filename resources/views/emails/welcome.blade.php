@extends('emails.layout')

@php
    $hideTelegramBanner = true;
@endphp

@section('content')
    <h2>Welcome to the elite club, {{ $name }}! 🚀</h2>
    <p>We're thrilled to have you on board. Linkocial is designed to help you scale your social presence with the most reliable SMM services, virtual phone numbers, data bundles, and digital account marketplace in the market.</p>
    
    <p>Your account is now verified and ready to go. You can start placing orders, managing your dashboard, and funding your wallet immediately.</p>
    
    <div class="button-wrapper">
        <a href="{{ route('dashboard') }}" class="button">Go to My Dashboard</a>
    </div>

    <!-- Video Navigation Tutorial Link -->
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 20px 22px; margin: 26px 0;">
        <div style="font-size: 16px; font-weight: 700; color: #166534; margin-bottom: 6px;">
            📺 Watch: How to Navigate & Use Linkocial
        </div>
        <p style="margin: 0 0 12px 0; font-size: 14px; color: #15803d; line-height: 1.5;">
            Need a quick walkthrough on how to deposit, place orders, buy virtual numbers, and navigate your dashboard? Watch our official video guide:
        </p>
        <a href="https://t.me/Linkocial/25" target="_blank" style="display: inline-block; background-color: #16a34a; color: #ffffff !important; font-weight: 700; font-size: 13px; padding: 10px 22px; border-radius: 8px; text-decoration: none; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.25);">
            ▶️ Watch Video Tutorial (https://t.me/Linkocial/25)
        </a>
    </div>

    <!-- Official Telegram Channel Community Box -->
    <div style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 14px; padding: 22px 24px; margin: 26px 0;">
        <div style="font-size: 17px; font-weight: 800; color: #0369a1; margin-bottom: 8px;">
            Join the Official Linkocial Telegram Channel for:
        </div>
        
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 18px;">
            <tr><td style="padding: 5px 0; font-size: 14px; color: #334155;">🔥 <strong>Exclusive offers &amp; promotions</strong></td></tr>
            <tr><td style="padding: 5px 0; font-size: 14px; color: #334155;">📲 <strong>Virtual Number updates</strong></td></tr>
            <tr><td style="padding: 5px 0; font-size: 14px; color: #334155;">🚀 <strong>Social media growth deals</strong></td></tr>
            <tr><td style="padding: 5px 0; font-size: 14px; color: #334155;">🌐 <strong>Data offers</strong></td></tr>
            <tr><td style="padding: 5px 0; font-size: 14px; color: #334155;">🛍️ <strong>New marketplace products</strong></td></tr>
            <tr><td style="padding: 5px 0; font-size: 14px; color: #334155;">📢 <strong>Important service announcements</strong></td></tr>
        </table>

        <div style="text-align: center;">
            <a href="https://t.me/Linkocial" target="_blank" style="display: inline-block; background-color: #0088cc; color: #ffffff !important; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 25px; text-decoration: none; box-shadow: 0 4px 8px rgba(0, 136, 204, 0.3);">
                👉 Join Official Telegram Channel (https://t.me/Linkocial)
            </a>
        </div>
    </div>
    
    <div class="divider"></div>
    
    <p style="font-size: 14px;"><strong>Pro Tip:</strong> Check out our "Hot & Trending" services section to see what's currently delivering the best results for our top clients.</p>
    
    <p>If you have any questions, our support team is just a ticket away.</p>
    
    <p>Stay awesome,<br>The Linkocial Team</p>
@endsection
