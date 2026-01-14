<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\FundingSourceController;
use App\Http\Controllers\GrantController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\LinkedAccountController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WithdrawController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard - uses controller for data
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'otp.verified', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'otp.verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update.patch');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    
    // Security routes
    Route::get('/security', [ProfileController::class, 'security'])->name('profile.security');
    Route::put('/security/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/security/sessions', [ProfileController::class, 'logoutOtherSessions'])->name('profile.sessions.destroy');

    // Linked Withdrawal Accounts (accessible even if withdraw is disabled - user needs to set up accounts)
    Route::prefix('linked-accounts')->name('linked-accounts.')->group(function () {
        Route::get('/', [LinkedAccountController::class, 'index'])->name('index');
        Route::get('/form-fields', [LinkedAccountController::class, 'getFormFields'])->name('form-fields');
        Route::get('/list', [LinkedAccountController::class, 'getLinkedAccounts'])->name('list');
        Route::post('/', [LinkedAccountController::class, 'store'])->name('store');
        Route::patch('/{linkedAccount}', [LinkedAccountController::class, 'update'])->name('update');
        Route::post('/{linkedAccount}/default', [LinkedAccountController::class, 'setDefault'])->name('set-default');
        Route::delete('/{linkedAccount}', [LinkedAccountController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'otp.verified'])->group(function () {
    // KYC Routes (accessible without KYC verification)
    Route::prefix('kyc')->name('kyc.')->middleware('feature:kyc')->group(function () {
        Route::get('/', [KycController::class, 'index'])->name('index');
        Route::get('/submit/{template}', [KycController::class, 'create'])->name('create');
        Route::post('/submit/{template}', [KycController::class, 'store'])->name('store');
        Route::get('/document/{document}', [KycController::class, 'show'])->name('show');
    });

    // Accounts (accessible without KYC - view only)
    Route::middleware('feature:accounts')->group(function () {
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    });

    // Transactions (all user transactions)
    Route::get('/transactions', [App\Http\Controllers\TransactionController::class, 'index'])
        ->middleware('feature:transactions')
        ->name('transactions.index');

    // Support Tickets (accessible without KYC)
    Route::middleware('feature:support')->group(function () {
        Route::resource('support-tickets', SupportTicketController::class);
        Route::post('/support-tickets/{supportTicket}/reply', [SupportTicketController::class, 'reply'])->name('support-tickets.reply');
        Route::post('/support-tickets/{supportTicket}/close', [SupportTicketController::class, 'close'])->name('support-tickets.close');
        Route::post('/support-tickets/{supportTicket}/reopen', [SupportTicketController::class, 'reopen'])->name('support-tickets.reopen');
    });

    // Ranks
    Route::get('/ranks', [RankController::class, 'index'])
        ->middleware('feature:ranks')
        ->name('ranks.index');

    // Referrals
    Route::prefix('referrals')->name('referrals.')->middleware('feature:referrals')->group(function () {
        Route::get('/', [ReferralController::class, 'index'])->name('index');
        Route::get('/earnings', [ReferralController::class, 'earnings'])->name('earnings');
    });

    // Funding Sources
    Route::prefix('funding-sources')->name('funding-sources.')->middleware('feature:funding-sources')->group(function () {
        Route::get('/', [FundingSourceController::class, 'index'])->name('index');
        Route::get('/{fundingSource}', [FundingSourceController::class, 'show'])->name('show');
    });

    // Funding Applications
    Route::middleware('feature:applications')->group(function () {
        Route::get('/funding-sources/{fundingSource}/apply', [App\Http\Controllers\FundingApplicationController::class, 'create'])->name('funding-sources.apply');
        Route::post('/funding-sources/{fundingSource}/apply', [App\Http\Controllers\FundingApplicationController::class, 'store'])->name('funding-sources.apply.store');
        
        // My Applications
        Route::prefix('my-applications')->name('my-applications.')->group(function () {
            Route::get('/', [App\Http\Controllers\FundingApplicationController::class, 'index'])->name('index');
            Route::get('/{application}', [App\Http\Controllers\FundingApplicationController::class, 'show'])->name('show');
            Route::post('/{application}/cancel', [App\Http\Controllers\FundingApplicationController::class, 'cancel'])->name('cancel');
        });
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->middleware('feature:notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::delete('/read', [NotificationController::class, 'destroyRead'])->name('destroy-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Admin Impersonation
    Route::get('/admin/users/{user}/impersonate', function (App\Models\User $user) {
        session(['impersonator_id' => auth()->id()]);
        auth()->login($user);
        return redirect()->route('dashboard');
    })->name('admin.impersonate');

    Route::get('/admin/stop-impersonating', function () {
        if (session()->has('impersonator_id')) {
            auth()->loginUsingId(session('impersonator_id'));
            session()->forget('impersonator_id');
            return redirect()->to('/admin');
        }
        return redirect()->route('dashboard');
    })->name('admin.stop-impersonating');
});

// Routes that REQUIRE KYC verification
Route::middleware(['auth', 'verified', 'kyc.verified'])->group(function () {
    // Deposit Hub & Methods
    Route::prefix('deposit')->name('deposit.')->middleware('feature:deposit')->group(function () {
        Route::get('/', [DepositController::class, 'index'])->name('index');
        Route::get('/manual', [DepositController::class, 'manual'])->name('manual');
        Route::get('/automatic', [DepositController::class, 'automatic'])->name('automatic');
        Route::get('/history', [DepositController::class, 'history'])->name('history');
    });

    // Payment Processing (tied to deposit feature)
    Route::middleware('feature:deposit')->group(function () {
        Route::post('/deposit', [PaymentController::class, 'deposit'])->name('payment.deposit');
        Route::get('/deposit/instructions/{transaction}', [PaymentController::class, 'manualInstructions'])->name('deposit.manual.instructions');
        Route::get('/payment/callback/{provider}/{trx}', [PaymentController::class, 'callback'])->name('payment.callback');
    });

    // Transfer Hub & Methods
    Route::prefix('transfer')->name('transfer.')->middleware('feature:transfer')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/history', [TransferController::class, 'history'])->name('history');
        Route::get('/search-recipient', [TransferController::class, 'searchRecipient'])->name('search-recipient');
        
        // Internal Transfer
        Route::middleware('feature:transfer.internal')->group(function () {
            Route::get('/internal', [TransferController::class, 'internal'])->name('internal');
            Route::post('/internal', [TransferController::class, 'storeInternal'])->name('store-internal');
        });
        
        // Own Accounts Transfer
        Route::middleware('feature:transfer.own')->group(function () {
            Route::get('/own-accounts', [TransferController::class, 'ownAccounts'])->name('own-accounts');
            Route::post('/own-accounts', [TransferController::class, 'storeOwnAccounts'])->name('store-own-accounts');
        });
        
        // Domestic Transfer
        Route::middleware('feature:transfer.domestic')->group(function () {
            Route::get('/domestic', [TransferController::class, 'domestic'])->name('domestic');
            Route::post('/domestic', [TransferController::class, 'storeDomestic'])->name('store-domestic');
        });
        
        // Wire Transfer
        Route::middleware('feature:transfer.wire')->group(function () {
            Route::get('/wire', [TransferController::class, 'wire'])->name('wire');
            Route::post('/wire', [TransferController::class, 'storeWire'])->name('store-wire');
        });
    });

    // Withdraw Hub & Methods
    Route::prefix('withdraw')->name('withdraw.')->middleware('feature:withdraw')->group(function () {
        Route::get('/', [WithdrawController::class, 'index'])->name('index');
        Route::get('/manual', [WithdrawController::class, 'manual'])->name('manual');
        Route::get('/automatic', [WithdrawController::class, 'automatic'])->name('automatic');
        Route::get('/verify', [WithdrawController::class, 'verify'])->name('verify');
        Route::post('/verify', [WithdrawController::class, 'verifyCode'])->name('verify.code');
        Route::post('/store', [WithdrawController::class, 'store'])->name('store');
        Route::get('/history', [WithdrawController::class, 'history'])->name('history');
    });

    // Voucher Redemption
    Route::prefix('voucher')->name('voucher.')->middleware('feature:voucher')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::post('/validate', [VoucherController::class, 'validateCode'])->name('validate');
        Route::post('/redeem', [VoucherController::class, 'redeem'])->name('redeem');
        Route::get('/history', [VoucherController::class, 'history'])->name('history');
    });

    // Loans
    Route::prefix('loans')->name('loans.')->middleware('feature:loans')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
        Route::post('/apply', [LoanController::class, 'apply'])->name('apply');
        Route::post('/{loan}/repay', [LoanController::class, 'repay'])->name('repay');
        Route::get('/{loan}/payment/callback', [LoanController::class, 'paymentCallback'])->name('payment.callback');
    });

    // Grants
    Route::prefix('grants')->name('grants.')->middleware('feature:grants')->group(function () {
        Route::get('/', [GrantController::class, 'index'])->name('index');
        Route::get('/{grant}', [GrantController::class, 'show'])->name('show');
    });
});
