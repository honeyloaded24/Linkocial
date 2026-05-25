@extends('emails.layout')

@section('content')
    <h2>Order Confirmed! ✅</h2>
    <p>Great news! Your order has been successfully placed and is now being processed.</p>
    
    <div style="background-color: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 24px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding-bottom: 10px; color: #64748b; font-size: 14px;">Order ID:</td>
                <td style="padding-bottom: 10px; text-align: right; font-weight: 700;">#{{ $order_id }}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 10px; color: #64748b; font-size: 14px;">Service:</td>
                <td style="padding-bottom: 10px; text-align: right; font-weight: 700;">{{ $service_name }}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 10px; color: #64748b; font-size: 14px;">Quantity:</td>
                <td style="padding-bottom: 10px; text-align: right; font-weight: 700;">{{ number_format($quantity) }}</td>
            </tr>
            <tr>
                <td style="color: #64748b; font-size: 14px;">Charge:</td>
                <td style="text-align: right; font-weight: 700; color: #6366f1;">{{ money($amount) }}</td>
            </tr>
        </table>
    </div>
    
    <p>You can track the real-time status of this order directly from your order history page.</p>
    
    <div class="button-wrapper">
        <a href="{{ route('orders.index') }}" class="button">Track My Order</a>
    </div>
    
    <p>Thank you for choosing Linkocial for your growth!</p>
    
    <p>Best regards,<br>The Linkocial Team</p>
@endsection
