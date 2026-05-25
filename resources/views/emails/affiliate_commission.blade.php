@extends('emails.layout')

@section('content')
    <h2 style="color: #14b8a6;">You just earned money! 💸</h2>
    <p>Boom! One of your referrals just completed a purchase, and your affiliate commission has been credited to your wallet.</p>
    
    <div style="text-align: center; padding: 30px; background-color: #f0fdfa; border-radius: 12px; margin-bottom: 24px; border: 1px dashed #14b8a6;">
        <span style="display: block; font-size: 14px; color: #14b8a6; font-weight: 700; text-uppercase; margin-bottom: 5px;">Commission Earned</span>
        <span style="font-size: 32px; font-weight: 800; color: #0f766e;">{{ money($amount) }}</span>
    </div>
    
    <p>Your affiliate link is working hard for you. Keep sharing it on social media, blogs, or with your friends to keep those commissions rolling in!</p>
    
    <div class="button-wrapper">
        <a href="{{ route('affiliate.index') }}" class="button" style="background-color: #14b8a6; box-shadow: 0 10px 15px -3px rgba(20, 184, 166, 0.3);">View Affiliate Stats</a>
    </div>
    
    <p>Keep up the great work!</p>
    
    <p>Cheers,<br>The Linkocial Growth Team</p>
@endsection
