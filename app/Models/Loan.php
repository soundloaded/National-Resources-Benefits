<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'due_date' => 'datetime',
        'amount' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'interest_rate' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($loan) {
            $loan->loan_id = strtoupper(uniqid('LOAN-'));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loanPlan()
    {
        return $this->belongsTo(LoanPlan::class);
    }
}
