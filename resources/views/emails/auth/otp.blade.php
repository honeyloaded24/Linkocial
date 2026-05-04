@extends('emails.layout', ['title' => 'Security Verification Code'])

@section('content')
    <h2>Hello {{ $name ?? 'there' }},</h2>
    <p>You are receiving this email because a login attempt was made on your Linkocial account with 2FA enabled.</p>
    
    <div style="background-color: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
        <span style="font-size: 32px; font-weight: 800; letter-spacing: 5px; color: #6366f1;">{{ $code }}</span>
    </div>

    <p>This code will expire in 10 minutes. If you did not attempt to log in, please secure your account immediately.</p>
    
    <div class="divider"></div>
    
    <p style="font-size: 13px; color: #94a3b8;">This is an automated security notification. Do not share this code with anyone, including Linkocial staff.</p>
@endsection
