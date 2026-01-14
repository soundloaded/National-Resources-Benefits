<?php

namespace Database\Seeders;

use App\Models\WithdrawalFormField;
use Illuminate\Database\Seeder;

class WithdrawalFormFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            [
                'name' => 'bank_name',
                'label' => 'Bank Name',
                'type' => 'text',
                'placeholder' => 'e.g., Chase Bank, Bank of America',
                'help_text' => 'Enter the full name of your bank',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'account_holder_name',
                'label' => 'Account Holder Name',
                'type' => 'text',
                'placeholder' => 'Name as it appears on the account',
                'help_text' => 'Must match the name on your bank account',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'account_number',
                'label' => 'Account Number',
                'type' => 'text',
                'placeholder' => 'Your bank account number',
                'help_text' => 'Usually 8-17 digits',
                'is_required' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'routing_number',
                'label' => 'Routing Number (ABA)',
                'type' => 'text',
                'placeholder' => '9-digit routing number',
                'help_text' => 'Found on checks or your bank\'s website',
                'is_required' => true,
                'validation_rules' => ['digits:9'],
                'sort_order' => 4,
            ],
            [
                'name' => 'account_type',
                'label' => 'Account Type',
                'type' => 'select',
                'options' => [
                    ['label' => 'Checking Account', 'value' => 'checking'],
                    ['label' => 'Savings Account', 'value' => 'savings'],
                ],
                'is_required' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'swift_code',
                'label' => 'SWIFT/BIC Code',
                'type' => 'text',
                'placeholder' => 'For international transfers (optional)',
                'help_text' => '8-11 character code for international wires',
                'is_required' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($fields as $field) {
            WithdrawalFormField::updateOrCreate(
                ['name' => $field['name']],
                $field
            );
        }
    }
}
