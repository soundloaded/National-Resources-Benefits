<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TransferController extends Controller
{
    /**
     * Show the transfer hub page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's accounts with balances
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => $account->balance,
                'balance_formatted' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        // Check transfer permissions
        $canTransfer = $user->can_transfer ?? true;
        
        // Get transfer settings
        $settings = [
            'transfer_internal_active' => Setting::get('transfer_internal_active', true),
            'transfer_domestic_active' => Setting::get('transfer_domestic_active', false),
            'transfer_wire_active' => Setting::get('transfer_wire_active', false),
            'transfer_min' => Setting::get('transfer_min', 1),
            'transfer_max' => Setting::get('transfer_max', 50000),
            'transfer_fee_fixed' => Setting::get('transfer_fee_fixed', 0),
            'transfer_fee_percentage' => Setting::get('transfer_fee_percentage', 0),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Transfer/Index', [
            'accounts' => $accounts,
            'canTransfer' => $canTransfer,
            'settings' => $settings,
            'hasMultipleAccounts' => $accounts->count() > 1,
        ]);
    }
    
    /**
     * Show internal (user-to-user) transfer form.
     */
    public function internal(Request $request)
    {
        $user = $request->user();
        
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => $account->balance,
                'balance_formatted' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        $settings = [
            'transfer_min' => Setting::get('transfer_min', 1),
            'transfer_max' => Setting::get('transfer_max', 50000),
            'transfer_fee_fixed' => Setting::get('transfer_fee_fixed', 0),
            'transfer_fee_percentage' => Setting::get('transfer_fee_percentage', 0),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Transfer/Internal', [
            'accounts' => $accounts,
            'settings' => $settings,
        ]);
    }
    
    /**
     * Show own accounts transfer form.
     */
    public function ownAccounts(Request $request)
    {
        $user = $request->user();
        
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => $account->balance,
                'balance_formatted' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        if ($accounts->count() < 2) {
            return redirect()->route('transfer.index')
                ->with('error', 'You need at least two accounts to transfer between your own accounts.');
        }
        
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        return Inertia::render('Transfer/OwnAccounts', [
            'accounts' => $accounts,
            'settings' => $settings,
        ]);
    }

    /**
     * Show domestic transfer form (bank-to-bank within country).
     */
    public function domestic(Request $request)
    {
        $user = $request->user();
        
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => $account->balance,
                'balance_formatted' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        $settings = [
            'transfer_domestic_active' => Setting::get('transfer_domestic_active', true),
            'domestic_transfer_min' => Setting::get('domestic_transfer_min', 10),
            'domestic_transfer_max' => Setting::get('domestic_transfer_max', 100000),
            'domestic_transfer_fee_fixed' => Setting::get('domestic_transfer_fee_fixed', 5),
            'domestic_transfer_fee_percentage' => Setting::get('domestic_transfer_fee_percentage', 0),
            'domestic_processing_days' => Setting::get('domestic_processing_days', '1-3'),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        // Get user's saved bank accounts (if any)
        $savedBanks = []; // Can be expanded to fetch from a user_bank_accounts table
        
        return Inertia::render('Transfer/Domestic', [
            'accounts' => $accounts,
            'settings' => $settings,
            'savedBanks' => $savedBanks,
        ]);
    }

    /**
     * Show wire transfer form (international).
     */
    public function wire(Request $request)
    {
        $user = $request->user();
        
        $accounts = $user->accounts()
            ->with('walletType')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->walletType?->name ?? 'Main Account',
                'balance' => $account->balance,
                'balance_formatted' => number_format($account->balance, 2),
                'currency' => $account->walletType?->currency ?? 'USD',
            ]);
        
        $settings = [
            'transfer_wire_active' => Setting::get('transfer_wire_active', true),
            'wire_transfer_min' => Setting::get('wire_transfer_min', 100),
            'wire_transfer_max' => Setting::get('wire_transfer_max', 500000),
            'wire_transfer_fee_fixed' => Setting::get('wire_transfer_fee_fixed', 25),
            'wire_transfer_fee_percentage' => Setting::get('wire_transfer_fee_percentage', 0.1),
            'wire_processing_days' => Setting::get('wire_processing_days', '3-5'),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];
        
        // Common countries for wire transfers
        $countries = [
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'JP', 'name' => 'Japan'],
            ['code' => 'CN', 'name' => 'China'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'BR', 'name' => 'Brazil'],
            ['code' => 'MX', 'name' => 'Mexico'],
            ['code' => 'NG', 'name' => 'Nigeria'],
            ['code' => 'ZA', 'name' => 'South Africa'],
            ['code' => 'AE', 'name' => 'United Arab Emirates'],
            ['code' => 'SG', 'name' => 'Singapore'],
        ];
        
        return Inertia::render('Transfer/Wire', [
            'accounts' => $accounts,
            'settings' => $settings,
            'countries' => $countries,
        ]);
    }
    
    /**
     * Search for users by email or account number.
     */
    public function searchRecipient(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
        ]);
        
        $query = $request->input('query');
        $currentUserId = $request->user()->id;
        
        // Search by email or name (not the current user)
        $users = User::where('id', '!=', $currentUserId)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('email', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $this->maskEmail($user->email),
                'avatar' => $user->avatar,
            ]);
        
        return response()->json($users);
    }
    
    /**
     * Process internal transfer.
     */
    public function storeInternal(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|uuid|exists:accounts,id',
            'recipient_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);
        
        $user = $request->user();
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $recipient = User::findOrFail($request->recipient_id);
        $recipientAccount = $recipient->accounts()->first();
        
        if (!$recipientAccount) {
            return back()->withErrors(['recipient_id' => 'Recipient does not have an account.']);
        }
        
        // Get limits
        $minAmount = Setting::get('transfer_min', 1);
        $maxAmount = Setting::get('transfer_max', 50000);
        $feeFixed = Setting::get('transfer_fee_fixed', 0);
        $feePercentage = Setting::get('transfer_fee_percentage', 0);
        
        $amount = $request->amount;
        $fee = $feeFixed + ($feePercentage / 100 * $amount);
        $totalDebit = $amount + $fee;
        
        // Validations
        if ($amount < $minAmount) {
            return back()->withErrors(['amount' => "Minimum transfer amount is \${$minAmount}."]);
        }
        
        if ($amount > $maxAmount) {
            return back()->withErrors(['amount' => "Maximum transfer amount is \${$maxAmount}."]);
        }
        
        if ($fromAccount->balance < $totalDebit) {
            return back()->withErrors(['amount' => 'Insufficient balance.']);
        }
        
        if ($recipient->id === $user->id) {
            return back()->withErrors(['recipient_id' => 'Cannot transfer to yourself.']);
        }
        
        // Process transfer
        $transfer = DB::transaction(function () use ($fromAccount, $recipientAccount, $amount, $fee, $totalDebit, $request, $user, $recipient) {
            $baseReference = 'TRF-' . strtoupper(Str::random(12));
            
            // Note: Balance updates are handled automatically by TransactionObserver
            // when completed transactions are created
            
            // Create transfer record
            $transfer = Transfer::create([
                'user_id' => $user->id,
                'type' => 'internal',
                'amount' => $amount,
                'description' => $request->description,
                'status' => 'completed',
                'created_by' => $user->id,
            ]);
            
            // Sender transaction (transfer_out)
            Transaction::create([
                'account_id' => $fromAccount->id,
                'transaction_type' => 'transfer_out',
                'method' => 'internal_transfer',
                'amount' => $totalDebit,
                'currency' => 'USD',
                'status' => 'completed',
                'description' => $request->description ?? "Transfer to {$recipient->name}",
                'reference_number' => $baseReference . '-OUT',
                'metadata' => [
                    'transfer_id' => $transfer->id,
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->name,
                    'fee' => $fee,
                ],
                'completed_at' => now(),
            ]);
            
            // Recipient transaction (transfer_in)
            Transaction::create([
                'account_id' => $recipientAccount->id,
                'transaction_type' => 'transfer_in',
                'method' => 'internal_transfer',
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
                'description' => "Transfer from {$user->name}",
                'reference_number' => $baseReference . '-IN',
                'metadata' => [
                    'transfer_id' => $transfer->id,
                    'sender_id' => $user->id,
                    'sender_name' => $user->name,
                ],
                'completed_at' => now(),
            ]);
            
            return $transfer;
        });
        
        // Notify sender and recipient (wrapped in try-catch to not break transfer flow)
        try {
            $user->notify(new \App\Notifications\TransferCompleted($transfer, $amount, $recipient->name, 'sent'));
            $recipient->notify(new \App\Notifications\TransferCompleted($transfer, $amount, $user->name, 'received'));
        } catch (\Exception $e) {
            \Log::error('Failed to send transfer notifications: ' . $e->getMessage());
        }
        
        return redirect()->route('transfer.index')
            ->with('success', "Successfully transferred \${$amount} to {$recipient->name}.");
    }
    
    /**
     * Process own accounts transfer.
     */
    public function storeOwnAccounts(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|uuid|exists:accounts,id',
            'to_account_id' => 'required|uuid|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);
        
        $user = $request->user();
        
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', $user->id)
            ->with('walletType')
            ->firstOrFail();
            
        $toAccount = Account::where('id', $request->to_account_id)
            ->where('user_id', $user->id)
            ->with('walletType')
            ->firstOrFail();
        
        $amount = $request->amount;
        
        if ($fromAccount->balance < $amount) {
            return back()->withErrors(['amount' => 'Insufficient balance.']);
        }
        
        // Process transfer (no fee for own account transfers)
        DB::transaction(function () use ($fromAccount, $toAccount, $amount, $request, $user) {
            $baseReference = 'OAT-' . strtoupper(Str::random(12));
            
            // Note: Balance updates are handled automatically by TransactionObserver
            // when completed transactions are created
            
            $fromName = $fromAccount->walletType?->name ?? 'Account';
            $toName = $toAccount->walletType?->name ?? 'Account';
            
            // Source transaction
            Transaction::create([
                'account_id' => $fromAccount->id,
                'transaction_type' => 'transfer_out',
                'method' => 'own_account_transfer',
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
                'description' => $request->description ?? "Transfer to {$toName}",
                'reference_number' => $baseReference . '-OUT',
                'metadata' => [
                    'to_account_id' => $toAccount->id,
                    'to_account_name' => $toName,
                ],
                'completed_at' => now(),
            ]);
            
            // Destination transaction
            Transaction::create([
                'account_id' => $toAccount->id,
                'transaction_type' => 'transfer_in',
                'method' => 'own_account_transfer',
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
                'description' => "Transfer from {$fromName}",
                'reference_number' => $baseReference . '-IN',
                'metadata' => [
                    'from_account_id' => $fromAccount->id,
                    'from_account_name' => $fromName,
                ],
                'completed_at' => now(),
            ]);
        });
        
        return redirect()->route('transfer.index')
            ->with('success', "Successfully transferred \${$amount} between your accounts.");
    }

    /**
     * Process domestic bank transfer.
     */
    public function storeDomestic(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|uuid|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:30',
            'routing_number' => 'required|string|size:9',
            'account_type' => 'required|in:checking,savings',
            'description' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Get limits and fees
        $minAmount = Setting::get('domestic_transfer_min', 10);
        $maxAmount = Setting::get('domestic_transfer_max', 100000);
        $feeFixed = Setting::get('domestic_transfer_fee_fixed', 5);
        $feePercentage = Setting::get('domestic_transfer_fee_percentage', 0);

        $amount = $request->amount;
        $fee = $feeFixed + ($feePercentage / 100 * $amount);
        $totalDebit = $amount + $fee;

        // Validations
        if ($amount < $minAmount) {
            return back()->withErrors(['amount' => "Minimum transfer amount is \${$minAmount}."]);
        }

        if ($amount > $maxAmount) {
            return back()->withErrors(['amount' => "Maximum transfer amount is \${$maxAmount}."]);
        }

        if ($fromAccount->balance < $totalDebit) {
            return back()->withErrors(['amount' => 'Insufficient balance for this transfer including fees.']);
        }

        // Process transfer
        DB::transaction(function () use ($fromAccount, $amount, $fee, $totalDebit, $request, $user) {
            $referenceNumber = 'DOM-' . strtoupper(Str::random(12));

            // Debit source account
            $fromAccount->decrement('balance', $totalDebit);

            // Create transfer record
            $transfer = Transfer::create([
                'user_id' => $user->id,
                'sender_id' => $user->id,
                'recipient_id' => null, // External transfer
                'from_account_id' => $fromAccount->id,
                'to_account_id' => null,
                'type' => 'domestic',
                'amount' => $amount,
                'fee' => $fee,
                'status' => 'pending',
                'description' => $request->description ?? "Domestic transfer to {$request->bank_name}",
                'reference_number' => $referenceNumber,
                'metadata' => [
                    'bank_name' => $request->bank_name,
                    'account_holder_name' => $request->account_holder_name,
                    'account_number' => substr($request->account_number, -4), // Store last 4 only
                    'routing_number' => $request->routing_number,
                    'account_type' => $request->account_type,
                ],
                'created_by' => $user->id,
            ]);

            // Create transaction record (balance already adjusted above, mark it so observer doesn't debit again)
            Transaction::create([
                'account_id' => $fromAccount->id,
                'transaction_type' => 'transfer_out',
                'method' => 'domestic_transfer',
                'amount' => $amount,
                'fee' => $fee,
                'currency' => 'USD',
                'status' => 'pending',
                'description' => $request->description ?? "Domestic transfer to {$request->bank_name}",
                'reference_number' => $referenceNumber,
                'metadata' => [
                    'transfer_id' => $transfer->id,
                    'bank_name' => $request->bank_name,
                    'account_holder' => $request->account_holder_name,
                    'estimated_arrival' => now()->addWeekdays(3)->format('M d, Y'),
                    'balance_adjusted' => true, // Prevents observer from debiting again
                ],
            ]);
        });

        return redirect()->route('transfer.history')
            ->with('success', "Domestic transfer of \${$amount} initiated successfully. Expected arrival: 1-3 business days.");
    }

    /**
     * Process international wire transfer.
     */
    public function storeWire(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|uuid|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'beneficiary_name' => 'required|string|max:255',
            'beneficiary_address' => 'required|string|max:500',
            'beneficiary_country' => 'required|string|size:2',
            'bank_name' => 'required|string|max:255',
            'bank_address' => 'required|string|max:500',
            'swift_code' => 'required|string|min:8|max:11',
            'iban' => 'nullable|string|max:34',
            'account_number' => 'required|string|max:30',
            'purpose' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Get limits and fees
        $minAmount = Setting::get('wire_transfer_min', 100);
        $maxAmount = Setting::get('wire_transfer_max', 500000);
        $feeFixed = Setting::get('wire_transfer_fee_fixed', 25);
        $feePercentage = Setting::get('wire_transfer_fee_percentage', 0.1);

        $amount = $request->amount;
        $fee = $feeFixed + ($feePercentage / 100 * $amount);
        $totalDebit = $amount + $fee;

        // Validations
        if ($amount < $minAmount) {
            return back()->withErrors(['amount' => "Minimum wire transfer amount is \${$minAmount}."]);
        }

        if ($amount > $maxAmount) {
            return back()->withErrors(['amount' => "Maximum wire transfer amount is \${$maxAmount}."]);
        }

        if ($fromAccount->balance < $totalDebit) {
            return back()->withErrors(['amount' => 'Insufficient balance for this transfer including fees.']);
        }

        // Process transfer
        DB::transaction(function () use ($fromAccount, $amount, $fee, $totalDebit, $request, $user) {
            $referenceNumber = 'WIRE-' . strtoupper(Str::random(12));

            // Debit source account
            $fromAccount->decrement('balance', $totalDebit);

            // Create transfer record
            $transfer = Transfer::create([
                'user_id' => $user->id,
                'sender_id' => $user->id,
                'recipient_id' => null, // External transfer
                'from_account_id' => $fromAccount->id,
                'to_account_id' => null,
                'type' => 'wire',
                'amount' => $amount,
                'fee' => $fee,
                'status' => 'pending',
                'description' => $request->description ?? "Wire transfer to {$request->beneficiary_name}",
                'reference_number' => $referenceNumber,
                'metadata' => [
                    'beneficiary_name' => $request->beneficiary_name,
                    'beneficiary_address' => $request->beneficiary_address,
                    'beneficiary_country' => $request->beneficiary_country,
                    'bank_name' => $request->bank_name,
                    'bank_address' => $request->bank_address,
                    'swift_code' => $request->swift_code,
                    'iban' => $request->iban,
                    'account_number' => substr($request->account_number, -4), // Last 4 only
                    'purpose' => $request->purpose,
                ],
                'created_by' => $user->id,
            ]);

            // Create transaction record (balance already adjusted above, mark it so observer doesn't debit again)
            Transaction::create([
                'account_id' => $fromAccount->id,
                'transaction_type' => 'transfer_out',
                'method' => 'wire_transfer',
                'amount' => $amount,
                'fee' => $fee,
                'currency' => 'USD',
                'status' => 'pending',
                'description' => $request->description ?? "Wire transfer to {$request->beneficiary_name}",
                'reference_number' => $referenceNumber,
                'metadata' => [
                    'transfer_id' => $transfer->id,
                    'beneficiary_name' => $request->beneficiary_name,
                    'beneficiary_country' => $request->beneficiary_country,
                    'bank_name' => $request->bank_name,
                    'swift_code' => $request->swift_code,
                    'estimated_arrival' => now()->addWeekdays(5)->format('M d, Y'),
                    'balance_adjusted' => true, // Prevents observer from debiting again
                ],
            ]);
        });

        return redirect()->route('transfer.history')
            ->with('success', "Wire transfer of \${$amount} initiated successfully. Expected arrival: 3-5 business days.");
    }
    
    /**
     * Show transfer history.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $transfers = Transaction::whereHas('account', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereIn('transaction_type', ['transfer_in', 'transfer_out'])
            ->with(['account.walletType'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(fn($transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->transaction_type,
                'method' => $transaction->method,
                'amount' => number_format($transaction->amount, 2),
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'description' => $transaction->description,
                'reference' => $transaction->reference_number,
                'account_name' => $transaction->account?->walletType?->name ?? 'Main Account',
                'counterparty' => $this->getCounterparty($transaction),
                'created_at' => $transaction->created_at->format('M d, Y H:i'),
            ]);
        
        return Inertia::render('Transfer/History', [
            'transfers' => $transfers,
        ]);
    }
    
    /**
     * Mask email for privacy.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 3));
        
        return $maskedName . '@' . $domain;
    }
    
    /**
     * Get counterparty info from transaction metadata.
     */
    private function getCounterparty(Transaction $transaction): ?string
    {
        $metadata = $transaction->metadata ?? [];
        
        if ($transaction->transaction_type === 'transfer_out') {
            return $metadata['recipient_name'] ?? $metadata['to_account_name'] ?? null;
        }
        
        return $metadata['sender_name'] ?? $metadata['from_account_name'] ?? null;
    }
}
