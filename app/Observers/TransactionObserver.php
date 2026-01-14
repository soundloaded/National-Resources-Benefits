<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\ReferralService;
use App\Services\RankService;
use App\Notifications\DepositReceived;
use App\Notifications\AdminAlert;
use App\Models\PaymentGateway;
use Filament\Notifications\Notification as FilamentNotification;

class TransactionObserver
{
    protected $referralService;
    protected $rankService;

    public function __construct(ReferralService $referralService, RankService $rankService)
    {
        $this->referralService = $referralService;
        $this->rankService = $rankService;
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Check if status changed to 'completed'
        if ($transaction->isDirty('status') && $transaction->status === 'completed') {
            $this->processAfterCompletion($transaction);
        }
    }
    
    /**
     * Handle the Transaction "created" event.
     * (In case it's created directly as completed, e.g. automatic gateways)
     */
    public function created(Transaction $transaction): void
    {
        if ($transaction->status === 'completed') {
            $this->processAfterCompletion($transaction);
        } elseif ($transaction->status === 'pending') {
            if ($transaction->transaction_type === 'deposit' && $this->isManualTransaction($transaction)) {
                $this->notifyAdminsOfManualDeposit($transaction);
            } elseif ($transaction->transaction_type === 'withdrawal') {
                $this->notifyAdminsOfWithdrawalRequest($transaction);
            }
        }
    }

    protected function processAfterCompletion(Transaction $transaction)
    {
        // 1. Update Account Balance
        $this->updateAccountBalance($transaction);
        
        // 2. Process Referrals
        $this->processReferrals($transaction);

        // 3. Check Rank Progression (Only if it's a deposit, usually)
        if ($transaction->transaction_type === 'deposit') {
            $user = $transaction->account->user;
            if ($user) {
                // Wrap notification in try-catch to prevent mail errors from breaking the flow
                try {
                    $user->notify(new DepositReceived($transaction));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send deposit notification: ' . $e->getMessage());
                }
                $this->rankService->checkAndUpgradeRank($user);
            }
        }
    }
    
    /**
     * Update the account balance based on transaction type.
     */
    protected function updateAccountBalance(Transaction $transaction)
    {
        $account = $transaction->account;
        
        if (!$account) {
            \Log::error('Transaction has no associated account', ['transaction_id' => $transaction->id]);
            return;
        }
        
        // Skip if balance was already adjusted (e.g., external transfers that debit on creation)
        $metadata = $transaction->metadata ?? [];
        if (!empty($metadata['balance_adjusted'])) {
            \Log::info('Skipping balance update - already adjusted', ['transaction_id' => $transaction->id]);
            return;
        }
        
        $amount = (float) $transaction->amount;
        
        // Determine if this is a credit or debit transaction
        $creditTypes = ['deposit', 'transfer_in', 'refund', 'referral_bonus', 'rank_reward', 'loan_disbursement', 'grant_disbursement', 'voucher_redemption'];
        $debitTypes = ['withdrawal', 'transfer_out', 'payment', 'fee', 'loan_repayment'];
        
        if (in_array($transaction->transaction_type, $creditTypes)) {
            // Credit - add to balance
            $account->increment('balance', $amount);
            \Log::info('Account credited', [
                'account_id' => $account->id,
                'amount' => $amount,
                'new_balance' => $account->fresh()->balance,
                'transaction_id' => $transaction->id,
                'type' => $transaction->transaction_type
            ]);
        } elseif (in_array($transaction->transaction_type, $debitTypes)) {
            // Debit - subtract from balance
            $account->decrement('balance', $amount);
            \Log::info('Account debited', [
                'account_id' => $account->id,
                'amount' => $amount,
                'new_balance' => $account->fresh()->balance,
                'transaction_id' => $transaction->id,
                'type' => $transaction->transaction_type
            ]);
        } else {
            \Log::warning('Unknown transaction type for balance update', [
                'transaction_id' => $transaction->id,
                'type' => $transaction->transaction_type
            ]);
        }
    }

    protected function isManualTransaction(Transaction $transaction): bool
    {
         if (isset($transaction->metadata['gateway_id'])) {
             $gateway = PaymentGateway::find($transaction->metadata['gateway_id']);
             return $gateway && $gateway->type === 'manual';
         }
         
         // Fallback code check
         if ($transaction->method) {
             $gateway = PaymentGateway::where('code', $transaction->method)->first();
             return $gateway && $gateway->type === 'manual';
         }

         return false;
    }

    protected function notifyAdminsOfManualDeposit(Transaction $transaction)
    {
        try {
            $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
                       ->orWhere('email', 'admin@admin.com')
                       ->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new AdminAlert(
                    'New Manual Deposit Request',
                    "A user has requested a manual deposit of {$transaction->currency} {$transaction->amount}. Please review.",
                    url('/admin/deposit/manual-deposit-requests/' . $transaction->id . '/edit'),
                    'info'
                ));
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to send admin notification for manual deposit: ' . $e->getMessage());
        }
    }

    protected function notifyAdminsOfWithdrawalRequest(Transaction $transaction)
    {
        try {
            $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
                       ->orWhere('email', 'admin@admin.com')
                       ->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new AdminAlert(
                    'New Withdrawal Request',
                    "A user has requested a withdrawal of {$transaction->currency} {$transaction->amount}. Please review.",
                    url('/admin/withdraw/manual-withdraw-requests/' . $transaction->id . '/edit'), // Check URL matches resource slug
                    'warning'
                ));
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to send admin notification for withdrawal: ' . $e->getMessage());
        }
    }

    protected function processReferrals(Transaction $transaction)
    {
        if ($transaction->transaction_type === 'deposit') {
            $this->referralService->distributeDepositRewards($transaction);
        }
        
        // Assuming 'withdrawal' or specific 'payment' type fits the second category
        // The user requirement said "Payment Rewards", often this means purchases.
        // If "Payment" maps to Withdrawals (unlikely for rewards, usually you reward DEPOSITS), 
        // OR if it maps to "Sending Money" / "Purchase".
        // Without clear definition, I will assume any OUTGOING transaction that is NOT a referral reward or withdrawal might be a "payment"?
        // OR maybe the user uses "Payment" to mean "User pays for something".
        // For safety, I'll link it to 'payment' type if it exists, or wait for clarification.
        // However, I'll hook it to 'payment' string for now.
        
        if ($transaction->transaction_type === 'payment') {
            $this->referralService->distributePaymentRewards($transaction);
        }
    }
}
