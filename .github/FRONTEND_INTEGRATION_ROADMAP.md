# Frontend Integration Roadmap - User Dashboard

> **Document Created:** January 11, 2026
> **Project:** NationalResourceBenefits.gov - User Frontend
> **Priority:** Mobile-First, Responsive, Clean UI/UX
> **Tech Stack:** Vue 3 (Composition API), Inertia.js, PrimeVue 4, Tailwind CSS

---

## ⚠️ Important Notes

### Public Homepage (Welcome Page)
**STATUS: PENDING HTML TEMPLATE**
- The public homepage/landing page (`Welcome.vue`) will be provided as HTML
- This roadmap focuses ONLY on the authenticated user dashboard
- We will convert the provided HTML template when received

### Settings Integration Requirement
**ALL frontend features MUST reference admin settings from the Settings model:**
- Use `Setting::get('key', 'default')` in controllers
- Pass settings as Inertia props to Vue components
- Dynamic values: limits, fees, feature toggles, branding, etc.

---

## 📊 Admin Backend Audit - What We Have Built

### ✅ Filament Admin Panel (Complete)

#### Navigation Groups & Resources

| Group | Resource/Cluster | Models | Status |
|-------|------------------|--------|--------|
| **User Management** | UserResource | User | ✅ Complete |
| | RankResource | Rank, UserRankHistory | ✅ Complete |
| | ReferralResource | Referral, ReferralSetting | ✅ Complete |
| **Financial Management** | TransactionResource | Transaction, Account | ✅ Complete |
| | WalletTypeResource | WalletType | ✅ Complete |
| | FundingCategoryResource | FundingCategory | ✅ Complete |
| | FundingSourceResource | FundingSource | ✅ Complete |
| **Content Management** | NotificationResource | Notification | ✅ Complete |
| | VoucherResource | Voucher, VoucherRedemption | ✅ Complete |
| | Deposit Cluster | ManualDepositMethod, AutomaticDepositMethod, DepositHistory, ManualDepositRequest | ✅ Complete |
| **Operations** | KYC Management Cluster | KycDocument, KycTemplate | ✅ Complete |
| | Loan Management Cluster | Loan, Grant, GrantCategory | ✅ Complete |
| | Withdraw Cluster | ManualWithdrawMethod, AutomaticWithdrawMethod, WithdrawHistory, ManualWithdrawRequest, ScheduledWithdraw | ✅ Complete |
| | Transfer Cluster | WireTransfer, DomesticTransfer, InterbankTransfer, AccountToAccountTransfer | ✅ Complete |
| **Support Center** | SupportTicket Cluster | SupportTicket, SupportCategory | ✅ Complete |
| **System** | Settings Page | Setting | ✅ Complete |
| | SendNewsletter Page | - | ✅ Complete |
| | ManageReferrals Page | ReferralSetting | ✅ Complete |
| | Profile Page | - | ✅ Complete |

#### Models Available (23 Total)
```
Account, FundingCategory, FundingSource, Grant, GrantCategory, 
KycDocument, KycTemplate, Loan, Notification, PaymentGateway, 
Rank, Referral, ReferralSetting, Setting, SupportCategory, 
SupportTicket, Transaction, Transfer, User, UserRankHistory, 
Voucher, VoucherRedemption, WalletType
```

### ✅ Admin Settings - Keys to Reference

All settings must be passed from backend to frontend. Here's the complete list:

#### Site & Branding
| Key | Description | Default |
|-----|-------------|---------|
| `site_name` | Platform name | 'National Resource Benefits' |
| `site_logo` | Logo (light mode) | null |
| `site_logo_dark` | Logo (dark mode) | null |
| `site_favicon` | Favicon | null |
| `support_email` | Support email | null |
| `support_phone` | Support phone | null |
| `address` | Company address | null |
| `social_facebook` | Facebook URL | null |
| `social_twitter` | Twitter/X URL | null |
| `social_instagram` | Instagram URL | null |
| `social_linkedin` | LinkedIn URL | null |
| `social_youtube` | YouTube URL | null |
| `social_tiktok` | TikTok URL | null |

#### SEO Settings
| Key | Description | Default |
|-----|-------------|---------|
| `seo_meta_title` | Meta title | null |
| `seo_meta_description` | Meta description | null |
| `seo_meta_keywords` | Meta keywords (JSON) | null |
| `seo_og_image` | Open Graph image | null |
| `seo_google_analytics` | GA ID | null |
| `seo_google_tag_manager` | GTM ID | null |
| `seo_facebook_pixel` | FB Pixel ID | null |

#### Transaction Limits & Fees
| Key | Description | Default |
|-----|-------------|---------|
| `deposit_min` | Min deposit | 10 |
| `deposit_max` | Max deposit | 50000 |
| `deposit_limit_daily` | Daily deposit limit | 100000 |
| `withdrawal_min` | Min withdrawal | 50 |
| `withdrawal_max` | Max withdrawal | 50000 |
| `withdrawal_limit_daily` | Daily withdrawal limit | 25000 |
| `transfer_internal_active` | Enable internal transfers | true |
| `transfer_self_active` | Enable self transfers | true |
| `transfer_internal_min` | Min internal transfer | 1 |
| `transfer_internal_max` | Max internal transfer | 5000 |
| `transfer_internal_fee_percent` | Transfer fee % | 0 |
| `transfer_internal_fee_fixed` | Transfer fixed fee | 0 |
| `transfer_wire_max` | Max wire transfer | 100000 |
| `transfer_domestic_max` | Max domestic transfer | 50000 |

#### Feature Toggles
| Key | Description | Default |
|-----|-------------|---------|
| `applications_active` | Enable applications | true |
| `maintenance_mode` | Maintenance mode | false |
| `referral_enabled` | Enable referrals | true |
| `referral_bonus` | Referral bonus amount | 10 |
| `loans_enabled` | Enable loans | true |
| `loan_min_amount` | Min loan | 100 |
| `loan_max_amount` | Max loan | 10000 |
| `loan_interest_rate` | Interest rate % | 5 |

#### Mail/SMTP Settings (For Backend Email Sending)
| Key | Description | Default |
|-----|-------------|---------|
| `mail_mailer` | Mail driver | 'smtp' |
| `mail_host` | SMTP host | 'smtp.mailtrap.io' |
| `mail_port` | SMTP port | '2525' |
| `mail_username` | SMTP username | null |
| `mail_password` | SMTP password | null |
| `mail_encryption` | Encryption (tls/ssl) | 'tls' |
| `mail_from_address` | From email | null |
| `mail_from_name` | From name | null |

#### Other
| Key | Description | Default |
|-----|-------------|---------|
| `currency_symbol` | Currency symbol | '$' |
| `hero_title` | Hero section title | 'Applications are NOW Available!' |
| `registration_fee` | Registration fee | 0 |

### ✅ Email System Already Built

#### Mailable Classes
- `App\Mail\TransferNotification` - Transfer confirmation emails
- `App\Mail\UserNewsletter` - Newsletter/marketing emails

#### Notification Classes
- `App\Notifications\AdminAlert` - Admin notifications
- `App\Notifications\DepositReceived` - Deposit confirmations
- `App\Notifications\GeneralAnnouncement` - System announcements
- `App\Notifications\KycStatusUpdated` - KYC status changes

### ✅ User Permissions System (Built into User Model)

| Permission | Field | Description |
|------------|-------|-------------|
| Active Status | `is_active` | User can login |
| Deposit | `can_deposit` | User can make deposits |
| Exchange | `can_exchange` | User can exchange currencies |
| Transfer | `can_transfer` | User can transfer funds |
| Request | `can_request` | User can make requests |
| Withdraw | `can_withdraw` | User can withdraw |
| Voucher | `can_use_voucher` | User can redeem vouchers |

### ✅ User KYC Codes (For Withdrawal Security)
- `imf_code` - IMF verification code
- `tax_code` - Tax verification code
- `cot_code` - COT verification code
- `withdrawal_status` - Withdrawal status flag

---

## 🎨 Frontend Tech Stack

### Already Installed & Configured

| Package | Version | Status |
|---------|---------|--------|
| Vue 3 | ^3.4.0 | ✅ Installed |
| Inertia.js Vue 3 | ^2.0.0 | ✅ Installed |
| PrimeVue | ^4.5.4 | ✅ Installed |
| @primevue/themes (Aura) | ^4.5.4 | ✅ Installed |
| PrimeIcons | ^7.0.0 | ✅ Installed |
| Chart.js | ^4.5.1 | ✅ Installed |
| vue-chartjs | ^5.3.3 | ✅ Installed |
| @vueuse/core | ^14.1.0 | ✅ Installed |
| Tailwind CSS | ^3.4.19 | ✅ Installed |
| Ziggy (Laravel routes in JS) | - | ✅ Installed |

### PrimeVue Configuration (app.js)
```javascript
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';

// Already configured with Aura theme
.use(PrimeVue, {
    theme: {
        preset: Aura
    }
})
```

---

## 📁 Current Frontend Structure

```
resources/js/
├── app.js                    # Vue/Inertia/PrimeVue setup ✅
├── bootstrap.js              # Axios config ✅
├── Components/               # Reusable components
│   ├── ApplicationLogo.vue   # Basic logo ⚠️ Needs update
│   ├── Checkbox.vue          ✅
│   ├── DangerButton.vue      ✅
│   ├── Dropdown.vue          ✅
│   ├── DropdownLink.vue      ✅
│   ├── InputError.vue        ✅
│   ├── InputLabel.vue        ✅
│   ├── Modal.vue             ✅
│   ├── NavLink.vue           ✅
│   ├── PrimaryButton.vue     ✅
│   ├── ResponsiveNavLink.vue ✅
│   ├── SecondaryButton.vue   ✅
│   └── TextInput.vue         ✅
├── Layouts/
│   ├── AuthenticatedLayout.vue  # Main dashboard layout ⚠️ Needs major update
│   └── GuestLayout.vue          # Login/Register layout ✅
└── Pages/
    ├── Auth/                    # Auth pages ✅ (Keep as-is)
    ├── Dashboard.vue            # Main dashboard ❌ Empty shell
    ├── Deposit/
    │   └── ManualInstructions.vue  # Manual deposit page ✅
    ├── Profile/                 # Profile pages ✅ (Keep as-is)
    ├── Support/                 # Support pages (needs review)
    └── Welcome.vue              # Landing page ❌ (Waiting for HTML)
```

---

## 🚀 Implementation Phases

### Phase 1: Foundation & Layout (Current Priority)

#### 1.1 Create Base Dashboard Layout
**File:** `resources/js/Layouts/DashboardLayout.vue`

Features:
- [ ] Mobile-first responsive sidebar (collapsible)
- [ ] Top navigation bar with user menu
- [ ] Dynamic logo from settings
- [ ] Notification bell with count
- [ ] Mobile bottom navigation
- [ ] Dark mode toggle (optional)

PrimeVue Components to Use:
- `Sidebar` - Collapsible sidebar
- `Menu` / `PanelMenu` - Navigation menu
- `Avatar` - User avatar
- `Badge` - Notification count
- `Button` - Actions

#### 1.2 Create Settings Service
**File:** `resources/js/Services/settings.js`

```javascript
// Example: Access settings passed via Inertia
export function useSiteSettings() {
    const page = usePage();
    return page.props.settings || {};
}
```

#### 1.3 Update ApplicationLogo Component
**File:** `resources/js/Components/ApplicationLogo.vue`

- [ ] Accept logo URL from settings
- [ ] Support light/dark variants
- [ ] Fallback to text if no logo

### Phase 2: User Dashboard Home

#### 2.1 Dashboard Overview
**File:** `resources/js/Pages/Dashboard.vue`

Features:
- [ ] Account balance cards (all wallets)
- [ ] Quick action buttons (Deposit, Transfer, Withdraw)
- [ ] Recent transactions list
- [ ] KYC status banner
- [ ] Notifications preview
- [ ] Chart: Balance over time

PrimeVue Components:
- `Card` - Balance cards
- `Button` - Quick actions
- `DataTable` - Recent transactions
- `Tag` - Status badges
- `Message` - KYC alerts
- `Chart` (via vue-chartjs)

#### 2.2 Backend Controller
**File:** `app/Http/Controllers/DashboardController.php`

```php
// Pass to Inertia:
return Inertia::render('Dashboard', [
    'user' => $user->load(['accounts.walletType', 'rank']),
    'accounts' => $user->accounts,
    'recentTransactions' => $user->transactions()->latest()->take(5)->get(),
    'kycStatus' => $user->kyc_verified_at ? 'verified' : 'pending',
    'notifications' => $user->notifications()->unread()->count(),
    'settings' => [
        'site_name' => Setting::get('site_name'),
        'currency_symbol' => Setting::get('currency_symbol'),
        'deposit_min' => Setting::get('deposit_min'),
        'deposit_max' => Setting::get('deposit_max'),
        // ... all relevant settings
    ],
]);
```

### Phase 3: KYC System

#### 3.1 KYC Dashboard
**File:** `resources/js/Pages/Kyc/Index.vue`

Features:
- [ ] KYC status overview
- [ ] Required documents list (from KycTemplate)
- [ ] Document upload interface
- [ ] Submission history
- [ ] Status tracking

#### 3.2 KYC Document Upload
**File:** `resources/js/Pages/Kyc/Upload.vue`

PrimeVue Components:
- `FileUpload` - Document upload
- `Steps` - Progress indicator
- `Message` - Status messages

### Phase 4: Accounts & Wallet

#### 4.1 Accounts List
**File:** `resources/js/Pages/Accounts/Index.vue`

Features:
- [ ] List all user accounts/wallets
- [ ] Balance per wallet type
- [ ] Account details view
- [ ] Transaction history per account

#### 4.2 Account Details
**File:** `resources/js/Pages/Accounts/Show.vue`

PrimeVue Components:
- `Card` - Account card
- `DataTable` - Transactions
- `Paginator` - Pagination
- `Tag` - Status

### Phase 5: Deposit System

#### 5.1 Deposit Hub
**File:** `resources/js/Pages/Deposit/Index.vue`

Features:
- [ ] Deposit method selection (Manual/Automatic)
- [ ] Amount input with limits from settings
- [ ] Fee calculation
- [ ] Confirmation modal

#### 5.2 Manual Deposit
**File:** `resources/js/Pages/Deposit/Manual.vue`

- [ ] Bank details display
- [ ] Payment instructions
- [ ] Receipt upload
- [ ] Pending status tracking

#### 5.3 Automatic Deposit (Payment Gateway)
**File:** `resources/js/Pages/Deposit/Automatic.vue`

- [ ] Gateway selection
- [ ] Amount entry
- [ ] Redirect to payment

### Phase 6: Transfer System

#### 6.1 Transfer Hub
**File:** `resources/js/Pages/Transfer/Index.vue`

Transfer Types (if enabled in settings):
- [ ] Account-to-Account (own accounts)
- [ ] User-to-User (internal platform)
- [ ] External Bank (domestic)
- [ ] Wire Transfer

#### 6.2 Transfer Forms
- `resources/js/Pages/Transfer/Internal.vue`
- `resources/js/Pages/Transfer/External.vue`

Features:
- [ ] Recipient search/input
- [ ] Amount with fee preview
- [ ] Limits enforcement from settings
- [ ] Confirmation step
- [ ] Success/Error handling

### Phase 7: Withdrawal System

#### 7.1 Withdrawal Hub
**File:** `resources/js/Pages/Withdraw/Index.vue`

Features:
- [ ] Withdrawal method selection
- [ ] Amount with limits from settings
- [ ] Linked bank account selection
- [ ] KYC code verification (imf_code, tax_code, cot_code)
- [ ] Scheduled withdrawals

#### 7.2 Security Verification
**File:** `resources/js/Pages/Withdraw/Verify.vue`

- [ ] Code input forms
- [ ] Verification status

### Phase 8: Voucher Redemption

#### 8.1 Voucher Page
**File:** `resources/js/Pages/Voucher/Index.vue`

Features:
- [ ] Voucher code input
- [ ] Code validation
- [ ] Redemption history
- [ ] Balance credit confirmation

PrimeVue Components:
- `InputText` - Code input
- `Button` - Redeem button
- `DataTable` - History
- `Dialog` - Success/Error

### Phase 9: Loans & Grants

#### 9.1 Loans Dashboard
**File:** `resources/js/Pages/Loans/Index.vue`

Features:
- [ ] Available loan offers
- [ ] Loan application form
- [ ] Active loans status
- [ ] Payment schedule
- [ ] Repayment interface

#### 9.2 Grants Discovery
**File:** `resources/js/Pages/Grants/Index.vue`

- [ ] Available grants by category
- [ ] Eligibility checker
- [ ] Application status

### Phase 10: Notifications

#### 10.1 Notifications Center
**File:** `resources/js/Pages/Notifications/Index.vue`

Features:
- [ ] All notifications list
- [ ] Read/Unread filter
- [ ] Mark as read
- [ ] Delete notifications
- [ ] Real-time updates (optional)

### Phase 11: Support System

#### 11.1 Support Dashboard
**File:** `resources/js/Pages/Support/Index.vue`

Already partially built. Enhance with:
- [ ] Ticket list with status
- [ ] New ticket form
- [ ] Ticket detail view
- [ ] Reply functionality
- [ ] FAQ section

### Phase 12: Profile & Settings

#### 12.1 Enhanced Profile
**File:** `resources/js/Pages/Profile/Edit.vue`

Already exists. Add:
- [ ] Avatar upload
- [ ] Two-factor authentication (future)
- [ ] Notification preferences
- [ ] Security settings

---

## 📧 Email Notifications to Implement

### Transaction Emails (Must Use Admin SMTP Settings)

| Event | Email Class | Template |
|-------|-------------|----------|
| Deposit Received | DepositReceived | `emails.deposit-received` |
| Deposit Approved | Create New | `emails.deposit-approved` |
| Withdrawal Requested | Create New | `emails.withdrawal-requested` |
| Withdrawal Completed | Create New | `emails.withdrawal-completed` |
| Transfer Sent | TransferNotification | `emails.transfer-sent` |
| Transfer Received | Create New | `emails.transfer-received` |
| Loan Approved | Create New | `emails.loan-approved` |
| Loan Payment Due | Create New | `emails.loan-payment-due` |
| Voucher Redeemed | Create New | `emails.voucher-redeemed` |
| KYC Status Change | KycStatusUpdated | `emails.kyc-status` |

### Email Service Configuration
**File:** `app/Services/DynamicMailService.php`

```php
// Must read SMTP settings from database
$config = [
    'transport' => Setting::get('mail_mailer', 'smtp'),
    'host' => Setting::get('mail_host'),
    'port' => Setting::get('mail_port'),
    'encryption' => Setting::get('mail_encryption'),
    'username' => Setting::get('mail_username'),
    'password' => Setting::get('mail_password'),
];
```

---

## 🔄 Backend-Frontend Data Flow

### Global Props (via HandleInertiaRequests Middleware)

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'avatar_url' => $request->user()->getFilamentAvatarUrl(),
                'kyc_verified' => (bool) $request->user()->kyc_verified_at,
                'permissions' => [
                    'can_deposit' => $request->user()->can_deposit,
                    'can_withdraw' => $request->user()->can_withdraw,
                    'can_transfer' => $request->user()->can_transfer,
                    'can_use_voucher' => $request->user()->can_use_voucher,
                ],
            ] : null,
            'impersonating' => session()->has('impersonator_id'),
        ],
        'settings' => [
            'site_name' => Setting::get('site_name'),
            'site_logo' => Setting::get('site_logo'),
            'site_logo_dark' => Setting::get('site_logo_dark'),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
            'maintenance_mode' => Setting::get('maintenance_mode', false),
        ],
        'flash' => [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
        ],
    ];
}
```

---

## 📱 Mobile-First Design Principles

### Breakpoints (Tailwind)
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px
- `2xl`: 1536px

### Mobile Priorities
1. **Touch-friendly** - Min 44px tap targets
2. **Thumb-zone navigation** - Important actions within reach
3. **Bottom navigation** - Primary nav on mobile
4. **Collapsible content** - Accordion for details
5. **Full-width forms** - Easy input on small screens
6. **Swipe gestures** - For lists and cards (optional)

### PrimeVue Mobile Components
- `Drawer` - Side panel navigation
- `SpeedDial` - Quick actions
- `MeterGroup` - Progress indicators
- `Skeleton` - Loading states

---

## 📋 Implementation Checklist

### Immediate Priority (Phase 1-2) ✅ COMPLETED

- [x] Create `DashboardController.php`
- [x] Create `DashboardLayout.vue` with mobile sidebar
- [x] Update `HandleInertiaRequests.php` for global props
- [x] Build `Dashboard.vue` with:
  - [x] Balance cards
  - [x] Quick actions
  - [x] Recent transactions
  - [x] KYC status
- [x] Create `DynamicMailService.php` for dynamic SMTP

### Next Priority (Phase 3-5)

- [ ] KYC upload interface
- [ ] Accounts overview
- [ ] Deposit system (manual + automatic)

### Later Phases (6-12)

- [ ] Transfer system
- [ ] Withdrawal system
- [ ] Voucher redemption
- [ ] Loans interface
- [ ] Enhanced notifications
- [ ] Profile improvements

---

## 🔗 Required Routes (web.php additions)

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Accounts
    Route::resource('accounts', AccountController::class)->only(['index', 'show']);
    
    // KYC
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc.index');
    Route::post('/kyc/upload', [KycController::class, 'upload'])->name('kyc.upload');
    
    // Deposits
    Route::prefix('deposit')->name('deposit.')->group(function () {
        Route::get('/', [DepositController::class, 'index'])->name('index');
        Route::get('/manual', [DepositController::class, 'manual'])->name('manual');
        Route::post('/manual', [DepositController::class, 'storeManual'])->name('manual.store');
        Route::get('/automatic', [DepositController::class, 'automatic'])->name('automatic');
    });
    
    // Transfers
    Route::prefix('transfer')->name('transfer.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::post('/internal', [TransferController::class, 'internal'])->name('internal');
        Route::post('/external', [TransferController::class, 'external'])->name('external');
    });
    
    // Withdrawals
    Route::prefix('withdraw')->name('withdraw.')->group(function () {
        Route::get('/', [WithdrawController::class, 'index'])->name('index');
        Route::post('/', [WithdrawController::class, 'store'])->name('store');
        Route::post('/verify', [WithdrawController::class, 'verify'])->name('verify');
    });
    
    // Vouchers
    Route::prefix('voucher')->name('voucher.')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::post('/redeem', [VoucherController::class, 'redeem'])->name('redeem');
    });
    
    // Loans
    Route::resource('loans', LoanController::class);
    
    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    });
});
```

---

## 📝 Notes

1. **Auth Pages**: Login and Register pages are already functional with Laravel Breeze. Keep as-is.

2. **Welcome Page**: **PENDING** - Will be converted from provided HTML template.

3. **All Settings**: Must be fetched from database using `Setting::get()` helper.

4. **Email Configuration**: All emails must use admin-configured SMTP settings dynamically.

5. **User Permissions**: Always check `can_deposit`, `can_withdraw`, etc. before showing features.

6. **KYC Enforcement**: Show KYC banner until verified. Block sensitive actions if not verified.

7. **Mobile Priority**: Test all features on mobile viewport first.

---

## 🎯 Next Steps

1. **Start with Phase 1**: Create `DashboardController.php` and `DashboardLayout.vue`
2. **Set up global props**: Update `HandleInertiaRequests.php`
3. **Build Dashboard.vue**: Implement with real data from backend
4. **Test mobile responsiveness**: Ensure all components work on small screens
5. **Await HTML template**: For Welcome.vue public page conversion

---

*This roadmap will be updated as implementation progresses.*
