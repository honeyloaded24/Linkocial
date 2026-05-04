@extends('emails.layout')

@section('content')
    <h2>Welcome to the elite club, {{ $name }}! 🚀</h2>
    <p>We're thrilled to have you on board. Linkocial is designed to help you scale your social presence with the most reliable SMM services in the market.</p>
    
    <p>Your account is now active and ready to go. You can start placing orders, managing your dashboard, and even earning through our affiliate program immediately.</p>
    
    <div class="button-wrapper">
        <a href="{{ route('dashboard') }}" class="button">Go to My Dashboard</a>
    </div>
    
    <div class="divider"></div>
    
    <p style="font-size: 14px;"><strong>Pro Tip:</strong> Check out our "Hot & Trending" services section to see what's currently delivering the best results for our top clients.</p>
    
    <p>If you have any questions, our support team is just a ticket away.</p>
    
    <p>Stay awesome,<br>The Linkocial Team</p>
@endsection
