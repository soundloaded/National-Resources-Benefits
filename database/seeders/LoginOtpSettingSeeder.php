<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoginOtpSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Setting::firstOrCreate(
            ['key' => 'login_otp_enabled'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'security',
            ]
        );
    }
}
