# Admin Backend Audit Report

**Date:** January 12, 2026  
**Purpose:** Identify hardcoded values, dummy data, and areas needing dynamic configuration in the admin backend.

---

## 🔴 Critical Issues

### 1. **LatestTransactions Widget** - Missing Transaction Types
**File:** `app/Filament/Widgets/LatestTransactions.php`  
**Issue:** Transaction type badge colors are incomplete - missing new types
```php
// Current (incomplete):
'deposit' => 'success',
'withdrawal' => 'danger',
'transfer_in' => 'success',
'transfer_out' => 'warning',

// Missing types:
// - loan, loan_repayment
// - referral_reward, rank_reward  
// - funding_disbursement, grant
```
**Fix:** Add all transaction types to the color mapping.

---

### 2. **UserResource** - Hardcoded Avatar URL
**File:** `app/Filament/Resources/UserResource.php` (Line ~52)  
**Issue:** External service dependency for avatars
```php
->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->name))
```
**Risk:** External API dependency, privacy concerns  
**Fix:** Use local avatar generation or configurable placeholder

---

### 3. **UserResource** - Hardcoded Currency Symbols
**File:** `app/Filament/Resources/UserResource.php` (Lines ~143, ~145, ~168)  
**Issue:** Multiple `->prefix('$')` instances instead of using Setting
```php
Forms\Components\TextInput::make('amount')
    ->numeric()
    ->prefix('$') // Ideally dynamic based on selected wallet currency
```
**Fix:** Use `Setting::get('currency_symbol', '$')` or wallet currency

---

### 4. **UserResource** - Hardcoded Transaction/Stat Currencies  
**File:** `app/Filament/Resources/UserResource.php` (Lines 345-365)
**Issue:** Stats hardcoded to USD
```php
Components\TextEntry::make('stat_deposit')
    ->money('USD')
Components\TextEntry::make('stat_withdraw')
    ->money('USD')
Components\TextEntry::make('stat_total_balance')
    ->money('USD')
```
**Fix:** Make currency dynamic based on user's primary account or setting

---

### 5. **UserResource** - Hardcoded Funding Categories
**File:** `app/Filament/Resources/UserResource.php` (Lines 490-496)
**Issue:** Static category options instead of database lookup
```php
Forms\Components\Select::make('funding_category')
    ->options([
        'Personal' => 'Personal Loan',
        'Business' => 'Business Grant',
        'Education' => 'Education Support',
        'Real Estate' => 'Real Estate',
        'Emergency' => 'Emergency Relief',
    ])
```
**Fix:** Pull from `FundingCategory` model: `->relationship('fundingCategory', 'name')`

---

### 6. **AutomaticDepositMethodResource** - Hardcoded Gateway Logo
**File:** `app/Filament/Clusters/Deposit/Resources/AutomaticDepositMethodResource.php` (Line ~48)
**Issue:** External API for placeholder logos
```php
$set('logo', 'https://ui-avatars.com/api/?name=' . urlencode($names[$state] ?? 'Gateway') . '&background=random&color=fff');
```
**Fix:** Use local default images or upload required logo

---

### 7. **BaseTransferResource** - Hardcoded USD Currency
**File:** `app/Filament/Clusters/Transfer/BaseTransferResource.php` (Line ~41, ~65)
**Issue:** Currency hardcoded
```php
Forms\Components\TextInput::make('amount')
    ->prefix('$')
Tables\Columns\TextColumn::make('amount')
    ->money('USD')
```
**Fix:** Use dynamic currency from settings or user account

---

## 🟡 Medium Priority Issues

### 8. **Settings.php** - Default SMTP Host
**File:** `app/Filament/Pages/Settings.php` (Line ~50)
**Issue:** Mailtrap as default - could expose test environment
```php
'mail_host' => Setting::get('mail_host', 'smtp.mailtrap.io'),
```
**Fix:** Change default to empty string or localhost

---

### 9. **TransactionResource** - Incomplete Method Options
**File:** `app/Filament/Resources/TransactionResource.php` (Lines ~56-75)
**Issue:** Method options don't include all reward types
```php
'loan', 'loan_repayment' => [
    'internal' => 'Internal Credit',
    'external' => 'External Funding',
],
// Missing: referral_reward, rank_reward, funding_disbursement, grant
```
**Fix:** Add method options for reward transaction types

---

### 10. **LoanResource** - Hardcoded Default Interest Rate
**File:** `app/Filament/Clusters/LoanManagement/Resources/LoanResource.php` (Line ~53)
**Issue:** Default interest rate not from settings
```php
Forms\Components\TextInput::make('interest_rate')
    ->default(5)
```
**Fix:** Use `Setting::get('loan_interest_rate', 5)`

---

### 11. **RankResource** - Hardcoded Transaction Type Options
**File:** `app/Filament/Resources/RankResource.php` (Lines ~65-70)
**Issue:** Limited transaction types in allowed options
```php
Forms\Components\CheckboxList::make('allowed_transaction_types')
    ->options([
        'deposit' => 'Deposit',
        'send_money' => 'Send Money',
        'payment' => 'Payment',
        'referral_reward' => 'Referral Reward',
    ])
// Missing: withdrawal, transfer_in, transfer_out, loan, etc.
```
**Fix:** Include all relevant transaction types

---

### 12. **VoucherResource** - Hardcoded Category Colors
**File:** `app/Filament/Resources/VoucherResource.php` (Lines ~130-138)
**Issue:** Category colors are hardcoded mapping
```php
->colors([
    'success' => 'housing',
    'warning' => 'food',
    'danger' => 'healthcare',
    ...
])
```
**Note:** This is acceptable but could be made configurable if categories become dynamic.

---

### 13. **UserResource** - Hardcoded Age Range Options
**File:** `app/Filament/Resources/UserResource.php` (Lines ~407-414)
**Issue:** Static age ranges
```php
Forms\Components\Select::make('age_range')
    ->options([
        '18-24' => '18-24',
        '25-34' => '25-34',
        ...
    ])
```
**Note:** Acceptable for demographic data but could be configurable.

---

## 🟢 Acceptable Hardcoded Values

These are intentionally hardcoded and acceptable:

1. **Status Options** (pending, completed, failed, cancelled) - Standard states
2. **Transaction Types** (deposit, withdrawal, etc.) - Core system values
3. **Gender Options** - Standard demographic fields
4. **Citizenship Status** - US-focused application (by design)
5. **Navigation Icons** - UI elements
6. **Form Column Spans** - Layout configuration

---

## 📋 Action Items Summary

| Priority | File | Issue | Status |
|----------|------|-------|--------|
| 🔴 High | LatestTransactions.php | Missing transaction type colors | ✅ Fixed |
| 🔴 High | UserResource.php | External avatar API | ✅ Fixed (Gravatar) |
| 🔴 High | UserResource.php | Hardcoded currencies ($, USD) | ✅ Fixed (Currency Helper) |
| 🔴 High | UserResource.php | Hardcoded funding categories | ✅ Fixed |
| 🟡 Medium | AutomaticDepositMethodResource.php | External logo API | ✅ Fixed (PaymentLogos Helper) |
| 🟡 Medium | BaseTransferResource.php | Hardcoded USD | ✅ Fixed (Currency Helper) |
| 🟡 Medium | Settings.php | Mailtrap default | ✅ Fixed |
| 🟡 Medium | TransactionResource.php | Missing method options | ✅ Fixed |
| 🟡 Medium | LoanResource.php | Default interest rate | ✅ Fixed |
| 🟡 Medium | RankResource.php | Limited transaction types | ✅ Fixed |

---

## 🆕 New Helpers Created

### 1. Currency Helper (`app/Helpers/Currency.php`)
- Dynamic currency symbol and formatting
- Supports 70+ currencies (fiat + crypto)
- Methods: `getSymbol()`, `format()`, `getDefaultCode()`, `getOptions()`

### 2. Avatar Helper (`app/Helpers/Avatar.php`)
- Gravatar integration with fallbacks
- Methods: `gravatar()`, `initials()`, `robohash()`

### 3. PaymentLogos Helper (`app/Helpers/PaymentLogos.php`)
- Open source payment gateway logos
- Methods: `get()`, `getProcessors()`, `placeholder()`

### 4. Global Helper Functions (`app/Helpers/helpers.php`)
- `currency_symbol()` - Get currency symbol
- `format_currency()` - Format amount with currency
- `gravatar()` - Get gravatar URL
- `payment_logo()` - Get payment gateway logo

---

## 🛠️ Recommended Fixes

### Quick Wins (< 5 minutes each)

1. **Update LatestTransactions.php colors:**
```php
->color(fn (string $state): string => match ($state) {
    'deposit', 'transfer_in', 'referral_reward', 'rank_reward', 'funding_disbursement', 'grant' => 'success',
    'withdrawal', 'transfer_out' => 'danger',
    'loan', 'loan_repayment' => 'info',
    default => 'gray',
})
```

2. **Update Settings.php mail_host default:**
```php
'mail_host' => Setting::get('mail_host', ''),
```

3. **Update LoanResource interest_rate default:**
```php
->default(fn () => Setting::get('loan_interest_rate', 5))
```

### Medium Effort

4. **Create helper for currency display:**
```php
// app/Helpers/Currency.php
function getCurrencySymbol(): string {
    return Setting::get('currency_symbol', '$');
}
```

5. **Replace UserResource funding categories with model relationship**

6. **Add local avatar fallback or use Gravatar with privacy option**

---

## 📊 Statistics

- **Total Files Audited:** 25+
- **Critical Issues Found:** 6
- **Medium Issues Found:** 6
- **Files with No Issues:** ~15

---

*Generated by Copilot Audit Tool*
