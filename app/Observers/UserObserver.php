<?php

namespace App\Observers;

use App\Models\User;
use App\Models\WalletType;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Find default wallet types
        $defaultWallets = WalletType::where('is_default', true)->where('is_active', true)->get();

        foreach ($defaultWallets as $walletType) {
            $user->accounts()->create([
                'wallet_type_id' => $walletType->id,
                'account_number' => 'ACC-' . strtoupper(uniqid()), // Simple generation for now, can be improved
                'currency' => $walletType->currency_code,
                'balance' => 0,
                'account_type' => 'checking', // Fallback for legacy schema
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
