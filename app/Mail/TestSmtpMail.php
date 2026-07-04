<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('SMTP Connection Test Success! 🎉')
                    ->html('
                        <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; rounded-lg: 8px;">
                            <h2 style="color: #3b82f6; margin-bottom: 10px;">Hello Admin,</h2>
                            <p style="color: #4a5568; line-height: 1.6; font-size: 16px;">
                                This is a test email sent from your platform to verify that your SMTP server configuration is working correctly.
                            </p>
                            <div style="background-color: #f7fafc; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #10b981;">
                                <strong style="color: #2d3748;">Status:</strong> <span style="color: #10b981; font-weight: bold;">Connection Successful</span><br>
                                <strong style="color: #2d3748;">Timestamp:</strong> ' . now()->toDayDateTimeString() . '
                            </div>
                            <p style="color: #718096; font-size: 14px;">
                                If you received this, your email deliverability is fully active! You can now safely close the setup panel.
                            </p>
                        </div>
                    ');
    }
}
