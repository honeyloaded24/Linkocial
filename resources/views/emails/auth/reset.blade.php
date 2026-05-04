@extends('emails.layout', ['title' => 'Reset Your Password'])

@section('content')
    <h2>Hello {{ $name ?? 'there' }},</h2>
    <p>You are receiving this email because we received a password reset request for your Linkocial account.</p>
    
    <div class="button-wrapper">
        <a href="{{ $url }}" class="button">Reset Password</a>
    </div>

    <p>This password reset link will expire in 60 minutes. If you did not request a password reset, no further action is required.</p>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px; color: #94a3b8;">If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser: <br>
    <a href="{{ $url }}" style="color: #6366f1;">{{ $url }}</a></p>
@endsection
