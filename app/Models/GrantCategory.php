<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrantCategory extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function grants(): HasMany
    {
        return $this->hasMany(Grant::class);
    }
}
