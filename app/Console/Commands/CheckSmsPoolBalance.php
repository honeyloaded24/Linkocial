<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsPoolService;
use App\Mail\SmsPoolLowBalanceAlert;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CheckSmsPoolBalance extends Command
{
    protected $signature = 'smspool:check-balance';
    protected $description = 'Check SMSPool API balance and alert admins if below threshold';

    public function handle(SmsPoolService $smspool)
    {
        try {
            $response = $smspool->getBalance();
            
            // The SMSPool getBalance endpoint typically returns ['balance' => "12.34"]
            $balance = isset($response['balance']) ? (float)$response['balance'] : null;

            if ($balance === null) {
                $this->error('Could not retrieve balance from SMSPool API.');
                return;
            }

            $this->info("Current SMSPool Balance: $" . number_format($balance, 2));

            // Threshold: $1.00
            if ($balance < 1.00) {
                $this->warn('Balance is below $1.00! Sending alerts...');
                
                // Find all admin users to notify
                $admins = User::where('is_admin', true)->get();
                
                foreach ($admins as $admin) {
                    if ($admin->email) {
                        Mail::to($admin->email)->send(new SmsPoolLowBalanceAlert($balance));
                    }
                }
                
                $this->info('Alerts sent successfully.');
            } else {
                $this->info('Balance is healthy.');
            }

        } catch (\Exception $e) {
            Log::error('CheckSmsPoolBalance failed', ['error' => $e->getMessage()]);
            $this->error('Failed to check balance: ' . $e->getMessage());
        }
    }
}
