<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Filament\Models\Contracts\HasAvatar;

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'referral_code',
        'referred_by',
        
        // Funding Profile
        'funding_amount',
        'funding_category',
        'phone',
        'citizenship_status',
        'zip_code',
        'city',
        'state',
        'age_range',
        'gender',
        'ethnicity',
        'employment_status',

        'current_rank_id',
        'avatar_url',
        'is_active',
        'can_deposit',
        'can_exchange',
        'can_transfer',
        'can_request',
        'can_withdraw',
        'can_use_voucher',
        'kyc_verified_at',
        'idme_verified_at',
        'idme_uuid',
        'imf_code',
        'tax_code',
        'cot_code',
        'withdrawal_status',
        
        // Verification codes
        'email_verification_code',
        'email_verification_code_expires_at',
        'login_otp',
        'login_otp_expires_at',
        'login_otp_verified',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? \Illuminate\Support\Facades\Storage::url($this->avatar_url) : null;
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'can_deposit' => 'boolean',
        'can_exchange' => 'boolean',
        'can_transfer' => 'boolean',
        'can_request' => 'boolean',
        'can_withdraw' => 'boolean',
        'can_use_voucher' => 'boolean',
        'kyc_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'email_verification_code_expires_at' => 'datetime',
        'login_otp_expires_at' => 'datetime',
        'login_otp_verified' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Generate and save an email verification code.
     */
    public function generateEmailVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'email_verification_code' => $code,
            'email_verification_code_expires_at' => now()->addMinutes(30),
        ]);
        
        return $code;
    }

    /**
     * Verify the email verification code.
     */
    public function verifyEmailCode(string $code): bool
    {
        if ($this->email_verification_code !== $code) {
            return false;
        }
        
        if ($this->email_verification_code_expires_at && $this->email_verification_code_expires_at->isPast()) {
            return false;
        }
        
        $this->update([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ]);
        
        return true;
    }

    /**
     * Generate and save a login OTP.
     */
    public function generateLoginOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'login_otp' => $otp,
            'login_otp_expires_at' => now()->addMinutes(10),
            'login_otp_verified' => false,
        ]);
        
        return $otp;
    }

    /**
     * Verify the login OTP.
     */
    public function verifyLoginOtp(string $otp): bool
    {
        if ($this->login_otp !== $otp) {
            return false;
        }
        
        if ($this->login_otp_expires_at && $this->login_otp_expires_at->isPast()) {
            return false;
        }
        
        $this->update([
            'login_otp' => null,
            'login_otp_expires_at' => null,
            'login_otp_verified' => true,
        ]);
        
        return true;
    }

    /**
     * Check if login OTP is required (not yet verified in this session).
     */
    public function requiresLoginOtp(): bool
    {
        return !$this->login_otp_verified;
    }

    /**
     * Clear the login OTP verification status (call on logout).
     */
    public function clearLoginOtpStatus(): void
    {
        $this->update([
            'login_otp' => null,
            'login_otp_expires_at' => null,
            'login_otp_verified' => false,
        ]);
    }

    /**
     * Send the email verification notification with code.
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = $this->generateEmailVerificationCode();
        $this->notify(new \App\Notifications\VerifyEmailWithCode($code));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin') || $this->email === 'admin@admin.com';
    }

    public function accounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Account::class);
    }

    public function kycdocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function supportTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function recentNotifications()
    {
        return $this->notifications()->latest()->limit(10);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = self::generateReferralCode();
            }
        });
    }

    public static function generateReferralCode()
    {
        do {
            $code = strtoupper(\Illuminate\Support\Str::random(10));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function rank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rank::class, 'current_rank_id');
    }

    public function rankHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserRankHistory::class);
    }

    public function fundingApplications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FundingApplication::class);
    }

    public function linkedWithdrawalAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LinkedWithdrawalAccount::class);
    }

    public function activeLinkedWithdrawalAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LinkedWithdrawalAccount::class)->where('is_active', true);
    }
}
