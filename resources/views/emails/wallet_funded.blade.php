@extends('emails.layout')

@section('content')
    <h2>Wallet Funded! 💰</h2>
    <p>Great news! Your wallet has been successfully credited and your new balance is ready to use.</p>
    
    <div style="background-color: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 24px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding-bottom: 10px; color: #64748b; font-size: 14px;">Transaction ID:</td>
                <td style="padding-bottom: 10px; text-align: right; font-weight: 700;">#{{ $transaction_id }}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 10px; color: #64748b; font-size: 14px;">Payment Method:</td>
                <td style="padding-bottom: 10px; text-align: right; font-weight: 700;">{{ $method }}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 10px; color: #64748b; font-size: 14px;">Amount Credited:</td>
                <td style="padding-bottom: 10px; text-align: right; font-weight: 700; color: #10b981;">₦ {{ number_format($amount, 2) }}</td>
            </tr>
            @if(isset($bonus) && $bonus > 0)
            <tr>
                <td style="color: #64748b; font-size: 14px;">Promo Bonus (+):</td>
                <td style="text-align: right; font-weight: 700; color: #6366f1;">₦ {{ number_format($bonus, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    <p>You can start placing orders immediately using your current balance.</p>
    
    <div class="button-wrapper">
        <a href="{{ route('wallet.history') }}" class="button">View My History</a>
    </div>
    
    <p>Thank you for choosing {{ \App\Models\Setting::get('site_name', 'Linkocial') }}!</p>
    
    <p>Best regards,<br>The {{ \App\Models\Setting::get('site_name', 'Linkocial') }} Team</p>
@endsection
