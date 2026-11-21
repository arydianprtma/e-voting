<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_code',
        'session_id',
        'voter_hash',
        'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VotingSession::class, 'session_id');
    }
}
