<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Display all transactions for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $accountIds = $user->accounts()->pluck('id');

        // Build transactions query
        $transactionsQuery = Transaction::whereIn('account_id', $accountIds)
            ->with('account:id,account_number,account_type');

        // Filter by type
        if ($request->filled('type')) {
            $transactionsQuery->where('transaction_type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $transactionsQuery->where('status', $request->status);
        }

        // Filter by account
        if ($request->filled('account')) {
            $transactionsQuery->where('account_id', $request->account);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $transactionsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $transactionsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by reference or description
        if ($request->filled('search')) {
            $search = $request->search;
            $transactionsQuery->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Get transactions with pagination
        $transactions = $transactionsQuery
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'account_id' => $t->account_id,
                'account_number' => $t->account?->account_number,
                'account_type' => $t->account?->account_type,
                'transaction_type' => $t->transaction_type,
                'amount' => (float) $t->amount,
                'currency' => $t->currency,
                'status' => $t->status,
                'description' => $t->description,
                'reference_number' => $t->reference_number,
                'method' => $t->method,
                'completed_at' => $t->completed_at?->format('M d, Y H:i'),
                'created_at' => $t->created_at->format('M d, Y H:i'),
            ]);

        // Get transfers for the user (wire transfers, internal transfers, etc.)
        $transfersQuery = Transfer::where('user_id', $user->id);

        // Apply same filters to transfers if applicable
        if ($request->filled('status')) {
            $transfersQuery->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $transfersQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $transfersQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $transfersQuery->where('description', 'like', "%{$request->search}%");
        }

        // Only show transfers if not filtering by transaction_type or filtering for transfers
        $transfers = collect();
        if (!$request->filled('type') || in_array($request->type, ['transfer', 'wire_transfer', 'internal_transfer', 'domestic_transfer'])) {
            $transfers = $transfersQuery
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()
                ->map(fn ($t) => [
                    'id' => 'transfer_' . $t->id,
                    'account_id' => null,
                    'account_number' => null,
                    'account_type' => null,
                    'transaction_type' => $t->type . '_transfer',
                    'amount' => (float) $t->amount,
                    'currency' => 'USD',
                    'status' => $t->status,
                    'description' => $t->description,
                    'reference_number' => 'TRF-' . str_pad($t->id, 8, '0', STR_PAD_LEFT),
                    'method' => $t->type,
                    'completed_at' => $t->status === 'completed' ? $t->updated_at->format('M d, Y H:i') : null,
                    'created_at' => $t->created_at->format('M d, Y H:i'),
                ]);
        }

        // Get user's accounts for filter dropdown
        $accounts = $user->accounts()->select('id', 'account_number', 'account_type')->get();

        // Get all transaction types for filter
        $transactionTypes = Transaction::whereIn('account_id', $accountIds)
            ->select('transaction_type')
            ->distinct()
            ->pluck('transaction_type')
            ->merge($transfers->pluck('transaction_type')->unique())
            ->unique()
            ->values()
            ->toArray();

        // Get all statuses
        $statuses = ['pending', 'completed', 'failed', 'cancelled'];

        // Stats
        $stats = [
            'total_transactions' => Transaction::whereIn('account_id', $accountIds)->count() + Transfer::where('user_id', $user->id)->count(),
            'total_deposits' => Transaction::whereIn('account_id', $accountIds)->where('transaction_type', 'deposit')->where('status', 'completed')->sum('amount'),
            'total_withdrawals' => Transaction::whereIn('account_id', $accountIds)->where('transaction_type', 'withdrawal')->where('status', 'completed')->sum('amount'),
            'pending_count' => Transaction::whereIn('account_id', $accountIds)->where('status', 'pending')->count() + Transfer::where('user_id', $user->id)->where('status', 'pending')->count(),
        ];

        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', '$'),
        ];

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'transfers' => $transfers,
            'accounts' => $accounts,
            'transactionTypes' => $transactionTypes,
            'statuses' => $statuses,
            'stats' => $stats,
            'filters' => [
                'type' => $request->type,
                'status' => $request->status,
                'account' => $request->account,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'search' => $request->search,
            ],
            'settings' => $settings,
        ]);
    }
}
