<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            $ticket->ticket_id = strtoupper(uniqid('TICKET-'));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
