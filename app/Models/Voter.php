<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'faculty',
        'study_program',
        'birth_date',
        'password',
        'token',
        'token_claimed_at',
        'has_voted',
        'voted_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'password' => 'hashed',
        'token_claimed_at' => 'datetime',
        'has_voted' => 'boolean',
        'voted_at' => 'datetime',
    ];

    protected $appends = [
        'has_password',
    ];

    public function getHasPasswordAttribute(): bool
    {
        return ! empty($this->password);
    }
}
