<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_number',
        'candidate_type',
        'chairman_name',
        'deputy_name',
        'faculty_info',
        'vision',
        'missions',
        'photo_path',
        'votes_count',
    ];

    protected $casts = [
        'missions' => 'array',
        'candidate_number' => 'integer',
        'votes_count' => 'integer',
    ];

    /**
     * Cek apakah calon merupakan kandidat tunggal (solo).
     */
    public function isSolo(): bool
    {
        return $this->candidate_type === 'solo';
    }

    /**
     * Dapatkan nama lengkap paslon / calon tunggal.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->isSolo() || empty($this->deputy_name)
            ? $this->chairman_name
            : "{$this->chairman_name} & {$this->deputy_name}";
    }
}
