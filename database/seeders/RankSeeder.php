<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ranks = [
            [
                'name' => 'Bronze',
                'description' => 'Starting rank for all new members',
                'icon' => null,
                'min_transaction_volume' => 0,
                'reward' => 0,
                'max_wallets' => 1,
                'max_referral_level' => 1,
                'allowed_transaction_types' => ['deposit', 'send_money'],
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Silver',
                'description' => 'Unlock more benefits with increased activity',
                'icon' => null,
                'min_transaction_volume' => 1000,
                'reward' => 10,
                'max_wallets' => 2,
                'max_referral_level' => 2,
                'allowed_transaction_types' => ['deposit', 'send_money', 'payment'],
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Gold',
                'description' => 'Premium member with enhanced privileges',
                'icon' => null,
                'min_transaction_volume' => 5000,
                'reward' => 25,
                'max_wallets' => 3,
                'max_referral_level' => 3,
                'allowed_transaction_types' => ['deposit', 'send_money', 'payment', 'referral_reward'],
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Platinum',
                'description' => 'Elite member with exclusive benefits',
                'icon' => null,
                'min_transaction_volume' => 25000,
                'reward' => 100,
                'max_wallets' => 5,
                'max_referral_level' => 4,
                'allowed_transaction_types' => ['deposit', 'send_money', 'payment', 'referral_reward'],
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Diamond',
                'description' => 'Top-tier member with maximum privileges',
                'icon' => null,
                'min_transaction_volume' => 100000,
                'reward' => 500,
                'max_wallets' => 10,
                'max_referral_level' => 5,
                'allowed_transaction_types' => ['deposit', 'send_money', 'payment', 'referral_reward'],
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($ranks as $rank) {
            Rank::updateOrCreate(
                ['name' => $rank['name']],
                $rank
            );
        }

        $this->command->info('✅ Created ' . count($ranks) . ' ranks successfully!');
    }
}
