<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Load user with relationships
        $user->load(['accounts.walletType', 'rank']);
        
        // Get recent transactions (last 10)
        $recentTransactions = $user->transactions()
            ->with('account.walletType')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'reference' => $transaction->reference_number,
                    'wallet' => $transaction->account?->walletType?->name ?? 'N/A',
                    'created_at' => $transaction->created_at->format('M d, Y H:i'),
                    'completed_at' => $transaction->completed_at?->format('M d, Y H:i'),
                ];
            });

        // Calculate total balance across all accounts
        $totalBalance = $user->accounts->sum('balance');
        
        // Get accounts with wallet info
        $accounts = $user->accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'balance' => (float) $account->balance,
                'account_number' => $account->account_number,
                'wallet_type' => [
                    'id' => $account->walletType?->id,
                    'name' => $account->walletType?->name ?? 'Main',
                    'slug' => $account->walletType?->slug ?? 'main',
                    'icon' => $account->walletType?->icon,
                ],
            ];
        });

        // Get unread notifications count
        $unreadNotifications = $user->unreadNotifications()->count();
        
        // Get recent notifications (last 5)
        $recentNotifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'data' => $notification->data,
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        // Get KYC status
        $kycStatus = $this->getKycStatus($user);

        // Get quick stats - use table prefix to avoid ambiguous column errors
        $stats = [
            'total_deposits' => $user->transactions()->where('transactions.transaction_type', 'deposit')->where('transactions.status', 'completed')->sum('transactions.amount'),
            'total_withdrawals' => $user->transactions()->where('transactions.transaction_type', 'withdrawal')->where('transactions.status', 'completed')->sum('transactions.amount'),
            'total_transfers' => $user->transactions()->whereIn('transactions.transaction_type', ['transfer_in', 'transfer_out'])->where('transactions.status', 'completed')->sum('transactions.amount'),
            'pending_transactions' => $user->transactions()->where('transactions.status', 'pending')->count(),
        ];

        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->getFilamentAvatarUrl(),
                'referral_code' => $user->referral_code,
                'rank' => $user->rank ? [
                    'name' => $user->rank->name,
                    'badge' => $user->rank->badge_color ?? 'gray',
                ] : null,
            ],
            'accounts' => $accounts,
            'totalBalance' => (float) $totalBalance,
            'recentTransactions' => $recentTransactions,
            'unreadNotifications' => $unreadNotifications,
            'recentNotifications' => $recentNotifications,
            'kycStatus' => $kycStatus,
            'stats' => $stats,
            'permissions' => [
                'can_deposit' => (bool) $user->can_deposit,
                'can_withdraw' => (bool) $user->can_withdraw,
                'can_transfer' => (bool) $user->can_transfer,
                'can_use_voucher' => (bool) $user->can_use_voucher,
            ],
        ]);
    }

    /**
     * Get user's KYC status and required documents.
     */
    private function getKycStatus($user): array
    {
        $isVerified = $user->kyc_verified_at !== null;
        
        // Get user's submitted documents
        $submittedDocs = $user->kycdocuments()->get();
        
        // Count by status
        $pending = $submittedDocs->where('status', 'pending')->count();
        $approved = $submittedDocs->where('status', 'approved')->count();
        $rejected = $submittedDocs->where('status', 'rejected')->count();

        return [
            'verified' => $isVerified,
            'verified_at' => $user->kyc_verified_at?->format('M d, Y'),
            'documents' => [
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'total' => $submittedDocs->count(),
            ],
            'status' => $isVerified ? 'verified' : ($pending > 0 ? 'pending' : ($rejected > 0 ? 'rejected' : 'not_started')),
        ];
    }
}
