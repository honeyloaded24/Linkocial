@extends('emails.layout', ['title' => 'Verify Your Email'])

@section('content')
    <h2>Hello {{ $name ?? 'there' }},</h2>
    <p>Welcome to Linkocial! We're excited to have you on board. To start using our premium SMM services, we just need to verify your email address.</p>
    
    <div class="button-wrapper">
        <a href="{{ $url }}" class="button">Verify Email Address</a>
    </div>

    <p>If you did not create an account, no further action is required.</p>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px; color: #94a3b8;">If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser: <br>
    <a href="{{ $url }}" style="color: #6366f1;">{{ $url }}</a></p>
@endsection
