<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            // Automatic Gateways
            [
                'name' => 'Stripe',
                'code' => 'stripe_payment',
                'type' => 'automatic',
                'category' => 'deposit', // Keep old column valid
                'category_new' => 'payment',
                'provider' => 'stripe',
                'mode' => 'test',
                'description' => 'Pay with credit/debit card via Stripe',
                'instructions' => 'You will be redirected to Stripe to complete your payment securely.',
                'min_limit' => 1.00,
                'max_limit' => 50000.00,
                'fee_fixed' => 0.30,
                'fee_percentage' => 2.9,
                'credentials' => [
                    'test_public_key' => '',
                    'test_secret_key' => '',
                    'test_webhook_secret' => '',
                    'live_public_key' => '',
                    'live_secret_key' => '',
                    'live_webhook_secret' => '',
                ],
                'is_active' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Paystack',
                'code' => 'paystack_payment',
                'type' => 'automatic',
                'category' => 'deposit',
                'category_new' => 'payment',
                'provider' => 'paystack',
                'mode' => 'test',
                'description' => 'Pay with card, bank transfer, or USSD via Paystack',
                'instructions' => 'You will be redirected to Paystack to complete your payment.',
                'min_limit' => 100.00,
                'max_limit' => 10000000.00,
                'fee_fixed' => 100.00,
                'fee_percentage' => 1.5,
                'credentials' => [
                    'test_public_key' => '',
                    'test_secret_key' => '',
                    'live_public_key' => '',
                    'live_secret_key' => '',
                ],
                'is_active' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Flutterwave',
                'code' => 'flutterwave_payment',
                'type' => 'automatic',
                'category' => 'deposit',
                'category_new' => 'payment',
                'provider' => 'flutterwave',
                'mode' => 'test',
                'description' => 'Pay with card, bank, mobile money via Flutterwave',
                'instructions' => 'You will be redirected to Flutterwave to complete your payment.',
                'min_limit' => 100.00,
                'max_limit' => 10000000.00,
                'fee_fixed' => 0,
                'fee_percentage' => 1.4,
                'credentials' => [
                    'test_public_key' => '',
                    'test_secret_key' => '',
                    'live_public_key' => '',
                    'live_secret_key' => '',
                ],
                'is_active' => false,
                'sort_order' => 3,
            ],
            
            // Manual Gateways
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'type' => 'manual',
                'category' => 'deposit',
                'category_new' => 'payment',
                'provider' => null,
                'mode' => 'live',
                'description' => 'Pay via direct bank transfer',
                'instructions' => "## Bank Transfer Instructions\n\n1. Transfer the exact amount to the bank account below\n2. Use your **Payment Reference** as the transfer description\n3. Upload proof of payment after transfer\n4. Your payment will be confirmed within 24 hours\n\n**Important:** Transfers without the correct reference may be delayed.",
                'min_limit' => 10.00,
                'max_limit' => 100000.00,
                'fee_fixed' => 0,
                'fee_percentage' => 0,
                'credentials' => [
                    'bank_name' => 'Example Bank',
                    'account_name' => 'Company Name LLC',
                    'account_number' => '1234567890',
                    'routing_number' => '021000021',
                    'swift_code' => 'EXAMUS33',
                ],
                'is_active' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'USDT (TRC-20)',
                'code' => 'usdt_trc20',
                'type' => 'manual',
                'category' => 'deposit',
                'category_new' => 'payment',
                'provider' => null,
                'mode' => 'live',
                'description' => 'Pay with USDT on TRC-20 network',
                'instructions' => "## USDT Payment Instructions\n\n1. Send the exact USDT amount to the wallet address below\n2. **Network:** TRC-20 (TRON Network)\n3. Include your **Payment Reference** in the memo/note\n4. Upload transaction hash after payment\n5. Confirmations usually take 1-5 minutes\n\n⚠️ **Warning:** Only send USDT on TRC-20 network. Other tokens or networks will be lost!",
                'min_limit' => 10.00,
                'max_limit' => 100000.00,
                'fee_fixed' => 1.00,
                'fee_percentage' => 0,
                'credentials' => [
                    'wallet_address' => 'TXXXXXXXXXXXXXXXXXXXXXXXXXXExample',
                    'network' => 'TRC-20',
                ],
                'is_active' => false,
                'sort_order' => 11,
            ],
            [
                'name' => 'Bitcoin (BTC)',
                'code' => 'bitcoin_btc',
                'type' => 'manual',
                'category' => 'deposit',
                'category_new' => 'payment',
                'provider' => null,
                'mode' => 'live',
                'description' => 'Pay with Bitcoin',
                'instructions' => "## Bitcoin Payment Instructions\n\n1. Send the exact BTC amount to the wallet address below\n2. Include your **Payment Reference** if possible\n3. Upload transaction hash after payment\n4. Wait for at least 3 network confirmations\n\n⚠️ Note: Bitcoin transactions may take 10-60 minutes to confirm.",
                'min_limit' => 50.00,
                'max_limit' => 500000.00,
                'fee_fixed' => 0,
                'fee_percentage' => 0,
                'credentials' => [
                    'wallet_address' => 'bc1qXXXXXXXXXXXXXXXXXXXXXXXXXXExample',
                    'network' => 'Bitcoin Mainnet',
                ],
                'is_active' => false,
                'sort_order' => 12,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                $gateway
            );
        }

        $this->command->info('Payment gateways seeded successfully!');
    }
}
