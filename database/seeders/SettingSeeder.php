<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => 'Linkocial SMM',
            'site_description' => 'Premium Social Media Marketing Services',
            'contact_email' => 'hello@linkocial.com',
            'maintenance_mode' => '0',
            'min_withdrawal' => '1000',
            'referral_commission' => '5', // 5%
            'usd_to_ngn_rate' => '1500', // default exchange rate
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::set($key, $value);
        }
    }
}
