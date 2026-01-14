# Email Notification Audit & Roadmap

## 1. System / Authentication
| Event | Recipient | Trigger | Current Status | Action Needed |
| :--- | :--- | :--- | :--- | :--- |
| **Welcome Email** | User | Registration | ✅ Default Laravel | Customize template |
| **Verify Email** | User | Registration | 🟡 Disabled | Enable `MustVerifyEmail` if needed |
| **Reset Password** | User | Forgot Password | ✅ Default Laravel | Customize template |

## 2. Transactions & Banking
| Event | Recipient | Trigger | Current Status | Action Needed |
| :--- | :--- | :--- | :--- | :--- |
| **New Deposit** | Admin | User initiates | ❌ None | Add Notification to Admin |
| **Deposit Success** | User | Callback Success | ❌ None | Add Email in `PaymentController` |
| **Deposit Failed** | User | Callback Fail | ❌ None | Add Email in `PaymentController` |
| **Withdraw Request** | Admin | User requests | ❌ None | Add Notification to Admin |
| **Withdraw Processed** | User | Admin approves | ❌ None | Add Email in `WithdrawResource` |

## 3. KYC & Compliance
| Event | Recipient | Trigger | Current Status | Action Needed |
| :--- | :--- | :--- | :--- | :--- |
| **Document Submitted** | Admin | User upload | ❌ None | Add Notification to Admin |
| **Document Approved** | User | Admin action | 🟡 Database Only | Add Mail Channel |
| **Document Rejected** | User | Admin action | 🟡 Database Only | Add Mail Channel |

## 4. Support System
| Event | Recipient | Trigger | Current Status | Action Needed |
| :--- | :--- | :--- | :--- | :--- |
| **New Ticket** | Admin | User creates | ❌ None | Add Notification to Admin |
| **Admin Reply** | User | Admin replies | ❌ None | Add Email in `TicketResource` |

## 5. Referrals
| Event | Recipient | Trigger | Current Status | Action Needed |
| :--- | :--- | :--- | :--- | :--- |
| **New Referral** | Referrer | User joins | ❌ None | Add Email in `UserObserver` |

---

## Technical Strategy
We will use **Filament Notifications** for both Admin and User communications. Filament's `Notification::make()` supports `sendToDatabase()` (which allows in-app bells) AND `mail()` (which sends emails).

**Required Changes:**
1.  **Users Model**: Ensure `User` model has the `routeNotificationForMail` method if using custom routing, but usually default is fine.
2.  **Notification Classes**: For complex emails, we create `php artisan make:notification`. For simple ones, we use inline Filament Notifications.
