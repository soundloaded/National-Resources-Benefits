<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grant extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description',
        'grant_category_id',
        'min_amount',
        'max_amount',
        'eligibility_criteria',
        'application_deadline',
        'funding_source',
        'url',
        'requirements',
        'status',
    ];
    
    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(GrantCategory::class, 'grant_category_id');
    }
}
