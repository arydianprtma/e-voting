<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_faculty',
        'start_time',
        'end_time',
        'is_active',
        'description',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'status_label',
        'is_open_now',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(VoteReceipt::class, 'session_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getIsOpenNowAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return $now->gte($this->start_time) && $now->lte($this->end_time);
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($now->lt($this->start_time)) {
            return 'upcoming';
        }

        if ($now->gt($this->end_time)) {
            return 'ended';
        }

        return 'running';
    }

    /**
     * Dapatkan sesi pemilihan yang sedang aktif untuk fakultas/jurusan pemilih.
     */
    public static function getActiveSessionForFaculty(?string $faculty): ?self
    {
        $now = now();

        // 1. Cek sesi aktif khusus jurusan ini terlebih dahulu
        if ($faculty) {
            $specific = self::active()
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->where('target_faculty', $faculty)
                ->first();

            if ($specific) {
                return $specific;
            }
        }

        // 2. Jika tidak ada sesi spesifik, cek sesi umum (target_faculty null atau kosong)
        return self::active()
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where(function ($q) {
                $q->whereNull('target_faculty')
                    ->orWhere('target_faculty', '');
            })
            ->first();
    }

    /**
     * Dapatkan sesi yang sedang berjalan secara umum (untuk tampilan beranda).
     */
    public static function getCurrentRunningSession(): ?self
    {
        $now = now();

        return self::active()
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->orderByRaw('CASE WHEN target_faculty IS NOT NULL THEN 1 ELSE 2 END')
            ->first();
    }
}
