<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountController extends Controller
{
    /**
     * Display a listing of user's accounts.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all accounts with wallet type and recent transactions count
        $accounts = $user->accounts()
            ->with('walletType')
            ->withCount('transactions')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_number' => $account->account_number,
                    'account_type' => $account->account_type,
                    'balance' => (float) $account->balance,
                    'currency' => $account->currency,
                    'status' => $account->status,
                    'wallet_type' => $account->walletType ? [
                        'id' => $account->walletType->id,
                        'name' => $account->walletType->name,
                        'icon' => $account->walletType->icon ?? 'pi-wallet',
                        'color' => $account->walletType->color ?? 'blue',
                    ] : null,
                    'transactions_count' => $account->transactions_count,
                    'created_at' => $account->created_at->format('M d, Y'),
                ];
            });

        // Calculate totals
        $totalBalance = $accounts->sum('balance');
        
        // Get stats per account
        $stats = [
            'total_accounts' => $accounts->count(),
            'active_accounts' => $accounts->where('status', 'active')->count(),
            'total_balance' => $totalBalance,
        ];

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the specified account with transaction history.
     */
    public function show(Request $request, Account $account)
    {
        $user = $request->user();

        // Ensure the account belongs to the current user
        if ($account->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this account.');
        }

        // Load wallet type
        $account->load('walletType');

        // Get transactions with pagination and filters
        $transactionsQuery = $account->transactions()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('type')) {
            $transactionsQuery->where('transaction_type', $request->type);
        }

        if ($request->filled('status')) {
            $transactionsQuery->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $transactionsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $transactionsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Paginate
        $transactions = $transactionsQuery->paginate(15)->through(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->transaction_type,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'description' => $transaction->description,
                'reference' => $transaction->reference_number,
                'created_at' => $transaction->created_at->format('M d, Y H:i'),
                'completed_at' => $transaction->completed_at?->format('M d, Y H:i'),
            ];
        });

        // Calculate account stats
        $accountStats = [
            'total_deposits' => $account->transactions()
                ->where('transaction_type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_withdrawals' => $account->transactions()
                ->where('transaction_type', 'withdrawal')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_transfers_in' => $account->transactions()
                ->where('transaction_type', 'transfer_in')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_transfers_out' => $account->transactions()
                ->where('transaction_type', 'transfer_out')
                ->where('status', 'completed')
                ->sum('amount'),
            'pending_transactions' => $account->transactions()
                ->where('status', 'pending')
                ->count(),
        ];

        return Inertia::render('Accounts/Show', [
            'account' => [
                'id' => $account->id,
                'account_number' => $account->account_number,
                'account_type' => $account->account_type,
                'balance' => (float) $account->balance,
                'currency' => $account->currency,
                'status' => $account->status,
                'wallet_type' => $account->walletType ? [
                    'id' => $account->walletType->id,
                    'name' => $account->walletType->name,
                    'icon' => $account->walletType->icon ?? 'pi-wallet',
                    'color' => $account->walletType->color ?? 'blue',
                ] : null,
                'created_at' => $account->created_at->format('M d, Y'),
            ],
            'transactions' => $transactions,
            'stats' => $accountStats,
            'filters' => [
                'type' => $request->type,
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ],
        ]);
    }
}
